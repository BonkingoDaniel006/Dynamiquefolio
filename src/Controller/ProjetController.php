<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\ProjetMedia;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use App\Service\ProjetMediaStorage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projets', name: 'app_projet_')]
#[IsGranted('ROLE_USER')]
final class ProjetController extends AbstractController
{
    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProjetMediaStorage $mediaStorage,
        LoggerInterface $logger,
    ): Response {
        $projet = new Projet();
        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($projet);
            $entityManager->flush();
            $projet->setSlug($mediaStorage->createSlug($projet));

            try {
                $mediaStorage->ensureProjectDirectory($projet);
                foreach ($mediaStorage->storeUploads($projet, $form->get('fichiers')->getData() ?? []) as $media) {
                    $entityManager->persist($media);
                }
                $entityManager->flush();
            } catch (FileException|IOExceptionInterface|\RuntimeException $exception) {
                $logger->error('Impossible de stocker les médias du projet.', [
                    'project_id' => $projet->getId(),
                    'exception' => $exception,
                ]);
                $entityManager->remove($projet);
                $entityManager->flush();
                $mediaStorage->deleteProjectDirectory($projet);
                $form->addError(new FormError('Le projet n’a pas été enregistré : '.$exception->getMessage()));

                return $this->render('projet/form.html.twig', [
                    'form' => $form,
                    'projet' => $projet,
                    'pageTitle' => 'Ajouter un projet',
                ]);
            }

            $this->addFlash('success', 'Le projet a été créé.');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('projet/form.html.twig', [
            'form' => $form,
            'projet' => $projet,
            'pageTitle' => 'Ajouter un projet',
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Projet $projet,
        Request $request,
        EntityManagerInterface $entityManager,
        ProjetMediaStorage $mediaStorage,
        LoggerInterface $logger,
    ): Response {
        $oldSlug = $projet->getSlug();
        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newSlug = $mediaStorage->createSlug($projet);
            $directoryPrepared = false;
            $directoryRenamed = false;

            try {
                if ($newSlug !== $oldSlug) {
                    $projet->setSlug($newSlug);
                    $directoryRenamed = $mediaStorage->renameProjectDirectory($projet, $oldSlug);
                    $directoryPrepared = true;
                }
                foreach ($mediaStorage->storeUploads($projet, $form->get('fichiers')->getData() ?? []) as $media) {
                    $entityManager->persist($media);
                }
                $entityManager->flush();
            } catch (FileException|IOExceptionInterface|\RuntimeException $exception) {
                $logger->error('Impossible de mettre à jour le projet ou ses médias.', [
                    'project_id' => $projet->getId(),
                    'exception' => $exception,
                ]);
                if ($directoryPrepared && $projet->getSlug() !== $oldSlug) {
                    if ($directoryRenamed) {
                        $newSlug = $projet->getSlug();
                        $projet->setSlug($oldSlug);
                        $mediaStorage->renameProjectDirectory($projet, $newSlug);
                    } else {
                        $mediaStorage->deleteProjectDirectory($projet);
                        $projet->setSlug($oldSlug);
                    }
                    $entityManager->refresh($projet);
                } elseif ($projet->getSlug() !== $oldSlug) {
                    $projet->setSlug($oldSlug);
                }
                $form->addError(new FormError('Le projet n’a pas été mis à jour : '.$exception->getMessage()));

                return $this->render('projet/form.html.twig', [
                    'form' => $form,
                    'projet' => $projet,
                    'pageTitle' => 'Modifier le projet',
                ]);
            }

            $this->addFlash('success', 'Le projet a été mis à jour.');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('projet/form.html.twig', [
            'form' => $form,
            'projet' => $projet,
            'pageTitle' => 'Modifier le projet',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Projet $projet,
        Request $request,
        EntityManagerInterface $entityManager,
        ProjetMediaStorage $mediaStorage,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_project_'.$projet->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Le jeton de sécurité du projet est invalide.');
        }

        $user = $this->getUser();
        $password = $request->getPayload()->getString('password');
        if (!$user instanceof PasswordAuthenticatedUserInterface
            || !$passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Mot de passe incorrect. Le projet n’a pas été supprimé.');

            return $this->redirectToRoute('dashboard');
        }

        $entityManager->remove($projet);
        $entityManager->flush();
        $mediaStorage->deleteProjectDirectory($projet);
        $this->addFlash('success', 'Le projet et ses médias ont été supprimés.');

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/{id}/medias/{mediaId}/supprimer', name: 'media_delete', requirements: ['id' => '\d+', 'mediaId' => '\d+'], methods: ['POST'])]
    public function deleteMedia(
        Projet $projet,
        int $mediaId,
        Request $request,
        EntityManagerInterface $entityManager,
        ProjetMediaStorage $mediaStorage,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_project_media_'.$mediaId, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Le jeton de sécurité du média est invalide.');
        }

        $media = $projet->getMedias()->filter(
            static fn (ProjetMedia $item): bool => $item->getId() === $mediaId
        )->first();

        if (!$media instanceof ProjetMedia) {
            throw $this->createNotFoundException('Ce média n’appartient pas à ce projet.');
        }

        $mediaStorage->deleteMediaFile($media);
        $projet->removeMedia($media);
        $entityManager->remove($media);
        $entityManager->flush();
        $this->addFlash('success', 'Le média a été supprimé.');

        return $this->redirectToRoute('app_projet_edit', ['id' => $projet->getId()]);
    }
}
