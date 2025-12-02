<?php

namespace App\Controller\Webapp;

use App\Entity\Webapp\Comment;
use App\Form\Webapp\CommentType;
use App\Repository\Webapp\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommentController extends AbstractController
{
    #[Route(path: '/webapp/comment/', name: 'webapp_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('webapp/comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route(path: '/webapp/comment/new', name: 'webapp_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();

        $comment = new Comment();
        $comment->setAuthor($user);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('webapp_comment_index');
        }

        return $this->render('webapp/comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/comment/{id}', name: 'webapp_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('webapp/comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route(path: '/webapp/comment/{id}/edit', name: 'webapp_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('webapp_comment_index');
        }

        return $this->render('webapp/comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/webapp/comment/{id}', name: 'webapp_comment_delete', methods: ['DELETE'])]
    public function delete(Request $request, Comment $comment): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('webapp_comment_index');
    }
}
