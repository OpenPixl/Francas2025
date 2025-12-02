<?php

namespace App\Entity\Admin;

use App\Entity\Gestapp\Ressources;
use App\Entity\Webapp\Article;
use App\Entity\Webapp\Section;
use App\Repository\Admin\CollegeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollegeRepository::class)]
class College
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(inversedBy: 'colleges')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $address;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $complement;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    private $zipcode;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $city;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $collegeEmail;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $groupEmail;

    #[ORM\Column(type: 'string', length: 14, nullable: true)]
    private $collegePhone;

    #[ORM\Column(type: 'string', length: 14, nullable: true)]
    private $groupPhone;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $animateur;

    #[ORM\Column(type: 'text', nullable: true)]
    private $GroupDescription;

    #[ORM\Column(type: 'string', nullable: true)]
    private $logoName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $logoSize;

    #[ORM\Column(type: 'string', nullable: true)]
    private $headerName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $headerSize;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $workMeeting;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isActive;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'college')]
    private Collection $articles;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'singleCollege')]
    private Collection $sections;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\ManyToMany(targetEntity: Section::class, inversedBy: 'colleges')]
    private Collection $section;

    /**
     * @var Collection<int, Ressources>
     */
    #[ORM\OneToMany(targetEntity: Ressources::class, mappedBy: 'college')]
    private Collection $ressources;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->sections = new ArrayCollection();
        $this->section = new ArrayCollection();
        $this->ressources = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getComplement(): ?string
    {
        return $this->complement;
    }

    public function setComplement(?string $complement): self
    {
        $this->complement = $complement;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): self
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCollegeEmail(): ?string
    {
        return $this->collegeEmail;
    }

    public function setCollegeEmail(?string $collegeEmail): self
    {
        $this->collegeEmail = $collegeEmail;

        return $this;
    }

    public function getGroupEmail(): ?string
    {
        return $this->groupEmail;
    }

    public function setGroupEmail(?string $groupEmail): self
    {
        $this->groupEmail = $groupEmail;

        return $this;
    }

    public function getCollegePhone(): ?string
    {
        return $this->collegePhone;
    }

    public function setCollegePhone(?string $collegePhone): self
    {
        $this->collegePhone = $collegePhone;

        return $this;
    }

    public function getGroupPhone(): ?string
    {
        return $this->groupPhone;
    }

    public function setGroupPhone(?string $groupPhone): self
    {
        $this->groupPhone = $groupPhone;

        return $this;
    }

    public function getAnimateur(): ?string
    {
        return $this->animateur;
    }

    public function setAnimateur(?string $animateur): self
    {
        $this->animateur = $animateur;

        return $this;
    }

    public function getGroupDescription(): ?string
    {
        return $this->GroupDescription;
    }

    public function setGroupDescription(?string $GroupDescription): self
    {
        $this->GroupDescription = $GroupDescription;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function setLogoName(?string $logoName): void
    {
        $this->logoName = $logoName;
    }

    public function getLogoName(): ?string
    {
        return $this->logoName;
    }

    public function setLogoSize(?int $logoSize): void
    {
        $this->logoSize = $logoSize;
    }

    public function getLogoSize(): ?int
    {
        return $this->logoSize;
    }

    public function setHeaderName(?string $headerName): void
    {
        $this->headerName = $headerName;
    }

    public function getHeaderName(): ?string
    {
        return $this->headerName;
    }

    public function setHeaderSize(?int $headerSize): void
    {
        $this->headerSize = $headerSize;
    }

    public function getHeaderSize(): ?int
    {
        return $this->headerSize;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getWorkMeeting(): ?string
    {
        return $this->workMeeting;
    }

    public function setWorkMeeting(?string $workMeeting): self
    {
        $this->workMeeting = $workMeeting;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setCollege($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getCollege() === $this) {
                $article->setCollege(null);
            }
        }

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
            $section->setSingleCollege($this);
        }

        return $this;
    }

    public function removeSection(Section $section): static
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getSingleCollege() === $this) {
                $section->setSingleCollege(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Section>
     */
    public function getSection(): Collection
    {
        return $this->section;
    }

    /**
     * @return Collection<int, Ressources>
     */
    public function getRessources(): Collection
    {
        return $this->ressources;
    }

    public function addRessource(Ressources $ressource): static
    {
        if (!$this->ressources->contains($ressource)) {
            $this->ressources->add($ressource);
            $ressource->setCollege($this);
        }

        return $this;
    }

    public function removeRessource(Ressources $ressource): static
    {
        if ($this->ressources->removeElement($ressource)) {
            // set the owning side to null (unless already changed)
            if ($ressource->getCollege() === $this) {
                $ressource->setCollege(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
