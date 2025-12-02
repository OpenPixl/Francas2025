<?php

namespace App\Controller\App;

use App\Entity\Gestapp\Theme;
use App\Form\Webapp\ThemeType;
use App\Repository\Webapp\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ThemeController extends AbstractController
{
    #[Route(path: '/webapp/theme/', name: 'webapp_theme_index', methods: ['GET'])]
    public function index(ThemeRepository $themeRepository): Response
    {
        return $this->render('webapp/theme/index.html.twig', [
            'themes' => $themeRepository->findAll(),
        ]);
    }

    #[Route(path: '/webapp/theme/new', name: 'webapp_theme_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $theme = new Theme();
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($theme);
            $entityManager->flush();

            return $this->redirectToRoute('webapp_theme_index');
        }

        return $this->render('webapp/theme/new.html.twig', [
            'theme' => $theme,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/theme/{id}', name: 'webapp_theme_show', methods: ['GET'])]
    public function show(Theme $theme): Response
    {
        return $this->render('webapp/theme/show.html.twig', [
            'theme' => $theme,
        ]);
    }

    #[Route(path: '/webapp/theme/{id}/edit', name: 'webapp_theme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Theme $theme): Response
    {
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('webapp_theme_index');
        }

        return $this->render('webapp/theme/edit.html.twig', [
            'theme' => $theme,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/theme/{id}', name: 'webapp_theme_delete', methods: ['DELETE'])]
    public function delete(Request $request, Theme $theme): Response
    {
        if ($this->isCsrfTokenValid('delete'.$theme->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($theme);
            $entityManager->flush();
        }

        return $this->redirectToRoute('webapp_theme_index');
    }
}
