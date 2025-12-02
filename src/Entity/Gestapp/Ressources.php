<?php

namespace App\Entity\Gestapp;

use App\Entity\Admin\College;
use App\Entity\Admin\User;
use App\Repository\Webapp\RessourcesRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable()
 */
#[ORM\Entity(repositoryClass: RessourcesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ressources
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 100)]
    private $name;

    #[ORM\Column(type: 'string', length: 100)]
    private $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private $content;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private $imageName;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private $imageSize;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $doc;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isTitleShow;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isShowIntro;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isShowReadMore;

    #[ORM\Column(type: 'text', length: 255, nullable: true)]
    private $Linkmedia;

    #[ORM\ManyToOne(inversedBy: 'ressources')]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'ressources')]
    private ?College $college = null;

    #[ORM\ManyToOne(inversedBy: 'ressources')]
    private ?RessourceCat $category = null;

    #[ORM\ManyToOne(inversedBy: 'ressources')]
    private ?Support $support = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

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

    public function setDoc($doc)
    {
        $this->doc = $doc;
    }

    public function getDoc()
    {
        return $this->doc;
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

    public function getIsShowIntro(): ?bool
    {
        return $this->isShowIntro;
    }

    public function setIsShowIntro(?bool $isShowIntro): self
    {
        $this->isShowIntro = $isShowIntro;

        return $this;
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

    public function getLinkmedia(): ?string
    {
        return $this->Linkmedia;
    }

    public function setLinkmedia(?string $Linkmedia): self
    {
        $this->Linkmedia = $Linkmedia;

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

    public function getCategory(): ?RessourceCat
    {
        return $this->category;
    }

    public function setCategory(?RessourceCat $category): static
    {
        $this->category = $category;

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
}
