<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $email = '';
        $password = '';
        $confirmPassword = '';
        $emailError = null;
        $passwordError = null;
        $confirmError = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Email exists check
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $emailError = 'Email already in use.';
            }

            // Password validation
            $passwordValid = true;
            if (strlen($password) < 6) {
                $passwordValid = false;
                $passwordError = 'Password must be at least 6 characters.';
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $passwordValid = false;
                $passwordError = 'Password must contain at least one uppercase letter.';
            } elseif (!preg_match('/[a-z]/', $password)) {
                $passwordValid = false;
                $passwordError = 'Password must contain at least one lowercase letter.';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $passwordValid = false;
                $passwordError = 'Password must contain at least one number.';
            } elseif (!preg_match('/[\W]/', $password)) {
                $passwordValid = false;
                $passwordError = 'Password must contain at least one special character.';
            }

            // Confirm password
            if ($password !== $confirmPassword) {
                $confirmError = 'Passwords do not match.';
            }

            // If all valid, create user
            if (!$emailError && !$passwordError && !$confirmError) {
                $user = new User();
                $user->setEmail($email);
                $user->setPassword($hasher->hashPassword($user, $password));
                $user->setRoles(['ROLE_ADMIN']);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Account created successfully');
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('register/register.html.twig', [
            'email' => $email,
            'password' => $password,
            'confirmPassword' => $confirmPassword,
            'emailError' => $emailError,
            'passwordError' => $passwordError,
            'confirmError' => $confirmError,
        ]);
    }

}
