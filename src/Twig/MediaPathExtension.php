<?php

namespace App\Twig;

use App\Entity\Admin\Etablissement;
use App\Entity\Webapp\Article;
use App\Service\MediaPathResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaPathExtension extends AbstractExtension
{
    public function __construct(private readonly MediaPathResolver $mediaPathResolver)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('etablissement_logo_url', $this->etablissementLogoUrl(...)),
            new TwigFunction('etablissement_header_url', $this->etablissementHeaderUrl(...)),
            new TwigFunction('article_image_url', $this->articleImageUrl(...)),
            new TwigFunction('article_doc_url', $this->articleDocUrl(...)),
            new TwigFunction('etablissement_image_url_prefix', $this->mediaPathResolver->etablissementImageUrlPrefixFor(...)),
            new TwigFunction('article_image_url_prefix', $this->mediaPathResolver->articleImageUrlPrefixFor(...)),
            new TwigFunction('article_doc_url_prefix', $this->mediaPathResolver->articleDocUrlPrefixFor(...)),
        ];
    }

    public function etablissementLogoUrl(?Etablissement $etablissement): ?string
    {
        return $this->mediaPathResolver->etablissementLogoUrl($etablissement);
    }

    public function etablissementHeaderUrl(?Etablissement $etablissement): ?string
    {
        return $this->mediaPathResolver->etablissementHeaderUrl($etablissement);
    }

    public function articleImageUrl(Article $article): ?string
    {
        return $this->mediaPathResolver->articleImageUrlFor($article);
    }

    public function articleDocUrl(Article $article): ?string
    {
        return $this->mediaPathResolver->articleDocUrlFor($article);
    }
}
