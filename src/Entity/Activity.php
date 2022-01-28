<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $name;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $location;

    #[ORM\Column(type: "datetime")]
    private ?DateTimeInterface $startAt;

    #[ORM\Column(type: "datetime")]
    private ?DateTimeInterface $endAt;

    #[ORM\Column(type: "integer")]
    private ?int $availableSeats;

    #[ORM\Column(type: "integer")]
    private ?int $occupiedSeats;

    public function getId(): ?int
    {
        return $this->id;
    }

    private function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getStartAt(): ?DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(DateTimeInterface $startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(DateTimeInterface $endAt): self
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getAvailableSeats(): ?int
    {
        return $this->availableSeats;
    }

    public function setAvailableSeats(int $availableSeats): self
    {
        $this->availableSeats = $availableSeats;
        return $this;
    }

    public function getOccupiedSeats(): ?int
    {
        return $this->occupiedSeats;
    }

    public function setOccupiedSeats(int $occupiedSeats): self
    {
        $this->occupiedSeats = $occupiedSeats;
        return $this;
    }
}
