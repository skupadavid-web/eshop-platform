<?php

declare(strict_types=1);

namespace App\Entity\System;

use App\Entity\Common\TimestampsTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'media')]
#[ORM\Index(name: 'idx_media_hash', columns: ['hash'])]
#[ORM\HasLifecycleCallbacks]
class Media
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 500)]
    public string $path;

    #[ORM\Column(length: 100)]
    public string $mime = '';

    #[ORM\Column]
    public int $size = 0;

    #[ORM\Column(length: 64, nullable: true)]
    public ?string $hash = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $alt = null;

    #[ORM\Column(nullable: true)]
    public ?int $width = null;

    #[ORM\Column(nullable: true)]
    public ?int $height = null;

    public function __construct(string $path)
    {
        $this->path = $path;
    }
}
