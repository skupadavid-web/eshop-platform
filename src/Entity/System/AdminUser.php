<?php

declare(strict_types=1);

namespace App\Entity\System;

use App\Entity\Common\TimestampsTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'admin_users')]
#[ORM\HasLifecycleCallbacks]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 190, unique: true)]
    public string $email;

    #[ORM\Column]
    public string $password = '';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    public array $roles = ['ROLE_ADMIN'];

    #[ORM\Column(length: 120)]
    public string $name = '';

    #[ORM\Column(nullable: true)]
    public ?string $totpSecret = null;

    #[ORM\Column]
    public bool $active = true;

    /** @var Collection<int,AdminUserStore> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AdminUserStore::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $storeScopes;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->storeScopes = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}
