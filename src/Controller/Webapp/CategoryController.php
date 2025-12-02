<?php

namespace App\Controller\Webapp;

use App\Entity\Webapp\Category;
use App\Form\Webapp\CategoryType;
use App\Repository\Webapp\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    #[Route(path: '/webapp/category/', name: 'webapp_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('webapp/category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route(path: '/webapp/category/new', name: 'webapp_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('webapp_category_index');
        }

        return $this->render('webapp/category/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/category/{id}', name: 'webapp_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('webapp/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route(path: '/webapp/category/{id}/edit', name: 'webapp_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('webapp_category_index');
        }

        return $this->render('webapp/category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/category/{id}', name: 'webapp_category_delete', methods: ['DELETE'])]
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('webapp_category_index');
    }
}
