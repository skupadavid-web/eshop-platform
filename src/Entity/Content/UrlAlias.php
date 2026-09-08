<?php

declare(strict_types=1);

namespace App\Entity\Content;

use App\Entity\Shop\Store;
use App\Enum\AliasTarget;
use Doctrine\ORM\Mapping as ORM;

/**
 * The routing table. Every public URL of every store resolves through here —
 * the backbone of keeping the old URLs alive after migration.
 */
#[ORM\Entity]
#[ORM\Table(name: 'url_aliases')]
#[ORM\UniqueConstraint(name: 'uniq_alias_path', columns: ['store_id', 'locale', 'path'])]
#[ORM\Index(name: 'idx_alias_target', columns: ['target_type', 'target_id'])]
class UrlAlias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\Column(length: 8)]
    public string $locale;

    #[ORM\Column(length: 500)]
    public string $path;

    #[ORM\Column(enumType: AliasTarget::class)]
    public AliasTarget $targetType;

    #[ORM\Column]
    public int $targetId;

    /** The one alias that is the canonical URL for this target. */
    #[ORM\Column]
    public bool $canonical = true;

    public function __construct(Store $store, string $locale, string $path, AliasTarget $targetType, int $targetId)
    {
        $this->store = $store;
        $this->locale = $locale;
        $this->path = $path;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
    }
}
