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
 * Renomme les médias déjà migrés (établissements + articles) vers la convention
 * de nommage déterministe "{slug}_{suffixe}.{extension}" (voir MediaPathResolver),
 * et met à jour les colonnes correspondantes en base.
 *
 * Rien n'est jamais modifié sans --apply.
 */
#[AsCommand(
    name: 'app:media:rename-to-slug',
    description: 'Renomme les fichiers médias existants vers la convention "{slug}_{suffixe}.{extension}" et met à jour la base.',
)]
class RenameMediaToSlugCommand extends Command
{
    private const BATCH_SIZE = 200;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaPathResolver $mediaPathResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Renomme réellement les fichiers et met à jour la base (sans cette option : dry-run, rien n\'est touché)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');

        if (!$apply) {
            $io->note('Mode dry-run (aucune option --apply) : rien ne sera renommé ni modifié en base, uniquement un rapport.');
        }

        $stats = ['renamed' => 0, 'already_ok' => 0, 'missing_source' => 0, 'target_exists' => 0];

        // ---- Établissements ----
        $etablissements = $this->entityManager->getRepository(Etablissement::class)->findAll();
        foreach ($etablissements as $etablissement) {
            if ($etablissement->getHeaderName()) {
                $this->renameOne(
                    $this->mediaPathResolver->etablissementHeaderDir($etablissement),
                    $etablissement->getHeaderName(),
                    $this->mediaPathResolver->etablissementHeaderFilename($etablissement, $this->extensionOf($etablissement->getHeaderName())),
                    fn (string $newName) => $etablissement->setHeaderName($newName),
                    $apply,
                    $io,
                    $stats
                );
            }
            if ($etablissement->getLogoName()) {
                $this->renameOne(
                    $this->mediaPathResolver->etablissementLogoDir($etablissement),
                    $etablissement->getLogoName(),
                    $this->mediaPathResolver->etablissementLogoFilename($etablissement, $this->extensionOf($etablissement->getLogoName())),
                    fn (string $newName) => $etablissement->setLogoName($newName),
                    $apply,
                    $io,
                    $stats
                );
            }
        }
        if ($apply) {
            $this->entityManager->flush();
        }

        // ---- Articles (par lots) ----
        $articleRepository = $this->entityManager->getRepository(Article::class);
        $offset = 0;
        while ($articles = $articleRepository->findBy([], ['id' => 'ASC'], self::BATCH_SIZE, $offset)) {
            foreach ($articles as $article) {
                if ($article->getImageName()) {
                    $this->renameOne(
                        $this->mediaPathResolver->articleImageDirFor($article),
                        $article->getImageName(),
                        $this->mediaPathResolver->articleImageFilename($article, $this->extensionOf($article->getImageName())),
                        fn (string $newName) => $article->setImageName($newName),
                        $apply,
                        $io,
                        $stats
                    );
                }
                if ($article->getDoc()) {
                    $this->renameOne(
                        $this->mediaPathResolver->articleDocDirFor($article),
                        $article->getDoc(),
                        $this->mediaPathResolver->articleDocFilename($article, $this->extensionOf($article->getDoc())),
                        fn (string $newName) => $article->setDoc($newName),
                        $apply,
                        $io,
                        $stats
                    );
                }
            }
            if ($apply) {
                $this->entityManager->flush();
            }
            $this->entityManager->clear();
            $offset += self::BATCH_SIZE;
        }

        $io->section('Résumé');
        $io->table(['Catégorie', 'Total'], [
            ['Renommés'.($apply ? '' : ' (à renommer)'), $stats['renamed']],
            ['Déjà au bon nom', $stats['already_ok']],
            ['Fichier source introuvable (ignoré)', $stats['missing_source']],
            ['Nom cible déjà pris par un autre fichier (ignoré, à vérifier)', $stats['target_exists']],
        ]);

        if (!$apply) {
            $io->info('Dry-run terminé. Relancez avec --apply pour renommer réellement et mettre à jour la base.');
        } else {
            $io->success('Renommage terminé.');
        }

        return Command::SUCCESS;
    }

    private function extensionOf(string $filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * @param array{renamed:int, already_ok:int, missing_source:int, target_exists:int} $stats
     */
    private function renameOne(
        string $dir,
        string $currentName,
        string $newName,
        callable $setter,
        bool $apply,
        SymfonyStyle $io,
        array &$stats,
    ): void {
        if ($currentName === $newName) {
            ++$stats['already_ok'];

            return;
        }

        $source = $dir.'/'.$currentName;
        if (!is_file($source)) {
            ++$stats['missing_source'];
            $io->writeln(sprintf('<comment>Introuvable, ignoré : %s</comment>', $source), OutputInterface::VERBOSITY_VERBOSE);

            return;
        }

        $destination = $dir.'/'.$newName;
        if (is_file($destination)) {
            ++$stats['target_exists'];
            $io->warning(sprintf('"%s" -> "%s" : le nom cible existe déjà, ignoré (collision potentielle à vérifier manuellement).', $currentName, $newName));

            return;
        }

        ++$stats['renamed'];
        $io->writeln(sprintf('%s%s -> %s', $apply ? '' : '[dry-run] ', $currentName, $newName), OutputInterface::VERBOSITY_VERBOSE);

        if (!$apply) {
            return;
        }

        if (!rename($source, $destination)) {
            $io->error(sprintf('Échec du renommage de "%s" vers "%s".', $currentName, $newName));

            return;
        }

        $setter($newName);
    }
}
