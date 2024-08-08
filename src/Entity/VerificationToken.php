<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\VerificationTokenRepository;
use DateTimeImmutable;
use App\Enum\ContactTypeEnum;

#[ORM\Entity(repositoryClass: VerificationTokenRepository::class)]
class VerificationToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $token;

    #[ORM\Column(type: 'string', length: 64)]
    private string $type;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    #[ORM\OneToOne(targetEntity: Contact::class, inversedBy: 'verificationToken')]
    #[ORM\JoinColumn(nullable: false)]
    private Contact $contact;

    public function __construct(Contact $contact, string $type, string $token, DateTimeImmutable $expiresAt)
    {
        $this->contact = $contact;
        $this->type = $type;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): void
    {
        $this->contact = $contact;
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable() > $this->expiresAt;
    }
}
