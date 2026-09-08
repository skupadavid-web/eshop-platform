<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Billing\InvoiceSeries;
use App\Entity\Catalog\Attribute;
use App\Entity\Shop\LegalEntity;
use App\Entity\Shop\PaymentMethod;
use App\Entity\Shop\ShippingMethod;
use App\Entity\Shop\Store;
use App\Entity\Shop\StoreDomain;
use App\Entity\System\AdminUser;
use App\Enum\PaymentGateway;
use App\Enum\ShippingCarrier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed', description: 'Seed base data: stores, legal entity, admin user, methods (idempotent).')]
final class SeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $legal = $this->em->getRepository(LegalEntity::class)->findOneBy(['companyId' => '700 77 461'])
            ?? new LegalEntity('David Skupa', 'Volavkova 1741/1, 162 00 Praha 6', '700 77 461');
        $legal->vatPayer = false;
        $legal->iban = 'CZ5020100000002301596177';
        $legal->bankAccount = '2301596177/2010';
        $legal->samplingRoom = 'U Elektry 650, Praha 9';
        $this->em->persist($legal);

        $defs = [
            ['tsp', 'Trička s potiskem', 'trickaspotiskem.eu', ['cs'], 'cs', 'CZK', 'info@trickaspotiskem.eu', 'tsl'],
            ['tsl', 'Tričká s potlačou', 'trickaspotlacou.eu', ['sk'], 'sk', 'EUR', 'info@trickaspotlacou.eu', 'tsp'],
            ['cd', 'Cool dresy', 'cooldresy.cz', ['cs'], 'cs', 'CZK', 'info@cooldresy.cz', null],
        ];
        /** @var array<string,Store> $stores */
        $stores = [];
        foreach ($defs as [$code, $name, $host, $locales, $def, $cur, $email, $_partner]) {
            $store = $this->em->getRepository(Store::class)->findOneBy(['code' => $code]) ?? new Store($code, $name, $host, $legal);
            $store->name = $name;
            $store->primaryHost = $host;
            $store->locales = $locales;
            $store->defaultLocale = $def;
            $store->currency = $cur;
            $store->theme = $code;
            $store->contactEmail = $email;
            $store->contactPhone = '+420 777 240 837';
            $store->legalEntity = $legal;
            $this->em->persist($store);
            $stores[$code] = $store;

            foreach ([$host, 'www.'.$host] as $h) {
                if (!$this->em->getRepository(StoreDomain::class)->findOneBy(['host' => $h])) {
                    $this->em->persist(new StoreDomain($store, $h));
                }
            }
        }
        $this->em->flush();
        foreach ($defs as [$code, , , , , , , $partner]) {
            $stores[$code]->partnerStore = $partner ? ($stores[$partner] ?? null) : null;
        }

        foreach ($stores as $store) {
            $eur = 'EUR' === $store->currency;
            $ship = [
                ['packeta_point', ShippingCarrier::Packeta, ['cs' => 'Zásilkovna – výdejní místo', 'sk' => 'Packeta – výdajné miesto'], $eur ? 250 : 5900, $eur ? 250 : 5900, true],
                ['post', 'cs' === $store->defaultLocale ? ShippingCarrier::CzechPost : ShippingCarrier::SlovakPost, ['cs' => 'Česká pošta', 'sk' => 'Slovenská pošta'], $eur ? 500 : 8900, $eur ? 500 : 8900, false],
                ['pickup', ShippingCarrier::Pickup, ['cs' => 'Osobní odběr Praha 9', 'sk' => 'Osobný odber Praha 9'], 0, 0, false],
            ];
            foreach ($ship as $i => [$c, $carrier, $labels, $cod, $tr, $pp]) {
                $m = $this->em->getRepository(ShippingMethod::class)->findOneBy(['store' => $store, 'code' => $c]) ?? new ShippingMethod();
                $m->store = $store;
                $m->code = $c;
                $m->carrier = $carrier;
                $m->labels = $labels;
                $m->priceCod = $cod;
                $m->priceTransfer = $tr;
                $m->hasPickupPoints = $pp;
                $m->position = $i;
                $this->em->persist($m);
            }
            $pay = [
                ['card', PaymentGateway::Comgate, ['cs' => 'Platební kartou', 'sk' => 'Platobnou kartou'], 0, false],
                ['transfer', PaymentGateway::BankTransfer, ['cs' => 'Bankovní převod', 'sk' => 'Bankový prevod'], 0, true],
                ['cod', PaymentGateway::CashOnDelivery, ['cs' => 'Dobírka', 'sk' => 'Dobierka'], $eur ? 100 : 2000, true],
            ];
            foreach ($pay as $i => [$c, $gw, $labels, $fee, $enabled]) {
                $m = $this->em->getRepository(PaymentMethod::class)->findOneBy(['store' => $store, 'code' => $c]) ?? new PaymentMethod();
                $m->store = $store;
                $m->code = $c;
                $m->gateway = $gw;
                $m->labels = $labels;
                $m->fee = $fee;
                $m->enabled = $enabled;
                $m->position = $i;
                $this->em->persist($m);
            }
        }

        foreach ([['segment', 'Segment'], ['occasion', 'Příležitost'], ['sport', 'Sport'], ['color', 'Barva'], ['motif', 'Motiv']] as [$c, $n]) {
            if (!$this->em->getRepository(Attribute::class)->findOneBy(['code' => $c])) {
                $a = new Attribute($c, $n);
                $a->filterable = true;
                $this->em->persist($a);
            }
        }

        if (!$this->em->getRepository(InvoiceSeries::class)->findOneBy(['code' => 'main'])) {
            $s = new InvoiceSeries('main');
            $s->prefix = date('Y').'-';
            $this->em->persist($s);
        }

        $admin = $this->em->getRepository(AdminUser::class)->findOneBy(['email' => 'david@skupa.cz']) ?? new AdminUser('david@skupa.cz');
        $admin->name = 'David Skupa';
        $admin->roles = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
        if ('' === $admin->password) {
            $admin->password = $this->hasher->hashPassword($admin, 'admin');
            $io->warning('Admin heslo nastaveno na "admin" — po přihlášení změň.');
        }
        $this->em->persist($admin);

        $this->em->flush();
        $io->success('Základní data nasazena (idempotentně).');

        return Command::SUCCESS;
    }
}
