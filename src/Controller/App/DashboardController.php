<?php

namespace App\Controller\App;

use App\Entity\Admin\College;
use App\Entity\Admin\Config;
use App\Entity\Admin\Message;
use App\Entity\Webapp\Article;
use App\Entity\Webapp\Page;
use App\Entity\Webapp\Section;
use App\Repository\Admin\MessageRepository;
use App\Repository\Webapp\ArticleRepository;
use App\Repository\Webapp\RessourcesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class DashboardController
 * @package App\Controller\App
 */
class DashboardController extends AbstractController
{
    #[Route(path: '/', name: 'op_webapp_home')]
    public function index(EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $config = $entityManager->getRepository(Config::class)->find(1);
        if(!$config)
        {
            return $this->redirectToRoute('op_webapp_public_firstinstall');
        }else{
            //dd($config->getIsOffline());
            if($config->getIsOffline() == true){
                return $this->redirectToRoute('op_webapp_public_offline');
            }else{
                return $this->redirectToRoute('op_webapp_public_homepage');
            }
        }
    }

    /**
     * Affiche la page de menu
     */
    #[Route(path: '/app/{slug}', name: 'op_webapp_page')]
    public function showPage(EntityManagerInterface $entityManager, $slug): \Symfony\Component\HttpFoundation\Response
    {
        $config = $entityManager->getRepository(Config::class)->find(1);
        if($config->getIsOffline() == 1){
            return $this->render('app/offline.html.twig');
        }
        $page = $entityManager->getRepository(Page::class)->findOneBy(['slug' => $slug]);

        if (!$page) {
            throw $this->createNotFoundException(
                "La page n'existe pas" .$slug
            );
        }

        return $this->render('webapp/page/page.html.twig', [
            'page' => $page
        ]);
    }

    /**
     * Affiche automatiquement la page d'acceuil
     */
    #[Route(path: '/home', name: 'op_webapp_public_homepage')]
    public function HomePage(EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\Response
    {
        $config = $entityManager->getRepository(Config::class)->find(1);
        if($config->getIsOffline() == 1){
            return $this->render('app/offline.html.twig');
        }
        $sections = $entityManager->getRepository(Section::class)->findBy(['favorites' => 1], ['position' => 'ASC']);

        return $this->render('webapp/public/index.html.twig',[
            'config' => $config,
            'sections' => $sections,
        ]);
    }

    /**
     * Affiche automatiquement la page d'acceuil
     */
    #[Route(path: '/offline', name: 'op_webapp_public_offline')]
    public function offline(EntityManagerInterface $entityManager): Response
    {
        $config = $entityManager->getRepository(Config::class)->find(1);
        if($config->getIsOffline() == 0){
            return $this->redirectToRoute('op_webapp_home');
        }
        return $this->render('app/offline.html.twig');
    }

    /**
     * Affiche automatiquement la page d'acceuil
     */
    #[Route(path: '/contact', name: 'op_webapp_public_contactpage')]
    public function ContactPage(EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\Response
    {
        $config = $entityManager->getRepository(Config::class)->find(1);
        $sections = $entityManager->getRepository(Section::class)->findBy(['favorites' => 1], ['position' => 'ASC']);

        return $this->render('webapp/public/contact.html.twig',[
            'config' => $config,
            'sections' => $sections,
        ]);
    }

    #[Route(path: '/espcoll/dashboard/', name: 'op_webapp_espcoll')]
    public function epscoll(EntityManagerInterface $entityManager, MessageRepository $messageRepository, ArticleRepository $articleRepository, RessourcesRepository $ressourcesRepository) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_COLLEGE');
        $user = $this->getUser();

        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);
        $messages = $messageRepository->listMessagesByUser($user->getId());
        $articles = $articleRepository->findBy(['college' => $college]);
        $ressources = $ressourcesRepository->findBy(['college' => $college]);

        return $this->render('espacecollege/dashboard/espcoll.html.twig', [
            'user' => $user,
            'college' => $college,
            'articles'  => $articles,
            'ressources' => $ressources,
            'messages' => $messages
        ]);
    }

    #[Route(path: '/app/firstinstall', name: 'op_webapp_public_firstinstall')]
    public function firstInstall()
    {
        return $this->render('app/firstinstall.html.twig');
    }

}
