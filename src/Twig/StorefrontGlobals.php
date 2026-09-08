<?php

declare(strict_types=1);

namespace App\Twig;

use App\Catalog\LegalEntity;
use App\Sample\SampleData;
use App\Store\StoreContext;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Makes the active store, the seller identity and the (phase-1 sample) catalogue
 * slice available to every template as {{ store }}, {{ legal }} and {{ view }}.
 * Runs after StoreResolverSubscriber has populated the store context.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 8)]
final readonly class StorefrontGlobals
{
    public function __construct(
        private Environment $twig,
        private StoreContext $storeContext,
        private LegalEntity $legal,
        private SampleData $sample,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->storeContext->has()) {
            return;
        }

        $store = $this->storeContext->get();
        $this->twig->addGlobal('store', $store);
        $this->twig->addGlobal('legal', $this->legal);
        $this->twig->addGlobal('view', $this->sample->forStore($store->code));
    }
}
