<?php

namespace App\Controller\Webapp;

use App\Entity\Admin\Etablissement;
use App\Entity\Admin\Config;
use App\Entity\Webapp\Article;
use App\Entity\Webapp\Section;
use App\Form\Webapp\ArticlesType;
use App\Form\Webapp\Articles2Type;
use App\Form\Webapp\SearcharticleType;
use App\Repository\Admin\EtablissementRepository;
use App\Repository\Webapp\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleController extends AbstractController
{
    /**
     * Liste dans l'admin tous les articles
     */
    #[Route(path: '/webapp/articles/', name: 'op_webapp_articles_index', methods: ['GET', 'POST'])]
    public function index(ArticleRepository $articleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $data = $articleRepository->findBy([], ['id' => 'DESC']);
        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('webapp/articles/index.html.twig', [
            'articles' => $articles,
            'page' => $request->query->getInt('page', 1),
        ]);
    }

    /**
     * Liste des articles depuis l'espace Etablissement
     */
    #[Route(path: '/espetab/etablissement/articles/{idetablissement}', name: 'op_espetab_articles_byetablissement', methods: ['GET', 'POST'])]
    public function articlesByEtablissement(
        ArticleRepository $articleRepository,
        PaginatorInterface $paginator,
        Request $request,
        EtablissementRepository $etablissementRepository,
        $idetablissement
    ): Response
    {
        $etablissement = $etablissementRepository->find($idetablissement);
        $data = $articleRepository->findBy(['etablissement' => $etablissement], ['updatedAt' => 'DESC']);
        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('webapp/articles/articlesbyetablissement.html.twig', [
            'etablissement' => $etablissement,
            'articles' => $articles,
            'page' => $request->query->getInt('page', 1),
        ]);
    }

    /**
     * Creation d'articles depuis l'espace Etablissement
     */
    #[Route(path: '/espetab/articles/new', name: 'op_webapp_articles_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();

        // récupération de l'objet etablissement
        $etablissement = $entityManager->getRepository(Etablissement::class)->EtablissementByUser($user);
        $article = new Article();
        $article->setAuthor($user);
        $article->setEtablissement($etablissement);

        $form = $this->createForm(Articles2Type::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ---------------------------
            // STEP 1 : insertion de l'image dans le dossier public/uploads/articles'
            // ---------------------------
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo((string) $imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setImageName($newFilename);
            }

            // ---------------------------
            // STEP 2 : insertion du Document dans le dossier public/uploads/articles'
            // ---------------------------
            $docFile = $form->get('docFile')->getData();
            if ($docFile) {
                $originalFilename = pathinfo((string) $docFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $docFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $docFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setDoc($newFilename);
            }

            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_espetab', [
                'iduser' => $user->getId(),
            ]);
        }

        return $this->render('espace_etablissement/newarticles.html.twig', [
            'article' => $article,
            'etablissement' =>$etablissement,
            'form' => $form->createView(),
            'errors' => $form->getErrors()
        ]);
    }

    /**
     * Création d'article depuis l'espace admin
     */
    #[Route(path: '/webapp/articles/newadmin', name: 'op_webapp_articles_newadmin', methods: ['GET', 'POST'])]
    public function newAdmin(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();

        // récupération de l'objet etablissement
        $etablissement = $entityManager->getRepository(Etablissement::class)->EtablissementByUser($user);

        $article = new Article();
        $article->setAuthor($user);
        $article->setEtablissement($etablissement);

        $form = $this->createForm(ArticlesType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ---------------------------
            // STEP 1 : insertion de l'image dans le dossier public/uploads/articles'
            // ---------------------------
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo((string) $imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setImageName($newFilename);
            }

            // ---------------------------
            // STEP 2 : insertion du Document dans le dossier public/uploads/articles'
            // ---------------------------
            $docFile = $form->get('docFile')->getData();
            if ($docFile) {
                $originalFilename = pathinfo((string) $docFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $docFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $docFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setDoc($newFilename);
            }

            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_articles_index', [
                'iduser' => $user->getId(),
            ]);
        }

        return $this->render('webapp/articles/newadmin.html.twig', [
            'article' => $article,
            'etablissement' =>$etablissement,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/articles/{id}', name: 'op_webapp_articles_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('webapp/articles/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route(path: '/espetab/articles/{id}/edit', name: 'op_webapp_articles_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $etablissement = $entityManager->getRepository(Etablissement::class)->EtablissementByUser($user);

        $form = $this->createForm(Articles2Type::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ---------------------------
            // STEP 2 : insertion de l'image dans le dossier public/uploads/articles'
            // ---------------------------
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo((string) $imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setImageName($newFilename);
            }

            // ---------------------------
            // STEP 4 : insertion du Document dans le dossier public/uploads/articles'
            // ---------------------------
            $docFile = $form->get('docFile')->getData();
            if ($docFile) {
                $originalFilename = pathinfo((string) $docFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $docFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $docFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setDoc($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_espetab', [
                'iduser' => $user->getId(),
            ]);
        }

        return $this->render('webapp/articles/edit.html.twig', [
            'article' => $article,
            'etablissement' =>$etablissement,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/articles/{id}/editAdmin', name: 'op_webapp_articles_edit_admin', methods: ['GET', 'POST'])]
    public function editAdmin(Request $request, Article $article, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ArticlesType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ---------------------------
            // STEP 1 : Suppression de l'image lors du click Checkbox
            // ---------------------------
            $supprvignettechkbx = $form->get('isSupprImage')->getData();

            if($supprvignettechkbx && $supprvignettechkbx == true){
                // récupération du nom de l'image
                $imageName = $article->getImageName();
                $path = $this->getParameter('article_directory').'/'.$imageName;
                // On vérifie si l'image existe
                if(file_exists($path)){
                    unlink($path);
                }
                $article->setImageName(null);
                $article->setIsSupprImage(0);
            }

            // ---------------------------
            // STEP 2 : insertion de l'image dans le dossier public/uploads/articles'
            // ---------------------------
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo((string) $imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setImageName($newFilename);
            }

            // ---------------------------
            // STEP 3 : Suppression du support lors du click Checkbox
            // ---------------------------
            $supprDocChkbx = $form->get('isSupprDoc')->getData();

            if($supprDocChkbx && $supprDocChkbx == true){
                // récupération du nom de l'image
                $docName = $article->getdoc();
                $path = $this->getParameter('article_directory').'/'.$docName;
                // On vérifie si l'image existe
                if(file_exists($path)){
                    unlink($path);
                }
                $article->setDoc(null);
                $article->setIsSupprDoc(0);
            }

            // ---------------------------
            // STEP 4 : insertion du Document dans le dossier public/uploads/articles'
            // ---------------------------
            $docFile = $form->get('docFile')->getData();
            if ($docFile) {
                $originalFilename = pathinfo((string) $docFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $docFile->guessExtension();



                // Move the file to the directory where brochures are stored
                try {
                    $docFile->move(
                        $this->getParameter('article_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $article->setDoc($newFilename);
            }


            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_articles_index');
        }

        return $this->render('webapp/articles/edit_admin.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/articles/{id}', name: 'op_webapp_articles_delete', methods: ['DELETE'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('op_webapp_articles_index');
    }

    #[Route(path: '/webapp/articles/section/{idsection}', name: 'op_webapp_articles_articlesbysection', methods: ['GET'])]
    public function listArticlesBySection($idsection, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->listArticlesBySection($idsection);

        //dd($article);

        return $this->render('webapp/articles/listarticlebysection.html.twig',[
            'article' => $article,
        ]);
    }

    #[Route(path: '/webapp/articles/section/complet/{idsection}', name: 'op_webapp_articles_articlescompletebysection', methods: ['GET'])]
    public function ArticlesCompleteBySection($idsection, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->listArticlesBySection($idsection);

        return $this->render('webapp/articles/listarticlecompletebysection.html.twig',[
            'article' => $article,
        ]);
    }

    #[Route(path: '/webapp/articles/other/{idsection}', name: 'op_webapp_articles_articlesbysectionother', methods: ['GET'])]
    public function listArticlesBySectionOther($idsection, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->listArticlesBySection($idsection);

        return $this->render('webapp/articles/listarticlesbysectionother.html.twig',[
            'article' => $article,
        ]);
    }

    /**
     * Affiche les articles d'un établissement dans sa page
     */
    #[Route(path: '/webapp/articles/etablissement/{idetablissement}', name: 'op_webapp_articles_articlesbyetablissement', methods: ['GET'])]
    public function listArticlesByEtablissement($idetablissement, Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $data = $entityManager->getRepository(Article::class)->listArticlesByEtablissement($idetablissement);

        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
        10
        );

        return $this->render('webapp/articles/listarticlesbyetablissement.html.twig',[
            'articles' => $articles,
            'idetablissement' => $idetablissement
        ]);
    }

    #[Route(path: '/webapp/articles/etablissement2/{idetablissement}', name: 'op_webapp_articles_pagebyetablissement', methods: ['GET'])]
    public function listArticlesByPageEtablissement($idetablissement, EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->listArticlesByEtablissement($idetablissement)
        ;

        return $this->render('webapp/articles/listarticlesbypageetablissement.html.twig',[
            'articles' => $articles,
        ]);
    }

    #[Route(path: '/webapp/articles/carousel/{category}', name: 'op_webapp_articles_five_articles', methods: ['GET'])]
    public function listFiveArticles($category, EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->listFiveArticles($category);

        return $this->render('webapp/articles/listFiveArticles.html.twig',[
            'articles' => $articles,
        ]);
    }

    /**
     * Affiche un article depuis la page de l'établissement
     */
    #[Route(path: '/webapp/articles/slug/{id}/{idetablissement}', name: 'op_webapp_articles_articleSlug', methods: ['GET'])]
    public function articleEtablissementSlug($id, EntityManagerInterface $entityManager, $idetablissement): Response
    {
        $etablissement = $entityManager->getRepository(Etablissement::class)->find($idetablissement);
        // Code pour afficher l'article depuis le slug'
        $article = $entityManager->getRepository(Article::class)->articleEtablissementSlug($id);
        $config = $entityManager->getRepository(Config::class)->find(1);

        return $this->render('webapp/articles/articleEtablissementSlug.html.twig',[
            'article' => $article,
            'etablissement' => $etablissement,
            'config' => $config,
        ]);
    }

    #[Route(path: '/webapp/articles/{id}/delete-media/{field}', name: 'op_webapp_articles_delete_media', methods: ['POST'])]
    public function deleteMedia(Article $article, string $field, EntityManagerInterface $em): Response
    {
        if (!in_array($field, ['image', 'doc'])) {
            return $this->json(['code' => 400, 'message' => 'Champ invalide'], 400);
        }

        if ($field === 'image') {
            $fileName = $article->getImageName();
            if ($fileName) {
                $path = $this->getParameter('article_directory') . '/' . $fileName;
                if (file_exists($path)) {
                    unlink($path);
                }
                $article->setImageName(null);
            }
        } else {
            $fileName = $article->getDoc();
            if ($fileName) {
                $path = $this->getParameter('article_directory') . '/' . $fileName;
                if (file_exists($path)) {
                    unlink($path);
                }
                $article->setDoc(null);
            }
        }

        $em->flush();

        return $this->json(['code' => 200, 'message' => 'Fichier supprimé avec succès'], 200);
    }

    /**
     * Suppression d'une ligne index.php
     */
    #[Route(path: '/webapp/article/del/{id}/{page}', name: 'op_webapp_article_del', methods: ['POST'])]
    public function DelEvent(Request $request, Article $article, PaginatorInterface $paginator, EntityManagerInterface $entityManager, $page) : Response
    {
        $entityManager->remove($article);
        $entityManager->flush();

        $data = $entityManager->getRepository(Article::class)->findBy([], ['id' => 'DESC']);
        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', $page),
            15
        );

        return $this->json([
            'code'=> 200,
            'message' => "L'article a été supprimé",
            'liste' => $this->renderView('webapp/articles/include/_liste.html.twig', [
                'articles' => $articles,
            ]),

        ], 200);
    }

    /**
     * Mise en archive d'un article
     */
    #[Route(path: '/webapp/articles/archived/{id}/{idetablissement}', name: 'op_webapp_articles_archived', methods: ['POST'])]
    public function archived(Article $articles, EntityManagerInterface $entityManager, $idetablissement ): \Symfony\Component\HttpFoundation\JsonResponse
    {
        // articles archivés
        $articles->setIsArchived(1);
        $entityManager->flush();

        // actualiser la liste des articles de l'établissement
        $listearticles = $entityManager->getRepository(Article::class)->listArticlesByEtablissement($idetablissement);

        return $this->json([
            'code'=> 200,
            'message' => "L'article a été correctement archivé",
            'listeArticles' => $this->renderView('webapp/articles/include/_listebyetablissement.html.twig', [
                'articles' => $listearticles,
                'idetablissement' => $idetablissement
            ]),
        ], 200);
    }
}
