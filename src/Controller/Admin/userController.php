<?php

namespace App\Controller\Admin;

use App\Controller\Webapp\ArticleController;
use App\Entity\Admin\User;
use App\Entity\Webapp\Article;
use App\Form\Admin\userEditType;
use App\Form\Admin\userType;
use App\Repository\Admin\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class userController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordEncoder)
    {}

    #[Route(path: '/admin/user/', name: 'op_admin_user_index', methods: ['GET'])]
    public function index(userRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $data = $userRepository->indexCollegesOnly();
        $users = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            15
        );
        return $this->render('admin/user/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route(path: '/admin/user/new', name: 'op_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = new user();
        $form = $this->createForm(userType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstname = $form->get("firstName")->getData();
            $lastname = $form->get("lastName")->getData();

            // ---------------------------
            // STEP 1 : insertion de l'image dans le dossier public/uploads/articles'
            // ---------------------------
            $imageFile = $form->get('avatarFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo((string) $imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('user_directory'),
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $user->setAvatarName($newFilename);
            }


            $hash = $this->passwordEncoder->hashPassword($user, $user->getPassword());
            $user->setLoginName($firstname ." ". $lastname);
            $user->setPassword($hash);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('op_admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/user/{id}', name: 'op_admin_user_show', methods: ['GET'])]
    public function show(user $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route(path: '/admin/user/{id}/edit', name: 'op_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, user $user, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(userType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstname = $form->get("firstName")->getData();
            $lastname = $form->get("lastName")->getData();
            // ---------------------------
            // STEP 2 : insertion de l'image dans le dossier public/uploads/articles'
            // ---------------------------
            $imageFile = $form->get('avatarFile')->getData();
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
                $user->setAvatarName($newFilename);
            }
            $user->setLoginName($firstname ." ". $lastname);
            $entityManager->flush();

            return $this->redirectToRoute('op_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/user/{id}', name: 'op_admin_user_delete', methods: ['DELETE'])]
    public function delete(Request $request, user $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('op_admin_user_index');
    }

    /**
     * Permet de mettre en menu la poge ou non
     */
    #[Route(path: '/admin/user/op_admin/user/actived/{id}', name: 'op_admin_user_actived')]
    public function jsVerified(User $user, EntityManagerInterface $em) : Response
    {
        $admin = $this->getUser();
        $isActiv = $user->getIsActiv();
        // renvoie une erreur car l'utilisateur n'est pas connecté
        if(!$admin) return $this->json([
            'code' => 403,
            'message'=> "Vous n'êtes pas connecté"
        ], 403);
        // Si la page est déja publiée, alors on dépublie
        if($isActiv == true){
            $user->setIsActiv(0);
            $em->flush();
            return $this->json(['code'=> 200, 'message' => "L'utilisateur n'accède plus à l'administration"], 200);
        }
        // Si la page est déja dépubliée, alors on publie
        $user->setIsActiv(1);
        $em->flush();
        return $this->json([
            'code'=> 200,
            'message' => "L'utilisateur accède à l'administration"],
            200);
    }

    /**
     * Suppression d'une ligne index.php
     */
    #[Route(path: '/admin/user/del/{id}/{page}', name: 'op_admin_user_del', methods: ['POST'])]
    public function Del(User $user, $page, PaginatorInterface $paginator, EntityManagerInterface $entityManager, Request $request) : Response
    {
        $admin = $this->getUser();
        // Migration des articles liés à l'user
        $articles = $user->getArticles();

        if($articles){
            foreach($articles as $article){
                $article->setAuthor($admin);
            }
        }

        // Désaffectation du college lié
        $college = $user->getCollege();
        if ($college) {
            $college->setUser(null);
        }

        // Désaffectation du college lié
        $pages = $user->getPages();
        if ($pages) {
            foreach ($pages as $page) {
                $page->setAuthor($admin);
            }
        }

        $comments = $user->getComments();
        if($comments){
            foreach($comments as $comment){
                $entityManager->remove($comment);
            }
        }

        $messages = $user->getMessages();
        if($messages){
            foreach($messages as $message){
                $entityManager->remove($message);
            }
        }

        $entityManager->flush();

        // supression de l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        $data = $entityManager->getRepository(User::class)->findAll();

        $users = $paginator->paginate(
            $data,
            $request->query->getInt('page', $page),
            15
        );

        return $this->json([
            'code'=> 200,
            'message' => "L'utilisateur a été supprimé",
            'liste' => $this->renderView('admin/user/include/_liste.html.twig', [
                'users' => $users
            ])
        ], 200);
    }
}
