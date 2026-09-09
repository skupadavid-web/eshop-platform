<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Catalog\Product;
use App\Entity\Catalog\ProductTranslation;
use App\Entity\Catalog\ProductVariant;
use App\Entity\Catalog\VariantSize;
use App\Entity\Catalog\VariantTranslation;
use App\Entity\Content\Page;
use App\Entity\Content\PageTranslation;
use App\Entity\Content\UrlAlias;
use App\Entity\Pricing\Price;
use App\Entity\Shop\Store;
use App\Entity\Taxonomy\Category;
use App\Entity\Taxonomy\CategoryProduct;
use App\Entity\Taxonomy\CategoryTranslation;
use App\Enum\AliasTarget;
use App\Store\StoreRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class SmokeTest extends WebTestCase
{
    private const CATEGORY_PATH = '/testovaci-kategorie';
    private const VARIANT_PATH = '/testovaci-tricko-modre-detail-1-1';
    private const PAGE_PATH = '/testovaci-stranka';

    protected function setUp(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $tool = new SchemaTool($em);
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($meta);
        $tool->createSchema($meta);

        $app = new Application(self::$kernel);
        $app->setAutoExit(false);
        $app->run(new ArrayInput(['command' => 'app:seed']), new NullOutput());

        $em->clear();
        $this->seedCatalog($em);

        self::ensureKernelShutdown();
    }

    private function seedCatalog(EntityManagerInterface $em): void
    {
        $store = $em->getRepository(Store::class)->findOneBy(['code' => 'tsp']);
        self::assertInstanceOf(Store::class, $store);

        $cat = new Category($store);
        $ct = new CategoryTranslation($cat, 'cs');
        $ct->name = 'Testovací kategorie';
        $ct->slug = ltrim(self::CATEGORY_PATH, '/');
        $cat->translations->add($ct);
        $em->persist($cat);

        $product = new Product();
        $product->published = true;
        $pt = new ProductTranslation($product, 'cs');
        $pt->name = 'Testovací tričko';
        $product->translations->add($pt);

        $variant = new ProductVariant($product);
        $variant->color = 'modrá';
        $variant->colorHex = '#2456c9';
        $vt = new VariantTranslation($variant, 'cs');
        $vt->name = 'modrá';
        $vt->slug = ltrim(self::VARIANT_PATH, '/');
        $variant->translations->add($vt);
        $variant->sizes->add(new VariantSize($variant, 'M'));
        $product->variants->add($variant);
        $em->persist($product);

        $price = new Price($store, $product);
        $price->price = 349;
        $price->currency = 'CZK';
        $em->persist($price);

        $em->persist(new CategoryProduct($cat, $product));

        $page = new Page($store);
        $pgt = new PageTranslation($page, 'cs');
        $pgt->title = 'Testovací stránka';
        $pgt->slug = ltrim(self::PAGE_PATH, '/');
        $pgt->bodyHtml = '<p>Obsah.</p>';
        $page->translations->add($pgt);
        $em->persist($page);

        $em->flush();

        $em->persist(new UrlAlias($store, 'cs', self::CATEGORY_PATH, AliasTarget::Category, (int) $cat->id));
        $em->persist(new UrlAlias($store, 'cs', self::VARIANT_PATH, AliasTarget::Variant, (int) $variant->id));
        $em->persist(new UrlAlias($store, 'cs', self::PAGE_PATH, AliasTarget::Page, (int) $page->id));
        $em->flush();
    }

    #[DataProvider('storefrontRoutes')]
    public function testStorefrontPageRenders(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path, server: ['HTTP_HOST' => 'trickaspotiskem.ddev.site']);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('footer.sfoot');
    }

    /** @return iterable<string,array{0:string}> */
    public static function storefrontRoutes(): iterable
    {
        yield 'home' => ['/'];
        yield 'category' => [self::CATEGORY_PATH];
        yield 'product variant' => [self::VARIANT_PATH];
        yield 'content page' => [self::PAGE_PATH];
        yield 'cart (empty)' => ['/kosik'];
        yield 'search' => ['/hledani?q=testovaci'];
    }

    public function testEmptyCheckoutRedirectsToCart(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pokladna', server: ['HTTP_HOST' => 'trickaspotiskem.ddev.site']);

        self::assertResponseRedirects('/kosik');
    }

    public function testAddToCartThenPlaceOrder(): void
    {
        $client = static::createClient();
        $host = ['HTTP_HOST' => 'trickaspotiskem.ddev.site'];

        // add the fixture variant-size to the cart
        $crawler = $client->request('GET', self::VARIANT_PATH, server: $host);
        $sizeId = (int) $crawler->filter('input[name="variant_size_id"]')->attr('value');
        self::assertGreaterThan(0, $sizeId);
        $addForm = $crawler->filter('form.buy-form')->form();
        $addForm['variant_size_id'] = (string) $sizeId;
        $addForm['qty'] = '2';
        $client->submit($addForm);
        self::assertResponseRedirects(self::VARIANT_PATH);  // stays on the product page
        $client->followRedirect();
        self::assertSelectorExists('.cart-toast');

        $crawler = $client->request('GET', '/kosik', server: $host);
        self::assertSelectorTextContains('.cart-table', 'Testovací tričko');

        // checkout
        $crawler = $client->request('GET', '/pokladna', server: $host);
        self::assertResponseIsSuccessful();
        $client->submit($crawler->filter('form.checkout-form')->form([
            'email' => 'kupujici@example.test',
            'firstName' => 'Jan',
            'lastName' => 'Novák',
            'phone' => '+420605111222',
            'street' => 'Květná 4',
            'city' => 'Praha',
            'zip' => '13000',
            'country' => 'CZ',
            'shippingMethod' => 'pickup',
            'paymentMethod' => 'transfer',
            'agreeTerms' => '1',
        ]));
        self::assertResponseRedirects('/hotovo');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Děkujeme za objednávku');

        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $order = $em->getRepository(\App\Entity\Order\Order::class)->findOneBy(['email' => 'kupujici@example.test']);
        self::assertNotNull($order);
        self::assertSame(698, $order->itemsTotal);         // 2 × 349
        self::assertSame(0, $order->shippingTotal);        // pickup is free
        self::assertSame(698, $order->grandTotal);
        self::assertCount(1, $order->items);
        self::assertStringStartsWith('TSP-', $order->code);
    }

    public function testUnknownPathIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tato-adresa-neexistuje-vubec', server: ['HTTP_HOST' => 'trickaspotiskem.ddev.site']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testCategoryMenuAndProductAppear(): void
    {
        $client = static::createClient();
        $client->request('GET', self::CATEGORY_PATH, server: ['HTTP_HOST' => 'trickaspotiskem.ddev.site']);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Testovací kategorie');
        self::assertSelectorExists('.catgrid .pcard');
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
