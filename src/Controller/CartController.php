<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cart\CartService;
use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly StoreContext $ctx,
        private readonly CartService $cart,
    ) {
    }

    #[Route('/kosik', name: 'cart', priority: 10)]
    public function show(): Response
    {
        return $this->render('checkout/cart.html.twig', [
            'cart' => $this->cart->get($this->ctx->get()),
        ]);
    }

    #[Route('/kosik/pridat', name: 'cart_add', methods: ['POST'], priority: 10)]
    public function add(Request $request): Response
    {
        $store = $this->ctx->get();
        if (!$this->isCsrfTokenValid('cart', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $sizeId = $request->request->getInt('variant_size_id');
        $qty = max(1, min(99, $request->request->getInt('qty', 1)));
        if ($sizeId > 0) {
            $this->cart->add($store, $sizeId, $qty);
            $this->addFlash('cart', 'added');
        }

        return $this->redirectToRoute('cart');
    }

    #[Route('/kosik/upravit', name: 'cart_update', methods: ['POST'], priority: 10)]
    public function update(Request $request): Response
    {
        $store = $this->ctx->get();
        if (!$this->isCsrfTokenValid('cart', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (null !== ($removeId = $request->request->get('remove'))) {
            $this->cart->remove($store, (int) $removeId);

            return $this->redirectToRoute('cart');
        }

        /** @var array<int|string,int|string> $qtys */
        $qtys = (array) $request->request->all('qty');
        foreach ($qtys as $sizeId => $qty) {
            $this->cart->setQty($store, (int) $sizeId, (int) $qty);
        }

        return $this->redirectToRoute('cart');
    }
}
