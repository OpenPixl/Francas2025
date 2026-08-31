<?php

namespace App\Controller\App;

use App\Entity\Gestapp\Support;
use App\Form\Webapp\SupportType;
use App\Repository\Webapp\SupportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SupportController extends AbstractController
{
    #[Route(path: '/webapp/support/', name: 'webapp_support_index', methods: ['GET'])]
    public function index(SupportRepository $supportRepository): Response
    {
        return $this->render('webapp/support/index.html.twig', [
            'supports' => $supportRepository->findAll(),
        ]);
    }

    #[Route(path: '/webapp/support/new', name: 'webapp_support_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $support = new Support();
        $form = $this->createForm(SupportType::class, $support);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($support);
            $entityManager->flush();

            return $this->redirectToRoute('webapp_support_index');
        }

        return $this->render('webapp/support/new.html.twig', [
            'support' => $support,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/support/{id}', name: 'webapp_support_show', methods: ['GET'])]
    public function show(Support $support): Response
    {
        return $this->render('webapp/support/show.html.twig', [
            'support' => $support,
        ]);
    }

    #[Route(path: '/webapp/support/{id}/edit', name: 'webapp_support_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Support $support, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SupportType::class, $support);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('webapp_support_index');
        }

        return $this->render('webapp/support/edit.html.twig', [
            'support' => $support,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/support/{id}', name: 'webapp_support_delete', methods: ['DELETE'])]
    public function delete(Request $request, Support $support, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$support->getId(), $request->request->get('_token'))) {
            $entityManager->remove($support);
            $entityManager->flush();
        }

        return $this->redirectToRoute('webapp_support_index');
    }
}
