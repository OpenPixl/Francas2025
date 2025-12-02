<?php

namespace App\Entity\Webapp;

use App\Entity\Admin\College;
use App\Entity\Gestapp\RessourceCat;
use App\Repository\Webapp\SectionRepository;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: SectionRepository::class)]
class Section
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['sections_read', 'pages_read'])]
    private $id;

    #[ORM\Column(type: 'string', length: 100)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $slug;

    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    private $className;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $content;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $descriptif;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $fluid;

    #[ORM\Column(type: 'text', nullable: true)]
    private $intro;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $priority;

    #[ORM\Column(type: 'boolean')]
    private $isActiv;

    #[ORM\Column(type: 'boolean')]
    private $favorites = false;

    #[ORM\Column(type: 'integer')]
    private $position;

    #[ORM\Column(type: 'boolean')]
    private $isShowTitle = false;

    #[ORM\Column(type: 'boolean')]
    private $isShowdescription = false;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    private ?College $singleCollege = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    private ?Page $page = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    private ?RessourceCat $ressourcesCat = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    private ?Category $category = null;

    #[ORM\ManyToMany(targetEntity: College::class, mappedBy: 'section')]
    private Collection $colleges;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    private ?Article $oneArticle = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'section')]
    private Collection $articles;


    public function __construct()
    {
        $this->colleges = new ArrayCollection();
        $this->articles = new ArrayCollection();
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

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): self
    {
        $this->className = $className;

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

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getDescriptif(): ?string
    {
        return $this->descriptif;
    }

    public function setDescriptif(?string $descriptif): self
    {
        $this->descriptif = $descriptif;

        return $this;
    }

    public function getFluid(): ?bool
    {
        return $this->fluid;
    }

    public function setFluid(bool $fluid): self
    {
        $this->fluid = $fluid;

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

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getIsActiv(): ?bool
    {
        return $this->isActiv;
    }

    public function setIsActiv(bool $isActiv): self
    {
        $this->isActiv = $isActiv;

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
            $this->slug = $slugify->slugify($this->name);
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

    public function getFavorites(): ?bool
    {
        return $this->favorites;
    }

    public function setFavorites(bool $favorites): self
    {
        $this->favorites = $favorites;

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

    public function getIsShowTitle(): ?bool
    {
        return $this->isShowTitle;
    }

    public function setIsShowTitle(bool $isShowTitle): self
    {
        $this->isShowTitle = $isShowTitle;

        return $this;
    }

    public function getIsShowdescription(): ?bool
    {
        return $this->isShowdescription;
    }

    public function setIsShowdescription(bool $isShowdescription): self
    {
        $this->isShowdescription = $isShowdescription;

        return $this;
    }

    public function getSingleCollege(): ?College
    {
        return $this->singleCollege;
    }

    public function setSingleCollege(?College $singleCollege): static
    {
        $this->singleCollege = $singleCollege;

        return $this;
    }

    /**
     * @return Collection<int, College>
     */
    public function getColleges(): Collection
    {
        return $this->colleges;
    }

    public function addCollege(College $college): static
    {
        if (!$this->colleges->contains($college)) {
            $this->colleges->add($college);
            $college->addSection($this);
        }

        return $this;
    }

    public function removeCollege(College $college): static
    {
        if ($this->colleges->removeElement($college)) {
            $college->removeSection($this);
        }

        return $this;
    }

    public function getRessourcesCat(): ?RessourceCat
    {
        return $this->ressourcesCat;
    }

    public function setRessourcesCat(?RessourceCat $ressourcesCat): static
    {
        $this->ressourcesCat = $ressourcesCat;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

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

    public function getOneArticle(): ?Article
    {
        return $this->oneArticle;
    }

    public function setOneArticle(?Article $oneArticle): static
    {
        $this->oneArticle = $oneArticle;

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
            $article->addSection($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            $article->removeSection($this);
        }

        return $this;
    }
}
