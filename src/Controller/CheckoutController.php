<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Cart & checkout shells. The real basket, order placement and payment land in E5;
 * for now these render an empty-state so the storefront is navigable end to end.
 */
final class CheckoutController extends AbstractController
{
    #[Route('/kosik', name: 'cart', priority: 10)]
    public function cart(): Response
    {
        return $this->render('checkout/cart.html.twig');
    }

    #[Route('/pokladna', name: 'checkout', priority: 10)]
    public function checkout(): Response
    {
        return $this->render('checkout/checkout.html.twig');
    }

    #[Route('/hotovo', name: 'checkout_done', priority: 10)]
    public function done(): Response
    {
        return $this->render('checkout/done.html.twig', ['code' => null]);
    }
}
