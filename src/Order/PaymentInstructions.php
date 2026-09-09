<?php

declare(strict_types=1);

namespace App\Order;

use App\Entity\Order\Order;
use App\Entity\Shop\LegalEntity;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Bank-transfer payment details for an order, plus the Czech "QR platba" (SPD)
 * string and a rendered SVG data URI. Only meaningful for the 'transfer' method
 * (and 'card' while there is no gateway yet).
 */
final class PaymentInstructions
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function needsBankDetails(Order $order): bool
    {
        return \in_array($order->paymentMethodCode, ['transfer', 'card'], true);
    }

    /**
     * @return array{iban:string, account:string, amount:string, currency:string, vs:string, message:string, spd:string, qrSvg:string}|null
     */
    public function forOrder(Order $order): ?array
    {
        if (!$this->needsBankDetails($order)) {
            return null;
        }
        $legal = $this->em->getRepository(LegalEntity::class)->findOneBy([]);
        if (!$legal instanceof LegalEntity || null === $legal->iban) {
            return null;
        }

        $amount = number_format($order->grandTotal, 2, '.', '');
        $vs = preg_replace('/\D+/', '', $order->code) ?: '0';
        $vs = substr($vs, -10);
        $message = 'Objednavka '.$order->code;
        $spd = sprintf(
            'SPD*1.0*ACC:%s*AM:%s*CC:%s*X-VS:%s*MSG:%s',
            $legal->iban,
            $amount,
            $order->currency,
            $vs,
            $message,
        );

        return [
            'iban' => $legal->iban,
            'account' => (string) $legal->bankAccount,
            'amount' => $amount,
            'currency' => $order->currency,
            'vs' => $vs,
            'message' => $message,
            'spd' => $spd,
            'qrSvg' => $this->qr($spd),
        ];
    }

    private function qr(string $data): string
    {
        try {
            $result = (new Builder(
                writer: new SvgWriter(),
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 220,
                margin: 0,
            ))->build();

            return 'data:image/svg+xml;base64,'.base64_encode($result->getString());
        } catch (\Throwable) {
            return '';
        }
    }
}
