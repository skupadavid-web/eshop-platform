<?php

declare(strict_types=1);

namespace App\Tests;

use App\Store\StoreRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SmokeTest extends WebTestCase
{
    public function testHomepageRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Eshop platforma');
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
