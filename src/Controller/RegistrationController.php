<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, AppAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/adduser', name: 'app_add_user')]
    public function addUser(Request $rq, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted("ROLE_ADMIN")) exit('Рано тебе пока новых регестрировать');

        $data = $rq->request;
        $pass = $data->get('pass');
        $login = $data->get('login');
        $rate = $data->get('rate');

        if (!$pass || !$login || !$rate) return $this->redirectToRoute('app_admin');
        $user = new User();

        $user->setPassword($passwordHasher->hashPassword($user, $pass))
            ->setTelegram($login)
            ->setRate($rate)
            ->setRoles(["APP_USER"]);
        $em->persist($user);
        $em->flush();
        $this->addFlash('success', "Пользователь " . $login . " зарегестрирован, пароль: " . $pass);

        return $this->redirectToRoute("app_admin");
    }
}
