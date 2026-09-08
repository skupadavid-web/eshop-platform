<?php

declare(strict_types=1);

namespace App\Sample;

/**
 * Hard-coded example catalogue so the E1 storefront renders on *.ddev.site before
 * the real data model (E2) and migration (E3) exist. NOT production data.
 */
final class SampleData
{
    /** @var array<string,Color> */
    public array $colors;
    /** @var list<Category> */
    public array $categories;
    /** @var list<Product> */
    public array $products;
    /** @var list<Page> */
    public array $pages;

    public function __construct()
    {
        $c = static fn (string $k, string $cs, string $sk, string $hex) => new Color($k, $cs, $sk, $hex);
        $this->colors = [];
        foreach ([
            $c('cerna', 'černá', 'čierna', '#1d1c1a'),
            $c('bila', 'bílá', 'biela', '#f2efe9'),
            $c('cervena', 'červená', 'červená', '#c0392b'),
            $c('modra', 'modrá', 'modrá', '#2456c9'),
            $c('tmave_modra', 'tmavě modrá', 'tmavomodrá', '#1e2a52'),
            $c('oranzova', 'oranžová', 'oranžová', '#e07b28'),
            $c('purpurova', 'purpurová', 'purpurová', '#8e3a80'),
            $c('seda', 'šedá', 'sivá', '#9b948a'),
            $c('grafitova', 'grafitová', 'grafitová', '#45433e'),
            $c('zelena', 'zelená', 'zelená', '#3a8f4f'),
            $c('khaki', 'khaki', 'khaki', '#8a7d58'),
            $c('zluta', 'žlutá', 'žltá', '#e6c141'),
        ] as $col) {
            $this->colors[$col->key] = $col;
        }

        $adult = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
        $kids = ['4 roky', '6 let', '8 let', '10 let', '12 let'];
        $ch = static fn (string $slug, string $cs, ?string $sk = null, ?string $parent = null) => new Category($slug, $cs, $sk ?? $cs, 40, [], [], false, $parent);

        $this->categories = [
            new Category('vtipna-tricka', 'Vtipná trička', 'Vtipné tričká', 214, ['tsp', 'tsl'], [
                $ch('vtipne-hlasky', 'Vtipné hlášky', 'Vtipné hlášky', 'vtipna-tricka'),
                $ch('tricka-pro-partu', 'Trička pro partu', 'Tričká pre partiu', 'vtipna-tricka'),
                $ch('tricka-na-akce', 'Trička na akce', 'Tričká na akcie', 'vtipna-tricka'),
            ]),
            new Category('narozeninova-tricka', 'Narozeninová trička', 'Narodeninové tričká', 96, ['tsp', 'tsl'], [
                $ch('podle-rocniku', 'Podle ročníku', 'Podľa ročníka', 'narozeninova-tricka'),
                $ch('kulate-narozeniny', 'Kulaté narozeniny', 'Okrúhle narodeniny', 'narozeninova-tricka'),
            ]),
            new Category('i-love-tricka', 'Trička I love', 'Tričká I love', 340, ['tsp', 'tsl'], [
                $ch('i-love-psy', 'I love psy – dle plemene', 'I love psov – podľa plemena', 'i-love-tricka'),
                $ch('i-love-kocky', 'I love kočky', 'I love mačky', 'i-love-tricka'),
                $ch('i-love-mesta', 'I love města a obce', 'I love mestá a obce', 'i-love-tricka'),
            ]),
            new Category('darky-pro-povolani', 'Dárky podle povolání', 'Darčeky podľa povolania', 158, ['tsp'], array_map(
                static fn (array $p) => $ch($p[0], $p[1], $p[1], 'darky-pro-povolani'),
                [
                    ['darek-pro-programatora', 'Pro programátora'], ['darek-pro-ucitele', 'Pro učitele'],
                    ['darek-pro-doktora', 'Pro doktora'], ['darek-pro-zdravotni-sestru', 'Pro zdravotní sestru'],
                    ['darek-pro-hasice', 'Pro hasiče'], ['darek-pro-policistu', 'Pro policistu'],
                    ['darek-pro-kuchare', 'Pro kuchaře'], ['darek-pro-elektrikare', 'Pro elektrikáře'],
                    ['darek-pro-automechanika', 'Pro automechanika'], ['darek-pro-ridice-kamionu', 'Pro řidiče kamionu'],
                    ['darek-pro-zahradnika', 'Pro zahradníka'], ['darek-pro-ucetniho', 'Pro účetního'],
                    ['darek-pro-manazera', 'Pro manažera'], ['darek-pro-truhlare', 'Pro truhláře'],
                    ['darek-pro-zamecnika', 'Pro zámečníka'], ['darek-pro-lekarnika', 'Pro lékárníka'],
                ]
            ), true),
            new Category('fotbalove-dresy', 'Fotbalové dresy', 'Futbalové dresy', 44, ['cd'], [
                $ch('dresy-domaci', 'Domácí sady', 'Domáce sady', 'fotbalove-dresy'),
                $ch('dresy-vyjezdni', 'Výjezdní sady', 'Výjazdové sady', 'fotbalove-dresy'),
                $ch('brankarske-dresy', 'Brankářské dresy', 'Brankárske dresy', 'fotbalove-dresy'),
            ]),
        ];

        $this->products = [
            new Product('tricko-s-kockou-damske', 'Dámské tričko s kočkou', 'Dámske tričko s mačkou',
                'dárek pro kočkomilky', 'darček pre milovníčky mačiek',
                'Klasické dámské tričko s příjemným střihem a potiskem kočky na hrudi. Gramáž 145 g/m², 100 % česaná bavlna, digitální tisk odolný v pračce do 40 °C.',
                'Klasické dámske tričko s príjemným strihom a potlačou mačky na hrudi. Gramáž 145 g/m², 100 % česaná bavlna.',
                'vtipna-tricka', 'Dámské', '100% bavlna', 145, 'cat', 4.7, 42, ['cs' => 369, 'sk' => 16],
                ['bila', 'cerna', 'cervena', 'modra', 'seda', 'zelena', 'zluta', 'purpurova', 'oranzova'],
                array_slice($adult, 0, 6), ['XXL'], ['tsp', 'tsl']),
            new Product('i-love-odrovice', 'Tričko I love Odrovice', 'Tričko I love Odrovice',
                'dárek pro patriota', 'darček pre patriota',
                'Tričko s velkým červeným srdcem a nápisem I ♥ Odrovice. Ideální dárek pro rodáky a patrioty.',
                'Tričko s veľkým červeným srdcom a nápisom I ♥ Odrovice.',
                'i-love-tricka', 'Pánské', '100% bavlna', 150, 'heart', 4.9, 11, ['cs' => 349, 'sk' => 15],
                ['cerna', 'bila', 'cervena', 'modra', 'zelena', 'khaki', 'grafitova', 'tmave_modra', 'zluta', 'oranzova'],
                $adult, [], ['tsp', 'tsl']),
            new Product('tricko-nic-nemusim', 'Tričko Nic nemusím', 'Tričko Nič nemusím',
                'vtipné tričko', 'vtipné tričko',
                'Když je všechno jasné. Minimalistický nápisový potisk pro milovníky klidu.',
                'Keď je všetko jasné. Minimalistická nápisová potlač.',
                'vtipna-tricka', 'Pánské', '100% bavlna', 150, 'text', 4.6, 58, ['cs' => 349, 'sk' => 15],
                ['cerna', 'bila', 'seda', 'grafitova', 'modra', 'zelena', 'cervena'],
                $adult, ['XXXL'], ['tsp', 'tsl']),
            new Product('tricko-pro-programatora', 'Tričko pro programátora', 'Tričko pre programátora',
                'dárek pro vývojáře', 'darček pre vývojára',
                'Nápis „It works on my machine" pro každého vývojáře. Tmavá trička s bílým potiskem.',
                'Nápis „It works on my machine".',
                'darky-pro-povolani', 'Pánské', '100% bavlna', 150, 'code', 4.8, 23, ['cs' => 389, 'sk' => 16],
                ['cerna', 'grafitova', 'tmave_modra', 'modra', 'seda'],
                $adult, [], ['tsp']),
            new Product('narozeninove-tricko-1985', 'Narozeninové tričko 1985', 'Narodeninové tričko 1985',
                'ročník 1985', 'ročník 1985',
                'Vintage potisk s rokem narození. Skvělý dárek k 40. narozeninám.',
                'Vintage potlač s rokom narodenia.',
                'narozeninova-tricka', 'Unisex', '100% bavlna', 150, 'year', 4.7, 31, ['cs' => 359, 'sk' => 15],
                ['cerna', 'bila', 'seda', 'modra', 'zelena', 'cervena', 'grafitova'],
                $adult, [], ['tsp', 'tsl']),
            new Product('tricko-i-love-psy', 'Tričko I love psy', 'Tričko I love psov',
                'podle plemene', 'podľa plemena',
                'Srdce s tlapkou a názvem plemene. Vyberte si své plemeno v konfigurátoru.',
                'Srdce s labkou a názvom plemena.',
                'i-love-tricka', 'Dámské', '100% bavlna', 145, 'paw', 4.8, 64, ['cs' => 369, 'sk' => 16],
                ['bila', 'cerna', 'seda', 'cervena', 'modra', 'zelena', 'purpurova', 'zluta'],
                array_slice($adult, 0, 6), [], ['tsp', 'tsl']),
            new Product('tricko-anglicka-vlajka', 'Tričko s anglickou vlajkou', 'Tričko s anglickou vlajkou',
                'pánské', 'pánske',
                'Potisk vlajky Spojeného království přes celou hruď.',
                'Potlač vlajky Spojeného kráľovstva.',
                'vtipna-tricka', 'Pánské', '100% bavlna', 145, 'flag', 4.4, 9, ['cs' => 329, 'sk' => 14],
                ['bila', 'cerna', 'seda', 'modra', 'cervena'],
                array_slice($adult, 0, 6), [], ['tsp']),
            new Product('fotbalovy-dres-domaci', 'Fotbalový dres – domácí sada', 'Futbalový dres – domáca sada',
                's potiskem jména a čísla', 's potlačou mena a čísla',
                'Funkční dres z prodyšného materiálu s možností potisku jména a čísla. Sleva při odběru celé sady.',
                'Funkčný dres z priedušného materiálu.',
                'fotbalove-dresy', 'Unisex', '100% polyester, prodyšný', 130, 'number', 4.5, 12, ['cs' => 590, 'sk' => 24],
                ['modra', 'cervena', 'zelena', 'cerna', 'bila', 'zluta'],
                $adult, [], ['cd']),
        ];

        $legalNote = '<p>Provozovatelem e-shopu je David Skupa, IČ 700 77 461, se sídlem Volavkova 1741/1, 162 00 Praha 6, neplátce DPH. Dozorovým orgánem je Česká obchodní inspekce. Spotřebitel má právo odstoupit od smlouvy do 14 dnů bez udání důvodu.</p>';
        $this->pages = [
            new Page('kontakty', 'Kontakty', 'Kontakty',
                '<p>Rádi poradíme s výběrem motivu, velikostí i s objednávkou pro firmy a týmy. Osobní odběr je možný v naší vzorkovně v Praze 9, U Elektry 650.</p>'.$legalNote,
                '<p>Radi poradíme s výberom motívu aj veľkosti.</p>'.$legalNote),
            new Page('obchodni-podminky', 'Obchodní podmínky', 'Obchodné podmienky',
                '<p>Tyto obchodní podmínky upravují práva a povinnosti mezi prodávajícím (David Skupa, IČ 700 77 461) a kupujícím. Trička jsou vyráběna na zakázku digitálním tiskem.</p>'.$legalNote,
                '<p>Tieto obchodné podmienky upravujú práva a povinnosti.</p>'.$legalNote),
            new Page('jak-nakupovat', 'Jak nakupovat', 'Ako nakupovať',
                '<p>Vyberte motiv, barvu a velikost, přidejte do košíku a projděte pokladnou. Tričko tiskneme na zakázku a expedujeme do 2 pracovních dnů.</p>',
                '<p>Vyberte motív, farbu a veľkosť.</p>'),
        ];
    }

    public function forStore(string $storeCode): SampleView
    {
        $inStore = static fn (array $codes) => [] === $codes || \in_array($storeCode, $codes, true);
        $cats = array_values(array_filter($this->categories, static fn (Category $c) => $inStore($c->storeCodes)));
        $prods = array_values(array_filter($this->products, static fn (Product $p) => $inStore($p->storeCodes)));

        return new SampleView($cats, $prods, $this->pages, $this->colors);
    }

    public function color(string $key): Color
    {
        return $this->colors[$key] ?? reset($this->colors);
    }

    public function findProduct(string $slug): ?Product
    {
        foreach ($this->products as $p) {
            if ($p->slug === $slug) {
                return $p;
            }
        }

        return null;
    }

    public function findCategory(string $slug): ?Category
    {
        foreach ($this->categories as $c) {
            if ($c->slug === $slug) {
                return $c;
            }
            foreach ($c->children as $k) {
                if ($k->slug === $slug) {
                    return $k;
                }
            }
        }

        return null;
    }

    public function findPage(string $slug): ?Page
    {
        foreach ($this->pages as $p) {
            if ($p->slug === $slug) {
                return $p;
            }
        }

        return null;
    }
}
