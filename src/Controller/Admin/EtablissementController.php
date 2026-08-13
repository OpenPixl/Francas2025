<?php

namespace App\Controller\Admin;

use App\Entity\Admin\Etablissement;
use App\Entity\Admin\Config;
use App\Entity\Admin\TypeEtablissement;
use App\Entity\Admin\User;
use App\Entity\Webapp\Article;
use App\Entity\Webapp\Message;
use App\Form\Admin\EtablissementEditType;
use App\Form\Admin\EtablissementType;
use App\Form\Search\EtablissementSearchType;
use App\Repository\Admin\EtablissementRepository;
use App\Repository\Admin\ConfigRepository;
use App\Repository\Webapp\ArticleRepository;
use App\Repository\Webapp\MessageRepository;
use App\Repository\Webapp\RessourcesRepository;
use App\Service\MediaPathResolver;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\MultiMatch;
use Elastica\Query\Term;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class EtablissementController extends AbstractController
{
    private $finder;

    public function __construct(PaginatedFinderInterface $finder, private readonly MediaPathResolver $mediaPathResolver)
    {
        $this->finder = $finder;
    }

    #[Route(path: '/op_admin/etablissement', name: 'op_admin_etablissement_index', methods: ['GET'])]
    public function index(EtablissementRepository $etablissementRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $data = $etablissementRepository->findAll();

        $etablissements = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('admin/etablissement/index.html.twig', [
            'etablissements' => $etablissements,
        ]);
    }

    #[Route(path: '/webapp/etablissement/newetablissement', name: 'op_webapp_etablissement_newetablissement', methods: ['GET', 'POST'])]
    public function newEtablissement(Request $request, EntityManagerInterface $entityManager): Response
    {

        // on récupére l'objet user de l'administrateur en cours
        //$iduser = $this->getUser()->getId();
        //$user = $this->getDoctrine()->getRepository(User::class)->find($iduser);
        // on crée l'instance Etablissement depuis la classe "Etablissement" et on injecte l'admin en cours
        $etablissement = new Etablissement();
        //$etablissement->setUser($user);
        $form = $this->createForm(EtablissementType::class, $etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($etablissement);
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_etablissement_espetab');
        }

        return $this->render('admin/etablissement/new.html.twig', [
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/etablissement/newetablissementAdmin/{iduser}', name: 'op_admin_etablissement_newetablissementadmin', methods: ['GET', 'POST'])]
    public function newEtablissementAdmin(Request $request, $iduser, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($iduser);

        $etablissement = new Etablissement();
        $etablissement->setUser($user);
        $form = $this->createForm(EtablissementType::class, $etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // l'établissement doit avoir un ID avant de pouvoir calculer son dossier de stockage
            $entityManager->persist($etablissement);
            $entityManager->flush();

            /** @var UploadedFile $headerFile */
            $headerFile = $form->get('headerFile')->getData();
            $logoFile = $form->get('logoFile')->getData();

            if ($headerFile) {
                $newHeaderFilename = $this->mediaPathResolver->etablissementHeaderFilename($etablissement, $headerFile->guessExtension());

                // Move the file to the directory where brochures are stored
                try {
                    $headerFile->move(
                        $this->mediaPathResolver->etablissementHeaderDir($etablissement),
                        $newHeaderFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setHeaderName($newHeaderFilename);
            }

            if ($logoFile) {
                $newlogoFilename = $this->mediaPathResolver->etablissementLogoFilename($etablissement, $logoFile->guessExtension());
                // Move the file to the directory where brochures are stored
                try {
                    $logoFile->move(
                        $this->mediaPathResolver->etablissementLogoDir($etablissement),
                        $newlogoFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setLogoName($newlogoFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('op_admin_etablissement_index');
        }

        return $this->render('admin/etablissement/new.html.twig', [
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/op_admin/etablissement/new', name: 'op_admin_etablissement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        //dd($user);
        $etablissement = new Etablissement();
        $etablissement->setUser($user);
        $form = $this->createForm(EtablissementType::class, $etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // l'établissement doit avoir un ID avant de pouvoir calculer son dossier de stockage
            $entityManager->persist($etablissement);
            $entityManager->flush();

            /** @var UploadedFile $banniereFile */
            $headerFileName = $form->get('headerFile')->getData();
            $logoFileName = $form->get('logoFile')->getData();

            if ($headerFileName) {
                $newheaderFilename = $this->mediaPathResolver->etablissementHeaderFilename($etablissement, $headerFileName->guessExtension());

                // Move the file to the directory where brochures are stored
                try {
                    $headerFileName->move(
                        $this->mediaPathResolver->etablissementHeaderDir($etablissement),
                        $newheaderFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setHeaderName($newheaderFilename);
            }

            if ($logoFileName) {
                $newlogoFilename = $this->mediaPathResolver->etablissementLogoFilename($etablissement, $logoFileName->guessExtension());
                // Move the file to the directory where brochures are stored
                try {
                    $logoFileName->move(
                        $this->mediaPathResolver->etablissementLogoDir($etablissement),
                        $newlogoFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setLogoName($newlogoFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('op_admin_etablissement_index');
        }


        return $this->render('admin/etablissement/new.html.twig', [
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/op_admin/etablissement/{id}', name: 'op_admin_etablissement_show', methods: ['GET'])]
    public function show(Etablissement $etablissement): Response
    {
        return $this->render('admin/etablissement/show.html.twig', [
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * Affiche un établissement depuis la page des établissements
     */
    #[Route(path: '/webapp/etablissement/blog/{id}', name: 'op_webapp_etablissement_show2', methods: ['GET'])]
    public function show2(Etablissement $etablissement,Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $data = $entityManager->getRepository(Article::class)->listArticlesByEtablissement($etablissement->getId());
        $data = array_map($this->mediaPathResolver->withArticleMediaUrls(...), $data);
        $config = $entityManager->getRepository(Config::class)->find(1);

        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/etablissement/show2.html.twig', [
            'etablissement' => $etablissement,
            'articles' => $articles,
            'config' => $config,
            'page' => $request->query->getInt('page', 1),
        ]);
    }

    /**
     * Affiche le bloc d'admin des établissements leur espace privé
     */
    #[Route(path: '/webapp/etablissement/bloc_admin/', name: 'op_webapp_etablissement_adminonly', methods: ['GET'])]
    public function blocAdminEtablissement(Etablissement $etablissement): Response
    {
        return $this->render('espace_etablissement/dashboard/_blocAdminEtablissement.html.twig', [
            'etablissement' => $etablissement,
        ]);
    }

    #[Route(path: '/espetab/etablissement/{id}/edit', name: 'op_espetab_etablissement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Etablissement $etablissement, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(EtablissementType::class, $etablissement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $headerFile */
            $headerFileInput = $form->get('headerFile')->getData();
            $logoFileInput = $form->get('logoFile')->getData();

            if ($headerFileInput) {
                // Effacement du fichier bannièreFileName si il est présent en BDD
                // récupération du nom de l'image
                $headerName = $etablissement->getHeaderName();
                // suppression du Fichier
                if($headerName){

                    $pathheader = $this->mediaPathResolver->etablissementHeaderDir($etablissement).'/'.$headerName;
                    // On vérifie si l'image existe
                    if(file_exists($pathheader)){
                        unlink($pathheader);
                    }
                }
                // Ajout de la nouvelle bannière
                $newheaderFilename = $this->mediaPathResolver->etablissementHeaderFilename($etablissement, $headerFileInput->guessExtension());

                // Move the file to the directory where brochures are stored
                try {
                    $headerFileInput->move(
                        $this->mediaPathResolver->etablissementHeaderDir($etablissement),
                        $newheaderFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setHeaderName($newheaderFilename);
            }

            if ($logoFileInput) {
                // Effacement du fichier bannièreFileName si il est présent en BDD
                // récupération du nom de l'image
                $logoName = $etablissement->getLogoName();
                // suppression du Fichier
                if($logoName){
                    $pathlogo = $this->mediaPathResolver->etablissementLogoDir($etablissement).'/'.$logoName;
                    // On vérifie si l'image existe
                    if(file_exists($pathlogo)){
                        unlink($pathlogo);
                    }
                }

                $newlogoFilename = $this->mediaPathResolver->etablissementLogoFilename($etablissement, $logoFileInput->guessExtension());
                // Move the file to the directory where brochures are stored
                try {
                    $logoFileInput->move(
                        $this->mediaPathResolver->etablissementLogoDir($etablissement),
                        $newlogoFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setLogoName($newlogoFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('op_espetab_etablissement_edit',[
                'id' => $etablissement->getId(),
            ]);
        }

        return $this->render('espace_etablissement/editetablissement.html.twig', [
            'layout' => 'base.html.twig',
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/etablissement/{id}/editetablissement', name: 'op_admin_etablissement_edit', methods: ['GET', 'POST'])]
    public function editEtablissementAdmin(Request $request, Etablissement $etablissement, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(EtablissementType::class, $etablissement);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // handleRequest a déjà appelé setUser() avec le nouveau user,
            // qui grâce au setUser() corrigé a bien détaché l'ancien animateur

            // si on change de photo
            /** @var UploadedFile $headerFile **/
            $headerFileInput = $form->get('headerFile')->getData();
            /** @var UploadedFile $logoFile **/
            $logoFileInput = $form->get('logoFile')->getData();

            if ($headerFileInput) {
                // Effacement du fichier bannièreFileName si il est présent en BDD
                // récupération du nom de l'image
                $headerName = $etablissement->getHeaderName();
                // suppression du Fichier
                if($headerName){
                    $pathheader = $this->mediaPathResolver->etablissementHeaderDir($etablissement).'/'.$headerName;
                    // On vérifie si l'image existe
                    if(file_exists($pathheader)){
                        unlink($pathheader);
                    }
                }
                // Ajout de la nouvelle bannière
                $newheaderFilename = $this->mediaPathResolver->etablissementHeaderFilename($etablissement, $headerFileInput->guessExtension());

                // Move the file to the directory where brochures are stored
                try {
                    $headerFileInput->move(
                        $this->mediaPathResolver->etablissementHeaderDir($etablissement),
                        $newheaderFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setHeaderName($newheaderFilename);
            }

            if ($logoFileInput) {
                // Effacement du fichier bannièreFileName si il est présent en BDD
                // récupération du nom de l'image
                $logoName = $etablissement->getLogoName();
                // suppression du Fichier
                if($logoName){
                    $pathlogo = $this->mediaPathResolver->etablissementLogoDir($etablissement).'/'.$logoName;
                    // On vérifie si l'image existe
                    if(file_exists($pathlogo)){
                        unlink($pathlogo);
                    }
                }

                $newlogoFilename = $this->mediaPathResolver->etablissementLogoFilename($etablissement, $logoFileInput->guessExtension());
                // Move the file to the directory where brochures are stored
                try {
                    $logoFileInput->move(
                        $this->mediaPathResolver->etablissementLogoDir($etablissement),
                        $newlogoFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $etablissement->setLogoName($newlogoFilename);
            }

            //dd($etablissement);

            $entityManager->flush();

            return $this->redirectToRoute('op_admin_etablissement_edit',[
               // 'id' => $user->getId(),
                'id' => $etablissement->getId(),
            ]);
        }

        return $this->render('admin/etablissement/edit.html.twig', [
            'layout' => 'admin.html.twig',
            'etablissement' => $etablissement,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/op_admin/etablissement/{id}/delete-media/{field}', name: 'op_admin_etablissement_delete_media', methods: ['POST'])]
    public function deleteMedia(Etablissement $etablissement, string $field, EntityManagerInterface $em): Response
    {
        if (!in_array($field, ['logo', 'header'])) {
            return $this->json(['code' => 400, 'message' => 'Champ invalide'], 400);
        }

        if ($field === 'logo') {
            $fileName = $etablissement->getLogoName();
            if ($fileName) {
                $path = $this->mediaPathResolver->etablissementLogoDir($etablissement) . '/' . $fileName;
                if (file_exists($path)) {
                    unlink($path);
                }
                $etablissement->setLogoName(null);
            }
        } else {
            $fileName = $etablissement->getHeaderName();
            if ($fileName) {
                $path = $this->mediaPathResolver->etablissementHeaderDir($etablissement) . '/' . $fileName;
                if (file_exists($path)) {
                    unlink($path);
                }
                $etablissement->setHeaderName(null);
            }
        }

        $em->flush();

        return $this->json(['code' => 200, 'message' => 'Fichier supprimé avec succès'], 200);
    }

    #[Route(path: '/op_admin/etablissement/{id}', name: 'op_admin_etablissement_delete', methods: ['DELETE'])]
    public function delete(
        Request $request,
        Etablissement $etablissement,
        Filesystem $filesystem,
        ArticleRepository $articlesRepository,
        RessourcesRepository $RessourcesRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$etablissement->getId(), $request->request->get('_token'))) {

            // Concerne la suppression des relations par rapport à l'établissement
            $articles = $articlesRepository->findBy(['etablissement'=>$etablissement]);
            foreach ($articles as $article) {
                $etablissement->removeArticle($article);
            }
            $ressources = $RessourcesRepository->findBy(['etablissement'=>$etablissement]);
            foreach ($ressources as $ressource){
                $etablissement->removeRessource($ressource);
            }

            // on instancie la classe de gestion des entités

            // Récupération des noms des images enregistrées
            $headerName = $etablissement->getHeaderName();
            $logoName = $etablissement->getLogoName();
            // Suppression de l'image physique liée à la bannière de l'établissement
            if($headerName){
                $pathheader = $this->mediaPathResolver->etablissementHeaderDir($etablissement).'/'.$headerName;
                // On vérifie si l'image existe
                if(file_exists($pathheader)){
                    unlink($pathheader);
                }
            }
            // Suppression de l'image physique liée à l'image de profil de l'établissement
            if($logoName){
                $pathlogo = $this->mediaPathResolver->etablissementLogoDir($etablissement).'/'.$logoName;
                // On vérifie si l'image existe
                if(file_exists($pathlogo)){
                    unlink($pathlogo);
                }
            }

            $entityManager->remove($etablissement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('op_admin_etablissement_index');
    }

    /**
     * Suppression d'une ligne dans le index.php
     */
    #[Route(path: '/op_admin/etablissement/del/{id}', name: 'op_admin_etablissement_del', methods: ['POST'])]
    public function Del(Etablissement $etablissement, EntityManagerInterface $entityManager) : Response
    {
        $entityManager->remove($etablissement);
        $entityManager->flush();

        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->json([
            'code'=> 200,
            'message' => "L'établissement a été supprimé",
            'liste' => $this->renderView('admin/user/include/_liste.html.twig', [
                'users' => $users
            ])
        ], 200);
    }

    #[Route(path: '/section/{idsection}', name: 'op_admin_etablissement_bysection', methods: ['GET', 'POST'])]
    public function listEtablissementsBySection(Request $request, $idsection, ConfigRepository $configRepository, EntityManagerInterface $entityManager): Response
    {
        $config = $configRepository->find(1);
        $etablissements = $entityManager->getRepository(Etablissement::class)->listEtablissementsBySection($idsection);

        $etablissementsByType = [];
        $etablissementsChoices = [];
        foreach ($etablissements as $e) {
            $type = $e['typeEtablissementLibelle'] ?? 'Autre';
            $etablissementsChoices[$type] = $e['idTypeEtablissement'];
            $e['logoUrl'] = $this->mediaPathResolver->etablissementImageUrl($e['id'], $e['logoName']);
            $etablissementsByType[$type][] = $e;
        }

        // Formulaire
        $form = $this->createForm(EtablissementSearchType::class, null, [
            'action' => $this->generateUrl('op_admin_etablissement_bysection', ['idsection' => $idsection]),
            'method' => 'POST',
            'attr' => [
                'id' => 'Etablissement_searchForm',
            ],
            'etablissementsChoices' => $etablissementsChoices

        ]);
        $form->handleRequest($request);

        // Construction de la requête Elasticsearch
        $boolQuery = new BoolQuery();

        //dd($form->isSubmitted());
        // Filtres issus du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Recherche texte
            if (!empty($data['query'])) {
                $multiMatch = new MultiMatch();
                $multiMatch->setFields(['name', 'city', 'zipcode']);
                $multiMatch->setQuery($data['query']);
                $boolQuery->addMust($multiMatch);
            }

            // Filtre structure sélectionnée
            if (!empty($data['etablissementChoice'])) {
                $termQuery = new Term();
                $termQuery->setTerm('typeEtablissement.id', $data['etablissementChoice']);
                $boolQuery->addFilter($termQuery);
            }

            // Exécution de la requête
            $query = new Query($boolQuery);
            $query->setSize(50);

            $results = $this->finder->find($query);

            $etablissementsByType = [];
            foreach ($results as $r) {
                $type = $r->getTypeEtablissement()?->getLibelle() ?? 'Autre';
                $etablissementsByType[$type][] = [
                    'id' => $r->getId(),
                    'name' => $r->getName(),
                    'city' => $r->getCity(),
                    'isActive' => $r->getIsActive(),
                    'logoName' => $r->getLogoName(),
                    'idTypeEtablissement' => $r->getTypeEtablissement()?->getId(),
                    'typeEtablissementLibelle' => $type,
                    'logoUrl' => $this->mediaPathResolver->etablissementLogoUrl($r),
                ];
            }

            return $this->json([
                'code' => 200,
                'liste' => $this->renderView('admin/etablissement/include/_listesearch.html.twig',[
                    'etablissementsByType' => $etablissementsByType,
                    'config' => $config,
                ]),
            ],200);
        }

        return $this->render('admin/etablissement/listetablissementsbysection.html.twig',[
            'form' => $form->createView(),
            'etablissementsByType' => $etablissementsByType,
            'config' => $config
        ]);
    }

    /**
     * Affiche tous les établissements d'un type donné
     */
    #[Route(path: '/webapp/etablissement/type/{idtype}', name: 'op_webapp_etablissement_bytype', methods: ['GET'])]
    public function listEtablissementsByType($idtype, ConfigRepository $configRepository, EntityManagerInterface $entityManager): Response
    {
        $config = $configRepository->find(1);
        $etablissements = $entityManager->getRepository(Etablissement::class)->listEtablissementsByType($idtype);
        $etablissements = array_map(
            fn (array $e) => $e + ['logoUrl' => $this->mediaPathResolver->etablissementImageUrl($e['id'], $e['logoName'])],
            $etablissements
        );
        $typeEtablissement = $entityManager->getRepository(TypeEtablissement::class)->find($idtype);

        return $this->render('admin/etablissement/listetablissementsbytype.html.twig', [
            'etablissements' => $etablissements,
            'typeEtablissement' => $typeEtablissement,
            'config' => $config
        ]);
    }

    /**
     * @param $iduser
     * @return Response
     */
    #[Route(path: 'webapp/etablissement/espace/{iduser}', name: 'op_webapp_etablissement_espetab')]
    public function findEtablissementById($iduser, EntityManagerInterface $entityManager): Response
    {
        $etablissement = $entityManager->getRepository(Etablissement::class)->EtablissementByUser($iduser);

        if (!$etablissement) {
            $this->redirectToRoute('op_admin_dashboard_index');
        }

        return $this->render('admin/etablissement/etablissementbyuser.html.twig', [
            'etablissement' => $etablissement,
        ]);
    }

    /**
     * Permet de mettre en menu la poge ou non
     */
    #[Route(path: '/op_admin/etablissement/verified/{id}', name: 'op_admin_etablissement_verified')]
    public function jsVerified(Etablissement $etablissement, EntityManagerInterface $em) : Response
    {
        $admin = $this->getUser();
        $isActive = $etablissement->getIsActive();
        // renvoie une erreur car l'utilisateur n'est pas connecté
        if(!$admin) return $this->json([
            'code' => 403,
            'message'=> "Vous n'êtes pas connecté"
        ], 403);
        // Si la page est déja publiée, alors on dépublie
        if($isActive == true){
            $etablissement->setIsActive(0);
            $em->flush();
            return $this->json(['code'=> 200, 'message' => "L'établissement est désactivé pour l'instant"], 200);
        }
        // Si la page est déja dépubliée, alors on publie
        $etablissement->setIsActive(1);
        $em->flush();
        return $this->json([
            'code'=> 200,
            'message' => "L'établissement est activé et sera visible sur le site."],
            200);
    }
}
