<?php

declare(strict_types=1);

namespace App\Tests;

use App\Store\StoreRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SmokeTest extends WebTestCase
{
    #[DataProvider('storefrontRoutes')]
    public function testStorefrontPageRenders(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('header .subnav');
        self::assertSelectorExists('footer.sfoot');
    }

    /** @return iterable<string,array{0:string}> */
    public static function storefrontRoutes(): iterable
    {
        yield 'home' => ['/'];
        yield 'category' => ['/kategorie/vtipna-tricka'];
        yield 'product' => ['/produkt/tricko-s-kockou-damske'];
        yield 'cart' => ['/kosik'];
        yield 'checkout' => ['/pokladna'];
        yield 'content page' => ['/stranka/kontakty'];
    }

    #[DataProvider('hostCases')]
    public function testStoreResolvesByHost(string $host, ?string $expectedCode): void
    {
        $store = (new StoreRegistry())->findByHost($host);

        self::assertSame($expectedCode, $store?->code);
    }

    /** @return iterable<string,array{0:string,1:?string}> */
    public static function hostCases(): iterable
    {
        yield 'production cz' => ['trickaspotiskem.eu', 'tsp'];
        yield 'production sk with www' => ['www.trickaspotlacou.eu', 'tsl'];
        yield 'production dresy' => ['cooldresy.cz', 'cd'];
        yield 'ddev short host' => ['trickaspotiskem.ddev.site', 'tsp'];
        yield 'ddev host with port' => ['cooldresy.ddev.site:8443', 'cd'];
        yield 'unknown host' => ['eshop-platform.ddev.site', null];
    }
}
