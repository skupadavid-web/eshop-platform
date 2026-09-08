<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentGateway: string
{
    case Comgate = 'comgate';
    case GoPay = 'gopay';
    case BankTransfer = 'bank_transfer';
    case CashOnDelivery = 'cash_on_delivery';
    case CashOnPickup = 'cash_on_pickup';
}
