<?php

namespace App\Entity;

use App\Controller\NoAvailableSeatsLeftException;
use App\Controller\UserAlreadyHasSeatException;
use App\Repository\ActivityRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @Entity(repositoryClass=ActivityRepository::class)
 */
class Activity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"activity"})
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"activity"})
     */
    private ?string $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"activity"})
     */
    private ?string $location;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"activity"})
     */
    private ?DateTimeInterface $startAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"activity"})
     */
    private ?DateTimeInterface $endAt;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"activity"})
     */
    private ?int $availableSeats;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"activity"})
     */
    private ?int $occupiedSeats = 0;

    /**
     * @ORM\ManyToMany(targetEntity=User::class)
     * @Groups({"activity"})
     */
    private Collection $users;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class, inversedBy="activities")
     * @Groups({"activity"})
     */
    private Collection $categories;

    /**
     * @ORM\OneToOne(targetEntity=ActivityMedia::class)
     */
    private ?ActivityMedia $media;

    #[Pure]
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

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

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection|array
    {
        return $this->users;
    }

    /**
     * @param User $user
     * @return Activity
     * @throws NoAvailableSeatsLeftException
     * @throws UserAlreadyHasSeatException
     */
    public function joinUser(User $user): self
    {
        if ($this->users->contains($user)) {
            throw new UserAlreadyHasSeatException();
        }

        if (!$this->hasAvailableSeats()) {
            throw new NoAvailableSeatsLeftException();
        }

        $this->users->add($user);
        $this->setOccupiedSeats($this->getOccupiedSeats() + 1);
        return $this;
    }

    /**
     * @param User $user
     * @return Activity
     */
    public function leaveUser(User $user): self
    {
        $removed = $this->users->removeElement($user);

        if ($removed) {
            $this->setOccupiedSeats($this->getOccupiedSeats() - 1);
        }

        return $this;
    }

    #[Pure]
    public function hasAvailableSeats(): bool
    {
        return $this->getAvailableSeats() > $this->getOccupiedSeats();
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection|array
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);
        return $this;
    }

    public function clearCategories(): self
    {
        $this->categories->clear();
        return $this;
    }

    public function getMedia(): ?ActivityMedia
    {
        return $this->media;
    }

    public function setMedia(?ActivityMedia $media): self
    {
        $this->media = $media;

        return $this;
    }
}
