<?php

namespace App\Controller\Admin;

use App\Entity\Admin\User;
use App\Form\Admin\ResettingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class resettingController
 * @package App\Controller\Admin
 */
class resettingController extends AbstractController
{
    #[Route(path: '/security/resetting/{id}', name: 'op_admin_security_resetting')]
    public function resetting(User $user, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)
    {

        $form = $this->createForm(ResettingType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $password = $userPasswordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($password);

            if($user->IsVerified() == false)
            {
                $user->setIsVerified(true);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $request->getSession()->getFlashBag()->add('success', "Votre mot de passe a été renouvelé.");

            return $this->redirectToRoute('op_webapp_espcoll', [
                'iduser' => $user->getId(),
            ]);

        }

        return $this->render('admin/resetting/request.html.twig', [
            'form' => $form->createView(),
            'user' => $user

        ]);

    }
}
