<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ContactTypeEnum;
use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0;

    #[ORM\Column(type: 'string', length: 64)]
    private string $contactTypeEnum;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $countryCode;

    #[ORM\Column(type: 'string', length: 512)]
    #[Assert\NotBlank]
    private ?string $value;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Beneficiary::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Beneficiary $beneficiary = null;

    #[ORM\OneToOne(targetEntity: VerificationToken::class, mappedBy: 'contact', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?VerificationToken $verificationToken;



    public function getId(): int
    {
        return $this->id;
    }

    public function getContactTypeEnum(): string
    {
        return $this->contactTypeEnum;
    }

    public function setContactTypeEnum(string $contactTypeEnum): self
    {
        if (!in_array($contactTypeEnum, ContactTypeEnum::getValues())) {
            throw new \InvalidArgumentException('Invalid contact type');
        }
        $this->contactTypeEnum = $contactTypeEnum;
        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }
    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }
    public function getValue(): ?string
    {
        return $this->value;
    }
    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }
    public function getIsVerified(): bool
    {
        return $this->isVerified;
    }
    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getBeneficiary(): ?Beneficiary
    {
        return $this->beneficiary;
    }

    public function setBeneficiary(Beneficiary $beneficiary): self
    {
        $this->beneficiary = $beneficiary;
        return $this;
    }

    public function setVerificationToken(VerificationToken $token): self
    {
        $this->verificationToken = $token;
        return $this;
    }

    public function getVerificationTokens(): ?VerificationToken
    {
        return $this->verificationToken;
    }
}
