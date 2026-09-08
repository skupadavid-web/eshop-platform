<?php

declare(strict_types=1);

namespace App\Controller;

use App\Sample\SampleData;
use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly StoreContext $ctx,
        private readonly SampleData $data,
    ) {
    }

    #[Route('/kosik', name: 'cart')]
    public function cart(): Response
    {
        $view = $this->data->forStore($this->ctx->get()->code);
        $items = [
            ['product' => $view->products[0], 'color' => 'zelena', 'size' => 'M', 'qty' => 1],
            ['product' => $view->products[1], 'color' => 'cerna', 'size' => 'L', 'qty' => 2],
        ];

        return $this->render('checkout/cart.html.twig', ['view' => $view, 'items' => $items]);
    }

    #[Route('/pokladna', name: 'checkout')]
    public function checkout(): Response
    {
        $view = $this->data->forStore($this->ctx->get()->code);

        return $this->render('checkout/checkout.html.twig', ['view' => $view]);
    }

    #[Route('/hotovo', name: 'checkout_done')]
    public function done(): Response
    {
        return $this->render('checkout/done.html.twig', ['code' => 'TSP-24218']);
    }
}
