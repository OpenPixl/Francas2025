<?php

namespace App\Twig;

use App\Entity\Webapp\Article;
use App\Repository\Webapp\ArticleRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expose au template la liste des dernières publications « audio » alimentant
 * le lecteur affiché sous la navbar (zone paramétrable par page).
 */
class AudioPlayerExtension extends AbstractExtension
{
    public function __construct(private readonly ArticleRepository $articleRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('latest_audio_articles', $this->latestAudioArticles(...)),
        ];
    }

    /**
     * @return Article[]
     */
    public function latestAudioArticles(int $max = 5): array
    {
        return $this->articleRepository->listLatestAudioArticles($max);
    }
}
