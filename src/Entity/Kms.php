<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Timestamps;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'kms')]
class Kms
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0; //https://github.com/doctrine/orm/issues/8452

    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private string $alias;

    /** @var string[] */
    #[ORM\Column(name: 'gateway_ids', type: 'json')]
    private array $gatewayIds = [];

    #[ORM\Column(name: 'last_health', type: 'boolean', nullable: true)]
    private ?bool $lastHealth = null;

    #[ORM\Column(name: 'check_date', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkDate = null;

    public function __construct(string $alias, array $gatewayIds)
    {
        $this->alias = $alias;
        $this->gatewayIds = $gatewayIds;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /** @return string[] */
    public function getGatewayIds(): array
    {
        return $this->gatewayIds;
    }

    /** @param string[] $gatewayIds */
    public function setGatewayIds(array $gatewayIds): self
    {
        $this->gatewayIds = array_values($gatewayIds);
        return $this;
    }

    public function getLastHealth(): ?bool
    {
        return $this->lastHealth;
    }

    public function setLastHealth(?bool $lastHealth): self
    {
        $this->lastHealth = $lastHealth;
        return $this;
    }

    public function getCheckDate(): ?\DateTimeImmutable
    {
        return $this->checkDate;
    }

    public function setCheckDate(?\DateTimeImmutable $checkDate): self
    {
        $this->checkDate = $checkDate;
        return $this;
    }
}
