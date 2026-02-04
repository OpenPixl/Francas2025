<?php

namespace App\Entity\Webapp;

use App\Entity\Admin\College;
use App\Entity\Admin\User;
use App\Entity\Gestapp\Support;
use App\Entity\Gestapp\Theme;
use App\Repository\Webapp\ArticleRepository;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


/**
 * @Vich\Uploadable()
 */
#[ORM\Table(name: 'article')]
#[ORM\Index(columns: ['title'], flags: ['fulltext'])]
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Article
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
    private $content;

    #[ORM\Column(type: 'string', nullable: true)]
    private $imageName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $imageSize;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $doc;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'articles')]
    private $category;

    #[ORM\Column(type: 'text', nullable: true)]
    private $intro;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isCode;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isTitleShow;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isShowIntro;

    #[ORM\Column(type: 'boolean')]
    private $isArchived = false;

    #[ORM\Column(type: 'boolean')]
    private $isShowCreated = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isShowReadMore;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isShowCategory;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isShowSupport;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isShowTheme;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?College $college = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?Theme $theme = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?Support $support = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'oneArticle')]
    private Collection $sections;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\ManyToMany(targetEntity: Section::class, inversedBy: 'articles')]
    private Collection $section;

    #[ORM\Column(nullable: true)]
    private ?bool $isSupprImage = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSupprDoc = null;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
        $this->section = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTime('now');
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
        $this->updatedAt = new \DateTime('now');
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

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    // fonction pour le slug du titre de l'article
    /**
     * Permet d'initialiser le slug ! Utilisation de slugify pour transformer une chaine de caractères en slug
     *
     *
     * @return void
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function initializeSlug() {
        if(empty($this->slug)) {
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->title);
        }
    }

    public function getIsShowReadMore(): ?bool
    {
        return $this->isShowReadMore;
    }

    public function setIsShowReadMore(?bool $isShowReadMore): self
    {
        $this->isShowReadMore = $isShowReadMore;

        return $this;
    }

    public function getIsShowCategory(): ?bool
    {
        return $this->isShowCategory;
    }

    public function setIsShowCategory(bool $isShowCategory): self
    {
        $this->isShowCategory = $isShowCategory;

        return $this;
    }

    public function getIsShowSupport(): ?bool
    {
        return $this->isShowSupport;
    }

    public function setIsShowSupport(bool $isShowSupport): self
    {
        $this->isShowSupport = $isShowSupport;

        return $this;
    }

    public function getIsShowTheme(): ?bool
    {
        return $this->isShowTheme;
    }

    public function setIsShowTheme(bool $isShowTheme): self
    {
        $this->isShowTheme = $isShowTheme;

        return $this;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
    }

    public function getDoc()
    {
        return $this->doc;
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

    public function getIsCode(): ?bool
    {
        return $this->isCode;
    }

    public function setIsCode(bool $isCode): self
    {
        $this->isCode = $isCode;

        return $this;
    }

    public function getIsShowIntro(): ?bool
    {
        return $this->isShowIntro;
    }

    public function setIsShowIntro(?bool $isShowIntro): self
    {
        $this->isShowIntro = $isShowIntro;

        return $this;
    }

    public function getIsArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    public function getIsShowCreated(): ?bool
    {
        return $this->isShowCreated;
    }

    public function setIsShowCreated(bool $isShowCreated): self
    {
        $this->isShowCreated = $isShowCreated;

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

    public function getCollege(): ?College
    {
        return $this->college;
    }

    public function setCollege(?College $college): static
    {
        $this->college = $college;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getSupport(): ?Support
    {
        return $this->support;
    }

    public function setSupport(?Support $support): static
    {
        $this->support = $support;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
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
            $section->setoneArticle($this);
        }

        return $this;
    }

    public function removeSection(Section $section): static
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getoneArticle() === $this) {
                $section->setoneArticle(null);
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

    public function isSupprImage(): ?bool
    {
        return $this->isSupprImage;
    }

    public function setIsSupprImage(?bool $isSupprImage): static
    {
        $this->isSupprImage = $isSupprImage;

        return $this;
    }

    public function isSupprDoc(): ?bool
    {
        return $this->isSupprDoc;
    }

    public function setIsSupprDoc(?bool $isSupprDoc): static
    {
        $this->isSupprDoc = $isSupprDoc;

        return $this;
    }
}
