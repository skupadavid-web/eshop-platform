<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Catalog\Product;
use App\Entity\Order\Order;
use App\Entity\Shop\Store;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'stores' => $em->getRepository(Store::class)->count([]),
                'products' => $em->getRepository(Product::class)->count([]),
                'orders' => $em->getRepository(Order::class)->count([]),
            ],
        ]);
    }
}
