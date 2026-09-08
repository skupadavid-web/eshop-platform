<?php

declare(strict_types=1);

namespace App\Controller;

use App\Sample\SampleData;
use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(StoreContext $ctx, SampleData $data): Response
    {
        $view = $data->forStore($ctx->get()->code);

        return $this->render('home/index.html.twig', [
            'view' => $view,
            'bestsellers' => \array_slice($view->products, 0, 4),
        ]);
    }
}
