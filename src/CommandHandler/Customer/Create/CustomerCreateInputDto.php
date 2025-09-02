<?php

namespace App\CommandHandler\Customer\Create;

use Symfony\Component\Validator\Constraints as Assert;

class CustomerCreateInputDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 64)]
    private string $customerName;
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $customerEmail;
    #[Assert\NotBlank]
    private string $password;

    #[Assert\Uuid]
    private ?string $referralCode = null;
    #[Assert\Uuid]
    private ?string $invitedByUuid = null;

    public function __construct(
        string  $customerName,
        string  $customerEmail,
        string  $password,
        ?string $referralCode,
        ?string $invitedByUuid
    )
    {
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->password = $password;
        $this->referralCode = $referralCode;
        $this->invitedByUuid = $invitedByUuid;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }
    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    public function setReferralCode(?string $referralCode): void
    {
        $this->referralCode = $referralCode;
    }

    public function getInvitedByUuid(): ?string
    {
        return $this->invitedByUuid;
    }

    public function setInvitedByUuid(?string $invitedByUuid): void
    {
        $this->invitedByUuid = $invitedByUuid;
    }

}

