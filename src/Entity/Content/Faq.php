<?php

declare(strict_types=1);

namespace App\Entity\Content;

use App\Entity\Catalog\Product;
use App\Entity\Shop\Store;
use App\Entity\Taxonomy\Category;
use App\Enum\ContentSource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A question/answer that applies store-wide, to a category, or to one product
 * (old 8_shopdata_faq — but de-duplicated: the generic ones become one store-wide
 * row instead of one per variant). The product detail merges product > category >
 * store FAQs and emits them as FAQPage JSON-LD.
 */
#[ORM\Entity]
#[ORM\Table(name: 'faqs')]
#[ORM\Index(name: 'idx_faq_scope', columns: ['store_id', 'product_id', 'category_id'])]
class Faq
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?Product $product = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?Category $category = null;

    #[ORM\Column(length: 255)]
    public string $question;

    #[ORM\Column(type: 'text')]
    public string $answerHtml;

    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column(enumType: ContentSource::class, options: ['default' => 'template'])]
    public ContentSource $contentSource = ContentSource::Template;

    #[ORM\Column]
    public bool $locked = false;

    public function __construct(Store $store, string $question, string $answerHtml)
    {
        $this->store = $store;
        $this->question = $question;
        $this->answerHtml = $answerHtml;
    }
}
