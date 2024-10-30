<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class UserController extends AbstractController
{
    
    #[Route('/user-panel', name: 'app_user_panel')]
    public function userPanel(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/userPanel.html.twig', ['user' => $user]);
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($newPassword !== null && $confirmPassword !== null && $newPassword === $confirmPassword && !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->flush();

                $this->addFlash('success', 'Hasło zostało pomyślnie zmienione.');

                return $this->redirectToRoute('app_user_panel');
            } elseif ($passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Nowe hasło nie może być takie samo jak aktualne.');
            } else {
                $this->addFlash('error', 'Aktualne hasło jest nieprawidłowe.');
            }
        }

        return $this->redirectToRoute('app_user_panel');
    }


    #[Route('/change-email', name: 'app_change_email')]
    public function changeEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $newEmail = $request->request->get('newEmail');

            // Sprawdź, czy nowy adres e-mail jest różny od aktualnego
            if ($newEmail !== $user->getEmail()) {
                $user->setEmail($newEmail);
                $entityManager->flush();

                $this->addFlash('success', 'Adres e-mail został pomyślnie zmieniony.');

                return $this->redirectToRoute('app_user_panel');
            } else {
                $this->addFlash('error', 'Nowy adres e-mail nie może być taki sam jak aktualny.');

                // Możesz dodać inne obsługi błędów, np. gdy nowy adres e-mail jest nieprawidłowy
            }
        }

        return $this->redirectToRoute('app_user_panel');
    }
    
    
    #[Route('/user-search', name: 'app_user_search')]
    public function search(Request $request, EntityManagerInterface $em): Response
    {
        // Sprawdzenie, czy użytkownik jest zalogowany
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login'); // Przekieruj do logowania, jeśli nie jest zalogowany
        }
        $query = $request->query->get('query');
        $users = [];
        
            // Wyszukiwanie użytkowników w bazie danych
        if ($query) {
            $users = $em->getRepository(User::class)->createQueryBuilder('u')
                ->where('u.email LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->getQuery()
                ->getResult();
        }
        
        return $this->render('user/userSearch.html.twig', ['users' => $users]);
    }

}
