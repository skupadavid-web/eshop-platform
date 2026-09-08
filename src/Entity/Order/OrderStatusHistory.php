<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Enum\OrderStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_status_history')]
class OrderStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Order $order;

    #[ORM\Column(enumType: OrderStatus::class)]
    public OrderStatus $status;

    #[ORM\Column]
    public \DateTimeImmutable $changedAt;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $changedBy = null;

    #[ORM\Column]
    public bool $mailSent = false;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $mailSentAt = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $promisedAt = null;   // datum slibu

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $note = null;

    public function __construct(Order $order, OrderStatus $status)
    {
        $this->order = $order;
        $this->status = $status;
        $this->changedAt = new \DateTimeImmutable();
    }
}
