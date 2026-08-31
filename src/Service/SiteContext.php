<?php

namespace App\Service;

use App\Entity\Admin\Config;
use App\Entity\Webapp\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Données transverses affichées sur (quasiment) toutes les pages : la
 * configuration du site et les pages de menu.
 *
 * But : supprimer les sous-requêtes `render(controller())` du layout
 * (`base.html.twig`) qui relançaient un cycle HttpKernel complet à chaque
 * page — et à chaque sous-requête de section. Ici, une seule requête SQL par
 * entrée et par requête HTTP, mémoïsée.
 *
 * `ResetInterface` : en mode worker FrankenPHP le service est partagé entre
 * les requêtes ; `reset()` (appelé automatiquement par `kernel.reset`) purge
 * le cache mémoire pour éviter de servir la config d'une requête précédente.
 */
class SiteContext implements ResetInterface
{
    private ?Config $config = null;
    private bool $configLoaded = false;

    /** @var Page[]|null */
    private ?array $menuPages = null;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getConfig(): ?Config
    {
        if (!$this->configLoaded) {
            $this->config = $this->em->getRepository(Config::class)->find(1);
            $this->configLoaded = true;
        }

        return $this->config;
    }

    /**
     * @return Page[]
     */
    public function getMenuPages(): array
    {
        return $this->menuPages ??= $this->em->getRepository(Page::class)->ListMenu();
    }

    public function reset(): void
    {
        $this->config = null;
        $this->configLoaded = false;
        $this->menuPages = null;
    }
}
