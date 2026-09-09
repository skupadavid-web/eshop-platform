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

        // stay where the customer was so they can keep browsing
        return $this->redirect($this->safeBackUrl($request));
    }

    private function safeBackUrl(Request $request): string
    {
        foreach ([$request->request->get('redirect_to'), $request->headers->get('referer')] as $candidate) {
            if (!\is_string($candidate) || '' === $candidate) {
                continue;
            }
            $path = (string) (parse_url($candidate, \PHP_URL_PATH) ?: '');
            if (str_starts_with($path, '/') && !str_starts_with($path, '//')) {
                $query = (string) (parse_url($candidate, \PHP_URL_QUERY) ?: '');

                return $path.('' !== $query ? '?'.$query : '');
            }
        }

        return $this->generateUrl('home');
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
