<?php

declare(strict_types=1);

namespace App\Checkout;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutData
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 190)]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    public ?string $firstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    #[Assert\Length(max: 120)]
    public ?string $company = null;

    #[Assert\Length(max: 20)]
    public ?string $companyId = null;   // IČO

    #[Assert\Length(max: 20)]
    public ?string $vatId = null;       // DIČ

    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    public ?string $street = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    public ?string $city = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public ?string $zip = null;

    #[Assert\NotBlank]
    #[Assert\Country]
    public string $country = 'CZ';

    public bool $shipToDifferent = false;

    #[Assert\Length(max: 120)]
    public ?string $shipName = null;

    #[Assert\Length(max: 200)]
    public ?string $shipStreet = null;

    #[Assert\Length(max: 120)]
    public ?string $shipCity = null;

    #[Assert\Length(max: 20)]
    public ?string $shipZip = null;

    #[Assert\Country]
    public ?string $shipCountry = 'CZ';

    #[Assert\NotBlank(message: 'checkout.error.shipping')]
    public ?string $shippingMethod = null;

    #[Assert\Length(max: 255)]
    public ?string $pickupPoint = null;

    #[Assert\NotBlank(message: 'checkout.error.payment')]
    public ?string $paymentMethod = null;

    #[Assert\Length(max: 2000)]
    public ?string $note = null;

    #[Assert\IsTrue(message: 'checkout.error.terms')]
    public bool $agreeTerms = false;

    public bool $newsletter = false;
}
