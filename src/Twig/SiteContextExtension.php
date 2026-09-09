<?php

namespace App\Twig;

use App\Entity\Admin\Config;
use App\Service\SiteContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SiteContextExtension extends AbstractExtension
{
    public function __construct(private readonly SiteContext $siteContext)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_config', $this->siteConfig(...)),
            new TwigFunction('menu_pages', $this->menuPages(...)),
            new TwigFunction('site_logo_path', $this->siteLogoPath(...)),
        ];
    }

    /**
     * Chemin (à passer à `asset()`) du logo du site, ou null si absent.
     */
    public function siteLogoPath(): ?string
    {
        return $this->siteContext->getLogoWebPath();
    }

    public function siteConfig(): ?Config
    {
        return $this->siteContext->getConfig();
    }

    /**
     * @return \App\Entity\Webapp\Page[]
     */
    public function menuPages(): array
    {
        return $this->siteContext->getMenuPages();
    }
}
