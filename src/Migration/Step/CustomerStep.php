<?php

declare(strict_types=1);

namespace App\Migration\Step;

use App\Entity\Customer\Customer;
use App\Entity\Customer\CustomerAddress;
use App\Entity\Customer\CustomerStore;
use App\Entity\Shop\Store;
use App\Migration\IdMapper;
use App\Migration\LegacyDb;
use App\Migration\MigrationReport;
use App\Migration\Source;
use Doctrine\ORM\EntityManagerInterface;

/** {prefix}zakaznici -> Customer + billing/shipping address + store link. MD5 kept as legacy hash. */
final class CustomerStep implements MigrationStep
{
    private const FLUSH_EVERY = 500;

    public function __construct(
        private readonly LegacyDb $db,
        private readonly EntityManagerInterface $em,
        private readonly IdMapper $ids,
    ) {
    }

    public function name(): string
    {
        return 'customers';
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
        $storeId = $store->id;
        $this->ids->warmup($source, 'customer');

        // dedup key -> Customer (pending, this batch) | int (id, already flushed). Never reset:
        // the new `customers.email` column collates case- & accent-insensitively, so the legacy
        // rows "kočenda.l@…" and "kocenda.l@…" collide in the DB and must map to one Customer.
        /** @var array<string,Customer|int> $known */
        $known = [];
        /** @var list<array{0:int,1:string}> $batch oldId -> dedup key, mapped to IdMap after each flush */
        $batch = [];
        $n = 0;

        foreach ($this->db->iterate('SELECT * FROM '.$source->t('zakaznici')) as $r) {
            $oldId = (int) $r['id'];
            if (null !== $this->ids->get($source, 'customer', $oldId)) {
                $report->add('customers.skipped');
                continue;
            }
            $email = strtolower(trim((string) $r['email']));
            if ('' === $email || !str_contains($email, '@')) {
                $report->add('customers.no_email');
                continue;
            }
            $key = self::dedupKey($email);

            $hit = $known[$key] ?? null;
            if (null === $hit) {
                $hit = $this->em->getRepository(Customer::class)->findOneBy(['email' => $email]);
            }

            if ($hit instanceof Customer || \is_int($hit)) {
                // merge: another legacy row already produced this Customer
                $c = \is_int($hit) ? $this->em->getReference(Customer::class, $hit) : $hit;
                if (!$this->hasStoreLink($c, $storeId)) {
                    $c->stores->add(new CustomerStore($c, $store));
                }
                $known[$key] = $c;
                if (!$dryRun) {
                    $batch[] = [$oldId, $key];
                }
                $report->add('customers.merged');
            } else {
                $c = new Customer($email);
                $c->legacyMd5 = self::clean($r['heslo'], 255);
                $c->legacyKey = $source->value.'_'.$oldId;
                $c->gender = self::clean($r['pohlavi'], 8);

                $addr = new CustomerAddress($c);
                $addr->type = 'billing';
                $addr->isDefault = true;
                $a = $addr->address;
                $a->company = self::clean($r['firma'], 120);
                [$fn, $ln] = self::splitName((string) ($r['jmeno_single'] ?: ''), (string) ($r['prijimeni_single'] ?: ''), (string) $r['jmeno']);
                $a->firstName = self::clip($fn, 120);
                $a->lastName = self::clip($ln, 120);
                $street = self::clean($r['ulice_single']) ? trim(($r['ulice_single'] ?? '').' '.($r['cp_single'] ?? '')) : self::clean($r['ulice']);
                $a->street = self::clip($street, 200);
                $a->city = self::clean($r['mesto'], 120);
                $a->zip = self::clean($r['psc'], 20);
                $a->phone = self::clean($r['telefon'], 20);
                $a->companyId = self::clean($r['ic'], 20);
                $a->vatId = self::clean($r['dic'], 20);
                $c->addresses->add($addr);

                $c->stores->add(new CustomerStore($c, $store));
                $known[$key] = $c;
                if (!$dryRun) {
                    $this->em->persist($c);
                    $batch[] = [$oldId, $key];
                }
                $report->add('customers');
            }

            if (!$dryRun && 0 === ++$n % self::FLUSH_EVERY) {
                $this->flushBatch($source, $batch, $known);
                $this->em->clear();
                $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $source->storeCode()]);
                $storeId = $store?->id;
                $batch = [];
            }
        }

        if (!$dryRun) {
            $this->flushBatch($source, $batch, $known);
        }
    }

    /**
     * @param list<array{0:int,1:string}> $batch
     * @param array<string,Customer|int>  $known rewritten in place: pending Customers -> their new id
     */
    private function flushBatch(Source $source, array $batch, array &$known): void
    {
        $this->em->flush();
        foreach ($batch as [$oldId, $key]) {
            $c = $known[$key] ?? null;
            $id = $c instanceof Customer ? $c->id : (\is_int($c) ? $c : null);
            if (null !== $id) {
                $this->ids->set($source, 'customer', $oldId, $id);
                $known[$key] = $id; // drop the (soon-detached) entity reference
            }
        }
        $this->em->flush(); // persist the IdMap rows created above
    }

    private function hasStoreLink(Customer $c, ?int $storeId): bool
    {
        foreach ($c->stores as $cs) {
            if (null !== $storeId && $cs->store->id === $storeId) {
                return true;
            }
        }

        return false;
    }

    /** Fold to what the DB's case-/accent-insensitive collation would consider equal. */
    private static function dedupKey(string $email): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $email);
        $ascii = false === $ascii ? $email : $ascii;

        return strtolower(preg_replace('/[^a-zA-Z0-9@._+\-]/', '', $ascii) ?? $ascii);
    }

    /** @return array{0:?string,1:?string} */
    private static function splitName(string $first, string $last, string $full): array
    {
        if ('' !== $first || '' !== $last) {
            return [$first ?: null, $last ?: null];
        }
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (\count($parts) < 2) {
            return [$full ?: null, null];
        }
        $ln = array_pop($parts);

        return [implode(' ', $parts), $ln];
    }

    private static function clean(mixed $v, int $max = 255): ?string
    {
        $v = trim((string) $v);

        return '' === $v ? null : mb_substr($v, 0, $max);
    }

    private static function clip(?string $v, int $max): ?string
    {
        return null === $v ? null : mb_substr($v, 0, $max);
    }
}
