<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cart\CartService;
use App\Checkout\CheckoutData;
use App\Checkout\CheckoutOptions;
use App\Entity\Order\Order;
use App\Order\OrderPlacer;
use App\Order\PaymentInstructions;
use App\Store\StoreContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly StoreContext $ctx,
        private readonly CartService $cart,
        private readonly CheckoutOptions $options,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/pokladna', name: 'checkout', methods: ['GET', 'POST'], priority: 10)]
    public function checkout(Request $request, OrderPlacer $placer): Response
    {
        $store = $this->ctx->get();
        $cart = $this->cart->get($store);
        if ($cart->isEmpty()) {
            return $this->redirectToRoute('cart');
        }

        $data = new CheckoutData();
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $this->fill($data, $request);
            foreach ($this->validator->validate($data) as $v) {
                $errors[$v->getPropertyPath()] = (string) $v->getMessage();
            }
            // pickup point required when the shipping method has pickup points
            $cod = $this->options->isCashOnDelivery($store, (string) $data->paymentMethod);
            $ship = null !== $data->shippingMethod ? $this->options->shippingOption($store, $data->shippingMethod, $cod) : null;
            if (null !== $ship && $ship->hasPickupPoints && '' === trim((string) $data->pickupPoint)) {
                $errors['pickupPoint'] = 'checkout.error.pickup';
            }

            if ([] === $errors) {
                try {
                    $order = $placer->place($store, $data);
                } catch (\DomainException) {
                    return $this->redirectToRoute('cart');
                }
                $request->getSession()->set('last_order', $order->code);

                return $this->redirectToRoute('checkout_done');
            }
        }

        $cod = $this->options->isCashOnDelivery($store, (string) $data->paymentMethod);

        return $this->render('checkout/checkout.html.twig', [
            'cart' => $cart,
            'data' => $data,
            'errors' => $errors,
            'shippingOptions' => $this->options->shipping($store, $cod),
            'paymentOptions' => $this->options->payment($store),
        ]);
    }

    #[Route('/hotovo', name: 'checkout_done', priority: 10)]
    public function done(Request $request, EntityManagerInterface $em, PaymentInstructions $payments): Response
    {
        $code = $request->getSession()->get('last_order');
        $order = \is_string($code) ? $em->getRepository(Order::class)->findOneBy(['code' => $code]) : null;

        if (!$order instanceof Order || $order->store->code !== $this->ctx->get()->code) {
            return $this->render('checkout/done.html.twig', ['order' => null, 'payment' => null]);
        }

        return $this->render('checkout/done.html.twig', [
            'order' => $order,
            'payment' => $payments->forOrder($order),
        ]);
    }

    private function fill(CheckoutData $d, Request $r): void
    {
        $p = $r->request;
        $d->email = trim((string) $p->get('email')) ?: null;
        $d->firstName = trim((string) $p->get('firstName')) ?: null;
        $d->lastName = trim((string) $p->get('lastName')) ?: null;
        $d->phone = trim((string) $p->get('phone')) ?: null;
        $d->company = trim((string) $p->get('company')) ?: null;
        $d->companyId = trim((string) $p->get('companyId')) ?: null;
        $d->vatId = trim((string) $p->get('vatId')) ?: null;
        $d->street = trim((string) $p->get('street')) ?: null;
        $d->city = trim((string) $p->get('city')) ?: null;
        $d->zip = trim((string) $p->get('zip')) ?: null;
        $d->country = trim((string) $p->get('country')) ?: 'CZ';
        $d->shipToDifferent = $p->getBoolean('shipToDifferent');
        $d->shipName = trim((string) $p->get('shipName')) ?: null;
        $d->shipStreet = trim((string) $p->get('shipStreet')) ?: null;
        $d->shipCity = trim((string) $p->get('shipCity')) ?: null;
        $d->shipZip = trim((string) $p->get('shipZip')) ?: null;
        $d->shipCountry = trim((string) $p->get('shipCountry')) ?: 'CZ';
        $d->shippingMethod = trim((string) $p->get('shippingMethod')) ?: null;
        $d->pickupPoint = trim((string) $p->get('pickupPoint')) ?: null;
        $d->paymentMethod = trim((string) $p->get('paymentMethod')) ?: null;
        $d->note = trim((string) $p->get('note')) ?: null;
        $d->agreeTerms = $p->getBoolean('agreeTerms');
        $d->newsletter = $p->getBoolean('newsletter');
    }
}
