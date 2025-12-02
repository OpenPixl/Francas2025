<?php

namespace App\Entity\Webapp;

use App\Entity\Admin\User;
use App\Repository\Webapp\PageRepository;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: PageRepository::class)]
class Page implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[ORM\Column(type: 'string', length: 255)]
    private $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private $intro;

    #[ORM\Column(type: 'string', length: 100)]
    private $state;

    #[ORM\Column(type: 'array', nullable: true)]
    private $metaKeywords = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private $metaDescription;

    #[ORM\Column(type: 'array', nullable: true)]
    private $tags = [];

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $publishAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $publishEnd;

    #[ORM\Column(type: 'integer')]
    private $position;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isTitleShow;

    #[ORM\Column(type: 'boolean')]
    private $isMenu;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isIntroShow;

    #[ORM\Column(type: 'boolean')]
    private $isPublish = false;

    #[ORM\ManyToOne(inversedBy: 'pages')]
    private ?User $author = null;

    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'page')]
    private Collection $sections;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Permet d'initialiser le slug !
     * Utilisation de slugify pour transformer une chaine de caractères en slug
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function initializeSlug() {
        if(empty($this->slug)) {
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->title);
        }
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getIsMenu(): ?bool
    {
        return $this->isMenu;
    }

    public function setIsMenu(bool $isMenu): self
    {
        $this->isMenu = $isMenu;

        return $this;
    }

    public function getMetaKeywords(): ?array
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?array $metaKeywords): self
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getPublishAt(): ?\DateTimeInterface
    {
        return $this->publishAt;
    }

    public function setPublishAt(?\DateTimeInterface $publishAt): self
    {
        $this->publishAt = $publishAt;

        return $this;
    }

    public function getPublishEnd(): ?\DateTimeInterface
    {
        return $this->publishEnd;
    }

    public function setPublishEnd(?\DateTimeInterface $publishEnd): self
    {
        $this->publishEnd = $publishEnd;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    public function getIsTitleShow(): ?bool
    {
        return $this->isTitleShow;
    }

    public function setIsTitleShow(?bool $isTitleShow): self
    {
        $this->isTitleShow = $isTitleShow;

        return $this;
    }

    public function getIntro(): ?string
    {
        return $this->intro;
    }

    public function setIntro(?string $intro): self
    {
        $this->intro = $intro;

        return $this;
    }

    public function getIsIntroShow(): ?bool
    {
        return $this->isIntroShow;
    }

    public function setIsIntroShow(?bool $isIntroShow): self
    {
        $this->isIntroShow = $isIntroShow;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getIsPublish(): ?bool
    {
        return $this->isPublish;
    }

    public function setIsPublish(bool $isPublish): self
    {
        $this->isPublish = $isPublish;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection<int, Section>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(Section $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setPage($this);
        }

        return $this;
    }

    public function removeSection(Section $section): static
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getPage() === $this) {
                $section->setPage(null);
            }
        }

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
    public function setUpdatedAt(): self
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }
}
