<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Customer\Customer;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\Order\OrderStatusHistory;
use App\Entity\Shop\Store;
use App\Enum\OrderStatus;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/** {prefix}objednavky (+_polozky) -> Order + items + a single status history entry. */
final class OrderStep implements MigrationStep
{
    private const STATUS = [
        0 => OrderStatus::New, 1 => OrderStatus::Processing, 2 => OrderStatus::ReadyToShip,
        3 => OrderStatus::Shipped, 4 => OrderStatus::Delivered, 5 => OrderStatus::Cancelled,
        6 => OrderStatus::Returned, 9 => OrderStatus::Cancelled,
    ];

    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'orders';
    }

    public function supports(): array
    {
        return [Source::Tsp, Source::Tsl, Source::Cd];
    }

    public function run(Source $source, MigrationReport $report, bool $dryRun): void
    {
        $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
        if (!$store instanceof Store) {
            return;
        }
        $this->ids->warmup($source, 'order');
        $this->ids->warmup($source, 'customer');
        $prefix = strtoupper($source->storeCode()).'-';
        $n = 0;

        foreach ($this->db->iterate('SELECT * FROM '.$source->t('objednavky').' ORDER BY id') as $r) {
            $oldId = (int) $r['id'];
            if (null !== $this->ids->get($source, 'order', $oldId)) {
                $report->add('orders.skipped');
                continue;
            }
            $o = new Order($store, $prefix.$oldId);
            $o->legacyId = $oldId;
            $o->placedAt = new \DateTimeImmutable((string) ($r['objednavka_datum'] ?: 'now'));
            $o->currency = $store->currency;
            $o->status = isset($r['status_objednavky'])
                ? (self::STATUS[(int) $r['status_objednavky']] ?? OrderStatus::New)
                : OrderStatus::New;
            $o->customerNote = self::clean($r['poznamka'] ?? null);
            $o->coupon = self::clean($r['slevovy_kod'] ?? null, 50);
            $o->source = self::clean($r['zdroj'] ?? null, 120);
            $o->pickupPoint = self::clean($r['vydejni_misto'] ?? null, 255);
            $o->shippingMethodCode = isset($r['doruceni']) ? self::clean('legacy-'.$r['doruceni'], 40) : null;
            $o->billingAddress->firstName = self::clean($r['jmeno'] ?? null, 120);
            $o->billingAddress->company = self::clean($r['firma'] ?? null, 120);

            $itemsTotal = 0;
            foreach ($this->db->all('SELECT * FROM '.$source->t('objednavky_polozky').' WHERE id_objednavky = ?', [$oldId]) as $li) {
                $it = new OrderItem($o);
                $it->nameSnapshot = mb_substr(trim((string) (($li['polozka_single'] ?? '') ?: $li['polozka'])), 0, 255);
                $it->variantSnapshot = self::clean(trim(($li['barva'] ?? '').' / '.($li['velikost'] ?? ''), ' /') ?: null, 120);
                $it->skuSnapshot = self::clean($li['kod_zbozi'] ?? null, 64);
                $it->quantity = max(1, (int) $li['pocet_ks']);
                $it->unitPrice = (int) round((float) $li['cena']);
                $itemsTotal += $it->quantity * $it->unitPrice;
                $o->items->add($it);
                $report->add('order-items');
            }

            // {prefix}objednavky.castka is authoritative when present (tsp/tsl); cooldresy leaves it
            // NULL, so fall back to the sum of the line items.
            $header = isset($r['castka']) ? (int) round((float) $r['castka']) : 0;
            $o->grandTotal = $header > 0 ? $header : $itemsTotal;
            $o->itemsTotal = $o->grandTotal;

            // customer link: by legacy id, else by e-mail carried in kontrolni_retezec ("mail_<junk>")
            $custNew = !empty($r['id_zakaznika']) ? $this->ids->get($source, 'customer', (int) $r['id_zakaznika']) : null;
            if (null !== $custNew) {
                $o->customer = $this->em->getReference(Customer::class, $custNew);
            } elseif (null !== ($email = self::emailFrom($r['kontrolni_retezec'] ?? null))) {
                $o->email = mb_substr($email, 0, 190);
                $c = $this->em->getRepository(Customer::class)->findOneBy(['email' => $email]);
                if ($c instanceof Customer) {
                    $o->customer = $c;
                } else {
                    $report->add('orders.customer_unmatched');
                }
            } else {
                $report->add('orders.customer_missing');
            }

            $h = new OrderStatusHistory($o, $o->status);
            $h->changedAt = new \DateTimeImmutable((string) ($r['status_posledni_editace'] ?: $r['objednavka_datum'] ?: 'now'));
            $h->note = 'migrováno ze starého systému';
            $o->statusHistory->add($h);

            if (!$dryRun) {
                $this->em->persist($o);
            }
            $report->add('orders');

            if (!$dryRun && 0 === ++$n % 200) {
                $this->flushMap($source);
                $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
            }
        }
        if (!$dryRun) {
            $this->flushMap($source);
        }
    }

    private function flushMap(Source $source): void
    {
        $this->em->flush();
        foreach ($this->em->getUnitOfWork()->getIdentityMap()[Order::class] ?? [] as $o) {
            if ($o instanceof Order && null !== $o->id && null !== $o->legacyId) {
                $this->ids->set($source, 'order', $o->legacyId, $o->id);
            }
        }
        $this->em->flush();
        $this->em->clear();
        $this->ids->warmup($source, 'customer');
    }

    private static function clean(mixed $v, int $max = 65535): ?string
    {
        $v = trim((string) $v);

        return '' === $v ? null : mb_substr($v, 0, $max);
    }

    /** cooldresy stores "<email>_<junk>" in kontrolni_retezec — pull a valid e-mail out of the front. */
    private static function emailFrom(mixed $v): ?string
    {
        $s = trim((string) $v);
        if ('' === $s || !str_contains($s, '@')) {
            return null;
        }
        if (!preg_match('/^([a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,})/i', $s, $m)) {
            return null;
        }
        $candidate = strtolower($m[1]);

        return filter_var($candidate, \FILTER_VALIDATE_EMAIL) ? $candidate : null;
    }
}
