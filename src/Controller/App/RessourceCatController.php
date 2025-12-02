<?php

namespace App\Controller\App;

use App\Entity\Gestapp\RessourceCat;
use App\Form\Webapp\RessourceCatType;
use App\Repository\Webapp\RessourceCatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 */
class RessourceCatController extends AbstractController
{
    #[Route(path: '/admin/ressourcescat/', name: 'op_webapp_ressource_cat_index', methods: ['GET'])]
    public function index(RessourceCatRepository $ressourceCatRepository): Response
    {
        return $this->render('webapp/ressource_cat/index.html.twig', [
            'ressource_cats' => $ressourceCatRepository->findAll(),
        ]);
    }

    #[Route(path: '/admin/ressourcescat/new', name: 'op_webapp_ressource_cat_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if(!$data) {
            $ressourceCat = new RessourceCat();
            $form = $this->createForm(RessourceCatType::class, $ressourceCat);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($ressourceCat);
                $entityManager->flush();

                return $this->json([
                    'code' => 200,
                    'category' => $ressourceCat,
                    'message' => "Une catégorie a été ajoutée."
                ], 200);
            }

            return $this->render('webapp/ressource_cat/new.html.twig', [
                'ressource_cat' => $ressourceCat,
                'form' => $form->createView(),
            ]);
        }else{
            $name = $data['name'];
            $parent = $data['parent'];
            $ressourceCat = new RessourceCat();
            $ressourceCat->setName($name);
            $ressourceCat->setParent($parent);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($ressourceCat);
            $entityManager->flush();

            $category = $ressourceCat->getName();

            return $this->json([
                'code' => 200,
                'category' => $category,
                'message' => "Une catégorie a été ajoutée."
            ], 200);
        }
    }

    #[Route(path: '/admin/ressourcescat/{id}', name: 'op_webapp_ressource_cat_show', methods: ['GET'])]
    public function show(RessourceCat $ressourceCat): Response
    {
        return $this->render('webapp/ressource_cat/show.html.twig', [
            'ressource_cat' => $ressourceCat,
        ]);
    }

    #[Route(path: '/admin/ressourcescat/{id}/edit', name: 'op_webapp_ressource_cat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RessourceCat $ressourceCat): Response
    {
        $form = $this->createForm(RessourceCatType::class, $ressourceCat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('op_webapp_ressource_cat_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('webapp/ressource_cat/edit.html.twig', [
            'ressource_cat' => $ressourceCat,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/ressourcescat/{id}', name: 'op_webapp_ressource_cat_delete', methods: ['POST'])]
    public function delete(Request $request, RessourceCat $ressourceCat): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ressourceCat->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ressourceCat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('webapp_ressource_cat_index', [], Response::HTTP_SEE_OTHER);
    }
}
