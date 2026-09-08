<?php

declare(strict_types=1);

namespace App\Entity\Content;

use App\Entity\Shop\Store;
use Doctrine\ORM\Mapping as ORM;

/** Old URL -> new URL. Imported from .htaccess + generated when an alias changes. */
#[ORM\Entity]
#[ORM\Table(name: 'redirects')]
#[ORM\UniqueConstraint(name: 'uniq_redirect_source', columns: ['store_id', 'source_path'])]
class Redirect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    #[ORM\Column(length: 500)]
    public string $sourcePath;

    #[ORM\Column(length: 500)]
    public string $target;               // path or absolute URL

    #[ORM\Column(options: ['default' => 301])]
    public int $code = 301;

    #[ORM\Column]
    public int $hits = 0;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $lastHitAt = null;

    #[ORM\Column(length: 40, nullable: true)]
    public ?string $origin = null;       // 'htaccess' | 'alias-change' | 'manual'

    public function __construct(Store $store, string $sourcePath, string $target)
    {
        $this->store = $store;
        $this->sourcePath = $sourcePath;
        $this->target = $target;
    }
}
