<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect to admin
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    public function logout(): void
    {
        throw new \LogicException('This will be intercepted by Symfony logout.');
    }
}
