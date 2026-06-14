<?php

namespace App\Entity;

use App\Repository\CompanySettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanySettingRepository::class)]
#[ORM\Table(name: 'company_setting')]
#[ORM\UniqueConstraint(name: 'uniq_company_setting_code', fields: ['code'])]
class CompanySetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $code = 'default';

    #[ORM\Column(length: 255)]
    private string $companyName = 'Hopic';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legalName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailFrom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailFromName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $replyToEmail = null;

    #[ORM\Column(type: Types::JSON)]
    private array $notificationEmails = [];

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address2 = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: 'boolean')]
    private bool $sendOrderEmailToAdmin = true;

    #[ORM\Column(type: 'boolean')]
    private bool $sendOrderEmailToCustomer = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->code = 'default';
        $this->companyName = 'Hopic';
        $this->notificationEmails = [];
        $this->sendOrderEmailToAdmin = true;
        $this->sendOrderEmailToCustomer = true;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = trim($code) !== '' ? trim($code) : 'default';
        return $this;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = trim($companyName);
        $this->touch();
        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(?string $legalName): static
    {
        $this->legalName = $legalName ? trim($legalName) : null;
        $this->touch();
        return $this;
    }

    public function getEmailFrom(): ?string
    {
        return $this->emailFrom;
    }

    public function setEmailFrom(?string $emailFrom): static
    {
        $this->emailFrom = $emailFrom ? trim($emailFrom) : null;
        $this->touch();
        return $this;
    }

    public function getEmailFromName(): ?string
    {
        return $this->emailFromName;
    }

    public function setEmailFromName(?string $emailFromName): static
    {
        $this->emailFromName = $emailFromName ? trim($emailFromName) : null;
        $this->touch();
        return $this;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function setReplyToEmail(?string $replyToEmail): static
    {
        $this->replyToEmail = $replyToEmail ? trim($replyToEmail) : null;
        $this->touch();
        return $this;
    }

    public function getNotificationEmails(): array
    {
        return $this->notificationEmails;
    }

    public function setNotificationEmails(array $notificationEmails): static
    {
        $clean = [];

        foreach ($notificationEmails as $email) {
            $email = trim((string) $email);
            if ($email !== '') {
                $clean[] = $email;
            }
        }

        $this->notificationEmails = array_values(array_unique($clean));
        $this->touch();

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone ? trim($phone) : null;
        $this->touch();
        return $this;
    }

    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    public function setAddress1(?string $address1): static
    {
        $this->address1 = $address1 ? trim($address1) : null;
        $this->touch();
        return $this;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function setAddress2(?string $address2): static
    {
        $this->address2 = $address2 ? trim($address2) : null;
        $this->touch();
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city ? trim($city) : null;
        $this->touch();
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode ? trim($postalCode) : null;
        $this->touch();
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country ? trim($country) : null;
        $this->touch();
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website ? trim($website) : null;
        $this->touch();
        return $this;
    }

    public function isSendOrderEmailToAdmin(): bool
    {
        return $this->sendOrderEmailToAdmin;
    }

    public function setSendOrderEmailToAdmin(bool $sendOrderEmailToAdmin): static
    {
        $this->sendOrderEmailToAdmin = $sendOrderEmailToAdmin;
        $this->touch();
        return $this;
    }

    public function isSendOrderEmailToCustomer(): bool
    {
        return $this->sendOrderEmailToCustomer;
    }

    public function setSendOrderEmailToCustomer(bool $sendOrderEmailToCustomer): static
    {
        $this->sendOrderEmailToCustomer = $sendOrderEmailToCustomer;
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getNotificationEmailsAsText(): string
    {
        return implode("\n", $this->notificationEmails);
    }
}