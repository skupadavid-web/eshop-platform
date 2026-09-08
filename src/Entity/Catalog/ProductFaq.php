<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\ORM\Mapping as ORM;

/** FAQ entry attached to a product or a single variant (feeds FAQPage schema). */
#[ORM\Entity]
#[ORM\Table(name: 'product_faqs')]
class ProductFaq
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?ProductVariant $variant = null;

    #[ORM\Column(length: 8)]
    public string $locale = 'cs';

    #[ORM\Column(length: 255)]
    public string $question;

    #[ORM\Column(type: 'text')]
    public string $answer;

    #[ORM\Column]
    public int $position = 0;

    public function __construct(Product $product, string $question, string $answer)
    {
        $this->product = $product;
        $this->question = $question;
        $this->answer = $answer;
    }
}
