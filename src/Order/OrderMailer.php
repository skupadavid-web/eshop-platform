<?php

declare(strict_types=1);

namespace App\Order;

use App\Catalog\LegalEntity;
use App\Entity\Order\Order;
use App\Store\Store;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly PaymentInstructions $payments,
        private readonly LegalEntity $legal,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendConfirmation(Order $order, Store $store): void
    {
        if (null === $order->email) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($store->email, $store->name))
            ->to($order->email)
            ->subject(sprintf('Potvrzení objednávky %s — %s', $order->code, $store->name))
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->context([
                'order' => $order,
                'store' => $store,
                'legal' => $this->legal,
                'payment' => $this->payments->forOrder($order),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('order confirmation e-mail failed', ['order' => $order->code, 'exception' => $e]);
        }
    }
}
