<?php

declare(strict_types=1);

namespace App\Entity\System;

use App\Entity\Shop\Store;
use Doctrine\ORM\Mapping as ORM;

/** Scopes an admin user to a subset of stores (empty = all stores). */
#[ORM\Entity]
#[ORM\Table(name: 'admin_user_stores')]
#[ORM\UniqueConstraint(name: 'uniq_user_store', columns: ['user_id', 'store_id'])]
class AdminUserStore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storeScopes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public AdminUser $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Store $store;

    public function __construct(AdminUser $user, Store $store)
    {
        $this->user = $user;
        $this->store = $store;
    }
}
