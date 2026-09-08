<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalog\Catalog;
use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(StoreContext $ctx, Catalog $catalog): Response
    {
        $store = $ctx->get();

        return $this->render('home/index.html.twig', [
            'bestsellers' => $catalog->newestProducts($store, 8),
        ]);
    }
}
