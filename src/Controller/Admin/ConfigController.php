<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Config;
use App\Form\Admin\ConfigType;
use App\Repository\Admin\ConfigRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
            // ---------------------------
            // STEP 1 : Suppression de la vignette lors du click Checkbox
            // ---------------------------
            $supprvignettechkbx = $form->get('isSupprVignette')->getData();

            if($supprvignettechkbx && $supprvignettechkbx == true){
                // récupération du nom de l'image
                $vignetteName = $config->getVignetteName();
                $pathvignette = $this->getParameter('config_directory').'/'.$vignetteName;
                // On vérifie si l'image existe
                if(file_exists($pathvignette)){
                    unlink($pathvignette);
                }
                $config->setHeaderName(null);
                $config->setIsSupprVignette(0);
            }

            // ---------------------------
            // STEP 2 : Ajout ou modif de la vignette si "FileType" renseigné
            // ---------------------------
            $vignetteFile = $form->get('vignetteFile')->getData();
            if ($vignetteFile) {
                // Effacement du fichier vignetteFileName si il est présent en BDD
                // ---------------------------
                // Récupération du nom de l'image
                $vignetteName = $config->getVignetteName();

                // suppression du Fichier
                if($vignetteName){
                    $pathvignette = $this->getParameter('college_directory').'/'.$vignetteName;
                    // On vérifie si l'image existe
                    if(file_exists($pathvignette)){
                        unlink($pathvignette);
                    }
                }

                // Renommage du fichier source
                $originalVignetteFilename = pathinfo((string) $vignetteFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeVignetteFilename = $slugger->slug($originalVignetteFilename);
                $newVignetteFilename = $safeVignetteFilename . '-' . uniqid() . '.' . $vignetteFile->guessExtension();

                // Déplacement du fichier dans le répertoire adéquat
                try {
                    $vignetteFile->move(
                        $this->getParameter('config_directory'),
                        $newVignetteFilename
                    );
                } catch (FileException) {
                    $config->setHeaderName($newVignetteFilename);
                }
                // Hydration de l'entité
                $config->setVignetteName($newVignetteFilename);
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

    public function headerShow(EntityManagerInterface $entityManager){
        $config = $entityManager->getRepository(Config::class)->findOneBy(['id'=> 1]);

        return $this->render('admin/config/headershow.html.twig',[
            'config' => $config,
        ]);
    }
}
