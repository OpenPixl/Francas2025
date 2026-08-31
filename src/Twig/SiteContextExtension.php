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
        ];
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
