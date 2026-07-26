<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:etablissement:move-uploads',
    description: 'Déplace les fichiers uploadés de public/uploads/images/colleges/ vers public/uploads/images/etablissements/ suite au renommage de College en Etablissement.',
)]
class MoveEtablissementUploadsCommand extends Command
{
    private readonly string $projectDir;

    public function __construct(KernelInterface $kernel)
    {
        parent::__construct();
        $this->projectDir = $kernel->getProjectDir();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $oldDir = $this->projectDir.'/public/uploads/images/colleges';
        $newDir = $this->projectDir.'/public/uploads/images/etablissements';

        if (!is_dir($oldDir)) {
            $io->info(sprintf('Aucun dossier "%s" trouvé, rien à déplacer.', $oldDir));

            return Command::SUCCESS;
        }

        if (!is_dir($newDir) && !mkdir($newDir, 0775, true) && !is_dir($newDir)) {
            $io->error(sprintf('Impossible de créer le dossier "%s".', $newDir));

            return Command::FAILURE;
        }

        $moved = 0;
        $skipped = 0;

        foreach (scandir($oldDir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $source = $oldDir.'/'.$file;
            $destination = $newDir.'/'.$file;

            if (!is_file($source)) {
                continue;
            }

            if (file_exists($destination)) {
                $io->warning(sprintf('Le fichier "%s" existe déjà dans le dossier de destination, ignoré.', $file));
                ++$skipped;
                continue;
            }

            if (rename($source, $destination)) {
                ++$moved;
            } else {
                $io->error(sprintf('Échec du déplacement de "%s".', $file));
            }
        }

        $io->success(sprintf('%d fichier(s) déplacé(s), %d ignoré(s) (déjà présents).', $moved, $skipped));

        if (count(scandir($oldDir)) === 2) {
            rmdir($oldDir);
            $io->info(sprintf('Dossier vide "%s" supprimé.', $oldDir));
        }

        return Command::SUCCESS;
    }
}
