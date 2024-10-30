<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Form\ArtworkFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\ArtworkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Imagine\Gd\Imagine; // Użycie Imagine z GD
use Imagine\Image\Box;
use App\Entity\User;


class ProfileController extends AbstractController
{
    private $entityManager;
    private $artworkRepository;
    private $artworksDirectory;

    public function __construct(EntityManagerInterface $entityManager, ArtworkRepository $artworkRepository, string $artworksDirectory)
    {
        $this->entityManager = $entityManager;
        $this->artworkRepository = $artworkRepository;
        $this->artworksDirectory = $artworksDirectory;
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(UserInterface $user): Response
    {
        $artworks = $this->artworkRepository->findBy(['user' => $user]);

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'artworks' => $artworks,
        ]);
    }

    #[Route('/profile/add-artwork', name: 'app_profile_add_artwork')]
    public function addArtwork(Request $request, UserInterface $user): Response
    {
        $artwork = new Artwork();
        $form = $this->createForm(ArtworkFormType::class, $artwork);
        $form->handleRequest($request);

        
        
        if ($form->isSubmitted() && $form->isValid()) {
            $artwork->setUser($user);

            $imageFile = $form->get('artworkImage')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->artworksDirectory . '/fulls',
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Nie udało się załadować obrazu.');
                    return $this->redirectToRoute('app_profile'); 
                }

                // Sprawdzenie i utworzenie katalogu "thumbs" jeśli nie istnieje
                $thumbsDirectory = $this->artworksDirectory . '/thumbs';
                if (!is_dir($thumbsDirectory)) {
                    mkdir($thumbsDirectory, 0755, true);
                }

                // Zapisanie miniaturki przy użyciu Imagine
                $imagine = new Imagine();
                $image = $imagine->open($this->artworksDirectory . '/fulls/' . $newFilename);
                $image->resize(new Box(360, 225))
                      ->save($thumbsDirectory . '/' . $newFilename);
                
                $artwork->setArtworkImage($newFilename);
            }

            $this->entityManager->persist($artwork);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/addArtwork.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/delete-artwork', name: 'app_profile_delete_artwork')]
    public function deleteArtworkList(UserInterface $user): Response
    {
        $artworks = $this->artworkRepository->findBy(['user' => $user]);

        return $this->render('profile/deleteArtwork.html.twig', [
            'artworks' => $artworks,
        ]);
    }

    #[Route('/profile/delete-artwork/{id}', name: 'app_profile_delete_artwork_confirm')]
    public function deleteArtwork(Artwork $artwork): Response
    {
        // Usunięcie oryginalnego obrazu
        $originalFilename = $artwork->getArtworkImage();
        $fullImagePath = $this->artworksDirectory . '/fulls/' . $originalFilename;
        if (file_exists($fullImagePath)) {
            unlink($fullImagePath);
        }

        // Usunięcie miniaturki
        $thumbImagePath = $this->artworksDirectory . '/thumbs/' . $originalFilename;
        if (file_exists($thumbImagePath)) {
            unlink($thumbImagePath);
        }

        $this->entityManager->remove($artwork);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_profile_delete_artwork');
    }
    
    #[Route('/profile/{id}', name: 'app_profile_search')]
    public function viewProfile(int $id, EntityManagerInterface $em): Response
    {
    // Sprawdzenie, czy użytkownik jest zalogowany
    if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
        return $this->redirectToRoute('app_login');
    }

    // Znajdź użytkownika według ID
    $user = $em->getRepository(User::class)->find($id);
    if (!$user) {
        throw $this->createNotFoundException('Użytkownik nie został znaleziony.');
    }

    // Pobierz dzieła użytkownika
    $artworks = $em->getRepository(Artwork::class)->findBy(['user' => $user]);

    return $this->render('user/profileUser.html.twig', [
        'user' => $user,
        'artworks' => $artworks,
    ]);
    }
}
