<?php

namespace App\Controller\Admin;


use App\Repository\Webapp\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{

    #[Route(path: '/security/redirect_login', name: 'op_admin_security_redirect_login')]
    public function redirectLogin()
    {
        $this->denyAccessUnlessGranted('ROLE_COLLEGE');
        $user = $this->getUser();

        if ($this->isGranted('ROLE_SUPER_ADMIN') || $this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('op_admin_dashboard_index');
        }

        if ($this->isGranted('ROLE_COLLEGE')) {
            return $this->redirectToRoute('op_webapp_espcoll');
        }

        // fallback si autre rôle
        return $this->redirectToRoute('op_admin_security_login');
    }

    #[Route(path: '/admin', name: 'op_admin_dashboard_index')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $listArticles = $articleRepository->createQueryBuilder('a')
            ->join('a.college', 'c')
            ->orderBy('a.id', 'DESC')   // ou 'a.createdAt' si tu as un champ de date
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard/index.html.twig', [
            'articles' => $listArticles,
        ]);
    }
}
