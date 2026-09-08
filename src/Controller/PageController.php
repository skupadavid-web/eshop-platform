<?php

declare(strict_types=1);

namespace App\Controller;

use App\Sample\SampleData;
use App\Store\StoreContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route('/stranka/{slug}', name: 'page', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug, StoreContext $ctx, SampleData $data): Response
    {
        $view = $data->forStore($ctx->get()->code);
        $page = $data->findPage($slug);
        if (null === $page) {
            throw $this->createNotFoundException();
        }

        return $this->render('page/content.html.twig', ['view' => $view, 'page' => $page]);
    }
}
