<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Config;
use App\Form\Admin\ConfigType;
use App\Repository\Admin\ConfigRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ConfigController extends AbstractController
{
    #[Route(path: '/opadmin/config/', name: 'op_admin_config_index', methods: ['GET'])]
    public function index(ConfigRepository $configRepository): Response
    {
        return $this->render('admin/config/index.html.twig', [
            'configs' => $configRepository->findAll(),
        ]);
    }

    #[Route(path: '/opadmin/config/new', name: 'op_admin_config_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $config = new Config();
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $vignetteFile = $form->get('headerFile')->getData();

            if ($vignetteFile) {
                $originalVignetteFilename = pathinfo((string) $vignetteFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeVignetteFilename = $slugger->slug($originalVignetteFilename);
                $newVignetteFilename = $safeVignetteFilename . '-' . uniqid() . '.' . $vignetteFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $vignetteFile->move(
                        $this->getParameter('config_directory'),
                        $newVignetteFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $config->setVignetteName($newVignetteFilename);
            }

            $entityManager->persist($config);
            $entityManager->flush();

            return $this->redirectToRoute('op_admin_config_index');
        }

        return $this->render('admin/config/new.html.twig', [
            'config' => $config,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/opadmin/config/{id}', name: 'op_admin_config_show', methods: ['GET'])]
    public function show(Config $config): Response
    {
        return $this->render('admin/config/show.html.twig', [
            'config' => $config,
        ]);
    }

    #[Route(path: '/opadmin/config/{id}/edit', name: 'op_admin_config_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Config $config, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Bandeau du site : remplacement si un fichier est renseigné.
            $headerFile = $form->get('headerFile')->getData();
            if ($headerFile) {
                $this->deleteConfigFile($config->getHeaderName());
                $config->setHeaderName($this->storeConfigFile($headerFile, $slugger));
            }

            // Vignette (support d'article par défaut) : idem.
            $vignetteFile = $form->get('vignetteFile')->getData();
            if ($vignetteFile) {
                $this->deleteConfigFile($config->getVignetteName());
                $config->setVignetteName($this->storeConfigFile($vignetteFile, $slugger));
            }

            $entityManager->flush();

            return $this->redirectToRoute('op_admin_config_edit', [
                "id" => $config->getId()
            ]);
        }

        return $this->render('admin/config/edit.html.twig', [
            'config' => $config,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/opadmin/config/{id}', name: 'op_admin_config_delete', methods: ['DELETE'])]
    public function delete(Request $request, Config $config, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$config->getId(), $request->request->get('_token'))) {
            $entityManager->remove($config);
            $entityManager->flush();
        }

        return $this->redirectToRoute('op_admin_config_index');
    }

    /**
     * Suppression AJAX d'un média du site (bandeau ou vignette) depuis la page
     * Paramètres — même principe que op_admin_etablissement_delete_media.
     */
    #[Route(path: '/opadmin/config/{id}/delete-media/{field}', name: 'op_admin_config_delete_media', methods: ['POST'])]
    public function deleteMedia(Config $config, string $field, EntityManagerInterface $entityManager): Response
    {
        if (!in_array($field, ['header', 'vignette'], true)) {
            return $this->json(['code' => 400, 'message' => 'Champ invalide'], 400);
        }

        if ($field === 'header') {
            $this->deleteConfigFile($config->getHeaderName());
            $config->setHeaderName(null);
        } else {
            $this->deleteConfigFile($config->getVignetteName());
            $config->setVignetteName(null);
        }

        $entityManager->flush();

        return $this->json(['code' => 200, 'message' => 'Fichier supprimé avec succès'], 200);
    }

    /**
     * Supprime du disque un fichier du répertoire des médias du site, s'il existe.
     */
    private function deleteConfigFile(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $path = $this->getParameter('config_directory').'/'.$fileName;
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Range un fichier uploadé dans le répertoire des médias du site et renvoie
     * son nom généré.
     */
    private function storeConfigFile(UploadedFile $file, SluggerInterface $slugger): string
    {
        $safeName = $slugger->slug(pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME));
        $newName = $safeName.'-'.uniqid().'.'.$file->guessExtension();

        $file->move($this->getParameter('config_directory'), $newName);

        return $newName;
    }

    public function headerShow(EntityManagerInterface $entityManager){
        $config = $entityManager->getRepository(Config::class)->findOneBy(['id'=> 1]);

        return $this->render('admin/config/headershow.html.twig',[
            'config' => $config,
        ]);
    }
}
