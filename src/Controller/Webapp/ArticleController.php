<?php

namespace App\Controller\Webapp;

use App\Entity\Admin\College;
use App\Entity\Admin\Config;
use App\Entity\Webapp\Article;
use App\Entity\Webapp\Section;
use App\Form\Webapp\ArticlesType;
use App\Form\Webapp\Articles2Type;
use App\Form\Webapp\SearcharticleType;
use App\Repository\Admin\CollegeRepository;
use App\Repository\Webapp\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    /**
     * Liste dans l'admin tous les articles
     */
    #[Route(path: '/webapp/articles/', name: 'op_webapp_articles_index', methods: ['GET', 'POST'])]
    public function index(ArticleRepository $articleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $data = $articleRepository->findAll();
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
     * Liste des articles depuis l'espace College
     */
    #[Route(path: '/espcoll/college/articles/{idcollege}', name: 'op_espcoll_articles_bycollege', methods: ['GET', 'POST'])]
    public function articlesByCollege(
        ArticleRepository $articleRepository,
        PaginatorInterface $paginator,
        Request $request,
        CollegeRepository $collegeRepository,
        $idcollege
    ): Response
    {
        $college = $collegeRepository->find($idcollege);
        $data = $articleRepository->findBy(['college' => $college], ['updatedAt' => 'DESC']);
        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('webapp/articles/articlesbycollege.html.twig', [
            'college' => $college,
            'articles' => $articles,
            'page' => $request->query->getInt('page', 1),
        ]);
    }

    /**
     * Creation d'articles depuis l'espace College
     */
    #[Route(path: '/espcoll/articles/new', name: 'op_webapp_articles_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // récupération de l'objet college
        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);
        $article = new Article();
        $article->setAuthor($user);
        $article->setCollege($college);

        $form = $this->createForm(Articles2Type::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_college_espcoll', [
                'iduser' => $user->getId(),
            ]);
        }

        return $this->render('espacecollege/newarticles.html.twig', [
            'article' => $article,
            'college' =>$college,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Création d'article depuis l'espace admin
     */
    #[Route(path: '/webapp/articles/newadmin', name: 'op_webapp_articles_newadmin', methods: ['GET', 'POST'])]
    public function newAdmin(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // récupération de l'objet college
        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);

        $article = new Article();
        $article->setAuthor($user);
        $article->setCollege($college);

        $form = $this->createForm(ArticlesType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_articles_index', [
                'iduser' => $user->getId(),
            ]);
        }

        return $this->render('webapp/articles/newadmin.html.twig', [
            'article' => $article,
            'college' =>$college,
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

    #[Route(path: '/espcoll/articles/{id}/edit', name: 'op_webapp_articles_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);

        $form = $this->createForm(Articles2Type::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_college_espcoll', [
                'iduser' => $user->getId(),
            ]);
        }

        return $this->render('webapp/articles/edit.html.twig', [
            'article' => $article,
            'college' =>$college,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/articles/{id}/editAdmin', name: 'op_webapp_articles_edit_admin', methods: ['GET', 'POST'])]
    public function editAdmin(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ArticlesType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
     * Affiche les articles d'un college dans sa page
     */
    #[Route(path: '/webapp/articles/college/{idcollege}', name: 'op_webapp_articles_articlesbycollege', methods: ['GET'])]
    public function listArticlesByCollege($idcollege, Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $data = $entityManager->getRepository(Article::class)->listArticlesByCollege($idcollege);

        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
        10
        );

        return $this->render('webapp/articles/listarticlesbycollege.html.twig',[
            'articles' => $articles,
            'idcollege' => $idcollege
        ]);
    }

    #[Route(path: '/webapp/articles/college2/{idcollege}', name: 'op_webapp_articles_pagebycollege', methods: ['GET'])]
    public function listArticlesByPageCollege($idcollege, EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->listArticlesByCollege($idcollege)
        ;

        return $this->render('webapp/articles/listarticlesbypagecollege.html.twig',[
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
     * Affiche un article depuis la page du collège
     */
    #[Route(path: '/webapp/articles/slug/{id}/{idcollege}', name: 'op_webapp_articles_articleSlug', methods: ['GET'])]
    public function articleCollegeSlug($id, EntityManagerInterface $entityManager, $idcollege): Response
    {
        $college = $entityManager->getRepository(College::class)->find($idcollege);
        // Code pour afficher l'article depuis le slug'
        $article = $entityManager->getRepository(Article::class)->articleCollegeSlug($id);
        $config = $entityManager->getRepository(Config::class)->find(1);

        return $this->render('webapp/articles/articleCollegeSlug.html.twig',[
            'article' => $article,
            'college' => $college,
            'config' => $config,
        ]);
    }

    /**
     * Suppression d'une ligne index.php
     */
    #[Route(path: '/webapp/article/del/{id}', name: 'op_webapp_article_del', methods: ['POST'])]
    public function DelEvent(Request $request, Article $article, PaginatorInterface $paginator, EntityManagerInterface $entityManager) : Response
    {
        $entityManager->remove($article);
        $entityManager->flush();

        $data = $entityManager->getRepository(Article::class)->findAll();
        $articles = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            15
        );

        return $this->json([
            'code'=> 200,
            'message' => "L'article a été supprimé",
            'liste' => $this->renderView('webapp/articles/include/_liste.html.twig', [
                'articles' => $articles,
                'page' => $request->query->getInt('page', 1),
            ]),

        ], 200);
    }

    /**
     * Mise en archive d'un article
     */
    #[Route(path: '/webapp/articles/archived/{id}/{idcollege}', name: 'op_webapp_articles_archived', methods: ['POST'])]
    public function archived(Article $articles, EntityManagerInterface $entityManager, $idcollege ): \Symfony\Component\HttpFoundation\JsonResponse
    {
        // articles archivés
        $articles->setIsArchived(1);
        $entityManager->flush();

        // actualiser la liste des articles du collège
        $listearticles = $entityManager->getRepository(Article::class)->listArticlesByCollege($idcollege);

        return $this->json([
            'code'=> 200,
            'message' => "L'article a été correctement archivé",
            'listeArticles' => $this->renderView('webapp/articles/include/_listebycollege.html.twig', [
                'articles' => $listearticles,
                'idcollege' => $idcollege
            ]),
        ], 200);
    }
}
