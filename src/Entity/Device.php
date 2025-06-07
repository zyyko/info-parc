<?php

namespace App\Entity;

use App\Repository\DeviceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceRepository::class)]
class Device
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $serial_number = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $fabrication_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $renewal_date = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $created_at = null;

    #[ORM\Column]
    private ?\DateTime $updated_at = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $device_image = null;

    #[ORM\Column(length: 255)]
    private ?string $device_name = null;

    public function __construct()
    {
        $this->created_at = new \DateTime(); // Initialize in constructor
        $this->updated_at = new \DateTime();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDeviceName(): ?string
    {
        return $this->device_name;
    }

    public function setDeviceName(string $device_name): static
    {
        $this->device_name = $device_name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serial_number;
    }

    public function setSerialNumber(string $serial_number): static
    {
        $this->serial_number = $serial_number;

        return $this;
    }

    public function getFabricationDate(): ?\DateTime
    {
        return $this->fabrication_date;
    }

    public function setFabricationDate(\DateTime $fabrication_date): static
    {
        $this->fabrication_date = $fabrication_date;

        return $this;
    }

    public function getRenewalDate(): ?\DateTime
    {
        return $this->renewal_date;
    }

    public function setRenewalDate(\DateTime $renewal_date): static
    {
        $this->renewal_date = $renewal_date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }


    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getDeviceImage(): ?string
    {
        return $this->device_image;
    }

    public function setDeviceImage(?string $device_image): static
    {
        $this->device_image = $device_image;

        return $this;
    }
}
