<?php

namespace App\Entity\Admin;

use App\Repository\Admin\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[ORM\Column(type: 'boolean')]
    private $isOffline;

    #[ORM\Column(type: 'string', nullable: true)]
    private $logoName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $logoSize;

    #[ORM\Column(type: 'string', nullable: true)]
    private $headerName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $headerSize;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isHeaderShow;

    #[ORM\Column(type: 'boolean')]
    private $isShowTitleSiteHome = false;

    #[ORM\Column(type: 'string', length: 255)]
    private $vignetteName;

    #[ORM\Column(type: 'string', length: 255)]
    private $vignetteSize;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isSupprVignette;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIsOffline(): ?bool
    {
        return $this->isOffline;
    }

    public function setIsOffline(bool $isOffline): self
    {
        $this->isOffline = $isOffline;

        return $this;
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

    public function getIsHeaderShow(): ?bool
    {
        return $this->isHeaderShow;
    }

    public function setIsHeaderShow(?bool $isHeaderShow): self
    {
        $this->isHeaderShow = $isHeaderShow;

        return $this;
    }

    public function getIsShowTitleSiteHome(): ?bool
    {
        return $this->isShowTitleSiteHome;
    }

    public function setIsShowTitleSiteHome(bool $isShowTitleSiteHome): self
    {
        $this->isShowTitleSiteHome = $isShowTitleSiteHome;

        return $this;
    }

    public function getVignetteName(): ?string
    {
        return $this->vignetteName;
    }

    public function setVignetteName(string $vignetteName): self
    {
        $this->vignetteName = $vignetteName;

        return $this;
    }

    public function getVignetteSize(): ?string
    {
        return $this->vignetteSize;
    }

    public function setVignetteSize(string $vignetteSize): self
    {
        $this->vignetteSize = $vignetteSize;

        return $this;
    }

    public function getIsSupprVignette(): ?bool
    {
        return $this->isSupprVignette;
    }

    public function setIsSupprVignette(?bool $isSupprVignette): self
    {
        $this->isSupprVignette = $isSupprVignette;

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
