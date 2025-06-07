<?php

namespace App\Controller;

use App\Entity\DeviceAssignment;
use App\Entity\User;
use App\Form\UserTypeForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/utilisateurs', name: 'app_users')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', ['users' => $users]);
    }

    #[Route('/utilisateur/{id}/edit', name: 'app_user_edit')]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserTypeForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('user_image')->getData();

            if ($imageFile) {
                $uploadsDirectory = $this->getParameter('uploads_directory');

                if ($user->getUserImage()) {
                    $oldImagePath = $uploadsDirectory . '/' . $user->getUserImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the uploads directory
                $imageFile->move($uploadsDirectory, $newFilename);

                // Set new filename to user entity
                $user->setUserImage($newFilename);
            }

            // save changes
            $entityManager->flush();

            $this->addFlash('success', "L'utilisateur a été mis à jour avec succès");
            return $this->redirectToRoute('app_users');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/utilisateur/{id}/delete', name: 'app_user_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $deviceAssignments = $em->getRepository(DeviceAssignment::class)
            ->findBy(['user' => $user]);

        foreach ($deviceAssignments as $assignment) {
            $em->remove($assignment);
        }

        //$em->flush();

        // delete the user
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé et appareils non attribués !');

        return $this->redirectToRoute('app_users');
    }

    #[Route('/utilisateur/ajouter', name: 'app_user_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        $form = $this->createForm(UserTypeForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('user_image')->getData();

            if ($imageFile) {
                $uploadsDirectory = $this->getParameter('uploads_directory');
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move($uploadsDirectory, $newFilename);

                $user->setUserImage($newFilename);
            }

            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', "Nouvel utilisateur créé avec succès");

            return $this->redirectToRoute('app_users');
        }

        return $this->render('user/ajouter.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
