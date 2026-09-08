<?php

declare(strict_types=1);

namespace App\Controller;

use App\Store\StoreContext;
use App\Store\StoreRegistry;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(StoreContext $storeContext, StoreRegistry $registry, Connection $db): Response
    {
        try {
            $dbVersion = (string) $db->fetchOne('SELECT VERSION()');
        } catch (\Throwable) {
            $dbVersion = null;
        }

        return $this->render('home/index.html.twig', [
            'store' => $storeContext->get(),
            'stores' => $registry->all(),
            'phpVersion' => \PHP_VERSION,
            'symfonyVersion' => Kernel::VERSION,
            'dbVersion' => $dbVersion,
        ]);
    }
}
