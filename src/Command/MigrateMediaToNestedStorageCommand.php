<?php

namespace App\Command;

use App\Entity\Admin\Etablissement;
use App\Entity\Webapp\Article;
use App\Service\MediaPathResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Migre les médias uploadés (logos/bandeaux d'établissement, images et pièces jointes
 * d'articles) depuis l'ancienne arborescence plate (public/uploads/images/{articles,etablissements})
 * vers la nouvelle arborescence imbriquée par propriétaire + type de média
 * (public/uploads/{etablissements,admins}/{id}/{images,articles,audios,videos,docs}).
 *
 * Rien n'est jamais modifié sans --apply. La suppression des fichiers orphelins (non
 * référencés en base) nécessite en plus --delete-orphans.
 */
#[AsCommand(
    name: 'app:media:migrate-to-nested-storage',
    description: 'Déplace les médias établissements/articles vers la nouvelle arborescence imbriquée par propriétaire.',
)]
class MigrateMediaToNestedStorageCommand extends Command
{
    private const BATCH_SIZE = 200;

    /** @var array<string, true> nom de fichier => réclamé, par dossier source (clé = chemin absolu du dossier) */
    private array $claimed = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaPathResolver $mediaPathResolver,
        private readonly string $etablissementDirectory,
        private readonly string $articleDirectory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Déplace réellement les fichiers (sans cette option : dry-run, rien n\'est touché)')
            ->addOption('delete-orphans', null, InputOption::VALUE_NONE, 'Supprime les fichiers physiques non réclamés par une ligne en base (seulement combiné avec --apply)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $deleteOrphans = (bool) $input->getOption('delete-orphans');

        if (!$apply) {
            $io->note('Mode dry-run (aucune option --apply) : rien ne sera déplacé ni supprimé, uniquement un rapport.');
        }

        $stats = [
            'etablissement_logo' => 0,
            'etablissement_header' => 0,
            'article_image' => 0,
            'article_doc' => 0,
            'missing_source' => 0,
        ];
        $fallbackLog = [];

        // ---- Établissements ----
        $etablissements = $this->entityManager->getRepository(Etablissement::class)->findAll();
        foreach ($etablissements as $etablissement) {
            if ($logoName = $etablissement->getLogoName()) {
                $this->migrateFile(
                    $this->etablissementDirectory,
                    $logoName,
                    $this->mediaPathResolver->etablissementLogoDir($etablissement),
                    $apply,
                    $io,
                    $stats,
                    'etablissement_logo'
                );
            }
            if ($headerName = $etablissement->getHeaderName()) {
                $this->migrateFile(
                    $this->etablissementDirectory,
                    $headerName,
                    $this->mediaPathResolver->etablissementHeaderDir($etablissement),
                    $apply,
                    $io,
                    $stats,
                    'etablissement_header'
                );
            }
        }

        // ---- Articles (par lots pour ne pas saturer la mémoire) ----
        $articleRepository = $this->entityManager->getRepository(Article::class);
        $offset = 0;
        while ($articles = $articleRepository->findBy([], ['id' => 'ASC'], self::BATCH_SIZE, $offset)) {
            foreach ($articles as $article) {
                if ($imageName = $article->getImageName()) {
                    $this->migrateFile(
                        $this->articleDirectory,
                        $imageName,
                        $this->mediaPathResolver->articleImageDirFor($article),
                        $apply,
                        $io,
                        $stats,
                        'article_image'
                    );
                }
                if ($doc = $article->getDoc()) {
                    $supportId = $article->getSupport()?->getId();
                    if (null === $supportId || 0 === $supportId) {
                        $fallbackLog[] = sprintf(
                            'Article #%d : support %s -> dossier "%s" (retombée sur l\'extension du fichier "%s")',
                            $article->getId(),
                            null === $supportId ? 'absent' : '"Aucun"',
                            $this->mediaPathResolver->resolveDocSubfolder($supportId, $doc),
                            $doc
                        );
                    }
                    $this->migrateFile(
                        $this->articleDirectory,
                        $doc,
                        $this->mediaPathResolver->articleDocDirFor($article),
                        $apply,
                        $io,
                        $stats,
                        'article_doc'
                    );
                }
            }
            $this->entityManager->clear();
            $offset += self::BATCH_SIZE;
        }

        $io->section('Résumé');
        $io->table(['Catégorie', 'Traités'], [
            ['Logos établissement', $stats['etablissement_logo']],
            ['Bandeaux établissement', $stats['etablissement_header']],
            ['Images article', $stats['article_image']],
            ['Pièces jointes article', $stats['article_doc']],
            ['Fichiers source introuvables (ignorés)', $stats['missing_source']],
        ]);

        if ($fallbackLog) {
            $io->section(sprintf('Support absent ou "Aucun" — %d cas (rangés par extension, à vérifier)', count($fallbackLog)));
            foreach ($fallbackLog as $line) {
                $io->writeln('  - '.$line);
            }
        }

        // ---- Orphelins : fichiers physiques jamais réclamés par une ligne en base ----
        $this->reportOrphans($this->etablissementDirectory, $apply, $deleteOrphans, $io);
        $this->reportOrphans($this->articleDirectory, $apply, $deleteOrphans, $io);

        if (!$apply) {
            $io->info('Dry-run terminé. Relancez avec --apply pour déplacer réellement les fichiers.');
        } else {
            $io->success('Migration terminée.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array{etablissement_logo:int, etablissement_header:int, article_image:int, article_doc:int, missing_source:int} $stats
     */
    private function migrateFile(
        string $sourceDir,
        string $filename,
        string $destinationDir,
        bool $apply,
        SymfonyStyle $io,
        array &$stats,
        string $statKey,
    ): void {
        $source = $sourceDir.'/'.$filename;
        $this->claimed[$sourceDir][$filename] = true;

        if (!is_file($source)) {
            ++$stats['missing_source'];
            $io->writeln(sprintf('<comment>Introuvable, ignoré : %s</comment>', $source), OutputInterface::VERBOSITY_VERBOSE);

            return;
        }

        ++$stats[$statKey];

        $destination = $destinationDir.'/'.$filename;
        if ($source === $destination || is_file($destination)) {
            // déjà migré (relance après un précédent --apply)
            return;
        }

        if (!$apply) {
            return;
        }

        if (!copy($source, $destination)) {
            $io->error(sprintf('Échec de la copie de "%s" vers "%s".', $source, $destination));

            return;
        }

        if (filesize($source) !== filesize($destination)) {
            $io->error(sprintf('Taille différente après copie pour "%s", fichier source conservé par sécurité.', $filename));
            unlink($destination);

            return;
        }

        unlink($source);
    }

    private function reportOrphans(string $sourceDir, bool $apply, bool $deleteOrphans, SymfonyStyle $io): void
    {
        if (!is_dir($sourceDir)) {
            return;
        }

        $claimed = $this->claimed[$sourceDir] ?? [];
        $orphans = [];
        foreach (scandir($sourceDir) as $file) {
            if ('.' === $file || '..' === $file || !is_file($sourceDir.'/'.$file)) {
                continue;
            }
            if (!isset($claimed[$file])) {
                $orphans[] = $file;
            }
        }

        if (!$orphans) {
            return;
        }

        $io->section(sprintf('Fichiers orphelins dans "%s" (%d)', $sourceDir, count($orphans)));
        foreach ($orphans as $file) {
            $io->writeln('  - '.$file);
        }

        if ($apply && $deleteOrphans) {
            foreach ($orphans as $file) {
                unlink($sourceDir.'/'.$file);
            }
            $io->warning(sprintf('%d fichier(s) orphelin(s) supprimé(s) dans "%s".', count($orphans), $sourceDir));
        } else {
            $io->note('Non supprimés (relancez avec --apply --delete-orphans pour les supprimer).');
        }
    }
}
