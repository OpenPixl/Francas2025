<?php

namespace App\Entity\Admin;

use App\Repository\Admin\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $author;

    #[ORM\Column(type: 'string', length: 255)]
    private $subject;

    #[ORM\Column(type: 'text', nullable: true)]
    private $content;

    #[ORM\Column(type: 'string', length: 255)]
    private $follow;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isRead;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;



    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'messages')]
    private Collection $recipient;

    public function __construct()
    {
        $this->recipient = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(?bool $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getFollow(): ?string
    {
        return $this->follow;
    }

    public function setFollow(string $follow): self
    {
        $this->follow = $follow;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getRecipient(): Collection
    {
        return $this->recipient;
    }

    public function addRecipient(User $recipient): static
    {
        if (!$this->recipient->contains($recipient)) {
            $this->recipient->add($recipient);
        }

        return $this;
    }

    public function removeRecipient(User $recipient): static
    {
        $this->recipient->removeElement($recipient);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedAt(): static
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }
}
