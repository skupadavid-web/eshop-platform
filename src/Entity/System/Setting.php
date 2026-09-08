<?php

declare(strict_types=1);

namespace App\Entity\System;

use App\Entity\Shop\Store;
use Doctrine\ORM\Mapping as ORM;

/** Key-value config, global (store null) or per store. */
#[ORM\Entity]
#[ORM\Table(name: 'settings')]
#[ORM\UniqueConstraint(name: 'uniq_setting', columns: ['store_id', 'skey'])]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    public ?Store $store = null;

    #[ORM\Column(name: 'skey', length: 120)]
    public string $key;

    #[ORM\Column(type: 'json', nullable: true)]
    public mixed $value = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }
}
