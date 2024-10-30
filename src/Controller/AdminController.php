<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin', name: 'app_admin_')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Tylko administrator może zobaczyć ten panel
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/users', name: 'users_list')]
    public function userList(UserRepository $userRepository): Response
    {
        // Tylko administrator może zobaczyć tę stronę
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        return $this->render('admin/usersList.html.twig', [
            'users' => $users,
        ]);
    }
    
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    #[Route('/users/reset-password/{id}', name: 'reset_password')]
    public function resetPassword(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');

            // Ustaw nowe hasło dla użytkownika
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setPasswordResetRequired(true); // flaga zmiany hasla (niezaimplementowane)
            $entityManager->flush();

            $this->addFlash('success', 'Hasło zostało pomyślnie ustawione.');

            return $this->redirectToRoute('app_admin_users_list');
        }

        return $this->render('admin/resetPassword.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/users/roles/{id}', name: 'manage_roles')]
    public function manageRoles(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $roles = $request->request->all('roles');
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            $user->setRoles($roles);
            $entityManager->flush();


            return $this->redirectToRoute('app_admin_users_list');
        }

        return $this->render('admin/manageRoles.html.twig', [
            'user' => $user,
        ]);
    }
}
