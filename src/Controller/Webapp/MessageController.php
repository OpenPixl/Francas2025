<?php

namespace App\Controller\Webapp;

use App\Entity\Admin\College;
use App\Entity\Admin\Message;
use App\Form\Admin\MessageType;
use App\Form\Webapp\ReplyType;
use App\Repository\Admin\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MessageController extends AbstractController
{
    #[Route(path: '/espcoll/message/', name: 'op_webapp_message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);

        return $this->render('webapp/message/index.html.twig', [
            'messages' => $messageRepository->findAll(),
            'college' => $college
        ]);
    }

    #[Route(path: '/espcoll/message/new', name: 'op_webapp_message_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // code d'information du college en cours d'utilisation
        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);

        $message = new Message();
        $message
            ->setAuthor($user)
            ->setFollow(uniqid())
        ;

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_message_messagesbyuser', ['iduser' => $user->getId()]);
        }

        return $this->render('espacecollege/newmessage.html.twig', [
            'message' => $message,
            'form' => $form->createView(),
            'college' => $college
        ]);
    }

    #[Route(path: '/espcoll/message/{id}', name: 'op_webapp_message_show', methods: ['GET'])]
    public function show(Message $message, EntityManagerInterface $entityManager): Response
    {
        // code pour afficher la bannière de l'établissement en haut de page
        $user = $this->getUser();
        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);

        // code pour basculer le message en statut lu
        $read = $message->getIsRead();
        if ($read == 0) {
            $message->setIsRead(1);
            $entityManager->flush();

        }

        return $this->render('webapp/message/show.html.twig', [
            'message' => $message,
            'college' => $college
        ]);
    }

    #[Route(path: '/espcoll/message/{id}/edit', name: 'op_webapp_message_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('op_webapp_message_messagesbyuser', ['iduser' => $user->getId()]);
        }

        return $this->render('webapp/message/edit.html.twig', [
            'message' => $message,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/espcoll/message/{id}', name: 'op_webapp_message_delete', methods: ['DELETE'])]
    public function delete(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();
        }

        return $this->redirectToRoute('op_webapp_message_messagesbyuser', ['iduser' => $user->getId()]);
    }

    #[Route(path: '/espcoll/message/deletemessageview/{id}', name: 'op_webapp_message_deletemessageview', methods: ['POST', 'GET'])]
    public function delete_message_view(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        // suppression du message sélectionné
        $entityManager->remove($message);
        $entityManager->flush();
        // lister tous les messages appartenant à l'utilisateur courant
        return $this->redirectToRoute('op_webapp_message_messagesbyuser', ['iduser' => $user->getId()]);
    }

    #[Route(path: '/espcoll/message/messagesbyuser/{iduser}', name: 'op_webapp_message_messagesbyuser', methods: ['GET'])]
    public function listMessageByUser($iduser, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $college = $entityManager->getRepository(College::class)->CollegeByUser($user);


        $messages = $entityManager->getRepository(Message::class)->listMessagesByUser($iduser);

        return $this->render('webapp/message/index.html.twig',[
            'messages' => $messages,
            'college' => $college

        ]);
    }

    /**
     * Action de réponse à un mail
     */
    #[Route(path: '/espcoll/message/reply_mail/{id}', name: 'op_webapp_message_reply_mail', methods: ['GET', 'POST'])]
    public function reply_mail(Message $message, Request $request, EntityManagerInterface $em){
        $user = $this->getUser();
        $college = $em->getRepository(College::class)->CollegeByUser($user);

        $recipient = $message->getAuthor();
        $content = $message->getContent();
        $newcontent = $content."----------------------------";

        $reply = new Message();

        $reply
            ->setSubject("Rép : " . $message->getSubject())
            ->addRecipient($recipient)
            ->setContent($newcontent)
            ->setAuthor($user)
            ->setContent($message->getContent())
            ->setFollow($message->getFollow())
        ;

        $replyform = $this->createForm(ReplyType::class, $reply);
        $replyform->handleRequest($request);

        if ($replyform->isSubmitted() && $replyform->isValid()) {
            $em->persist($reply);
            $em->flush();

            return $this->redirectToRoute('op_webapp_message_messagesbyuser', ['iduser' => $user->getId()]);
        }

        return $this->render('webapp/message/reply_mail.html.twig',[
            'college'=> $college,
            'message'=> $message,
            'reply' => $reply,
            'replyform' => $replyform->createView()
        ]);
    }

    /**
     * Liste les réponses selon le "follow"
     */
    #[Route(path: '/espcoll/message/reply_mail/{follow}', name: 'op_webapp_message_reply_mail_follow')]
    public function followmessage($follow, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        $follows = $em->getRepository(Message::class)->findBy(['follow'=> $follow],['createAt' => 'DESC']);

        return $this->render('webapp/message/follows.html.twig', [
            'follows' => $follows,
            'user' =>$user
        ]);
    }

    /**
     * Action de réponse à un mail
     */
    #[Route(path: '/espcoll/message/trans_mail/{id}', name: 'op_webapp_message_trans_mail', methods: ['GET', 'POST'])]
    public function trans_mail(Message $message, Request $request, EntityManagerInterface $em){
        $user = $this->getUser();
        $college = $em->getRepository(College::class)->CollegeByUser($user);

        $trans = new Message();

        $trans
            ->setSubject("Trans : " . $message->getSubject())
            ->setAuthor($user)
            ->setContent($message->getContent())
            ->setFollow($message->getFollow())
        ;

        $transform = $this->createForm(ReplyType::class, $trans);
        $transform->handleRequest($request);

        if ($transform->isSubmitted() && $transform->isValid()) {
            $em->persist($trans);
            $em->flush();

            return $this->redirectToRoute('op_webapp_message_messagesbyuser', ['iduser' => $user->getId()]);
        }

        return $this->render('webapp/message/reply_mail.html.twig',[
            'college'=> $college,
            'message'=> $message,
            'trans' => $trans,
            'transform' => $transform->createView()
        ]);
    }

    /**
     * Supprimer les messages par Javascript depuis l'interface Espcoll
     */
    #[Route(path: '/espcoll/message/delete/{id}', name: 'op_webapp_message_delete', methods: ['POST'])]
    public function deleteMessage(Message $message, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $user = $this->getUser();

        $em->remove($message);
        $em->flush();

        $messages = $em->getRepository(Message::class)->listMessagesByUser($user->getId());

        return $this->json([
            'code'=> 200,
            'message' => "Le message a été correctement supprimé",
            'listeMessages' => $this->renderView('webapp/message/include/_listbyuser.html.twig', [
                'messages' => $messages,
            ]),
        ], 200);
    }
}
