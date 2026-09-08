<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Store\StoreContext;
use App\Store\StoreRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Picks the active storefront by request host and sets the request locale from it.
 * Unknown hosts (including the bare eshop-platform.ddev.site) fall back to the default.
 */
final readonly class StoreResolverSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StoreRegistry $registry,
        private StoreContext $context,
        private string $defaultStoreCode = 'tsp',
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $store = $this->registry->findByHost($request->getHost())
            ?? $this->registry->get($this->defaultStoreCode);

        $this->context->set($store);
        $request->setLocale($store->locale);
    }

    public static function getSubscribedEvents(): array
    {
        // Before the router (32) but after Symfony locale handling.
        return [KernelEvents::REQUEST => [['onKernelRequest', 20]]];
    }
}
