<?php

namespace App\Controller;

use App\Entity\ProjetMedia;
use App\Service\ProjetMediaStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class ProjetMediaController extends AbstractController
{
    #[Route('/projet-media/{id}', name: 'app_projet_media', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(ProjetMedia $media, ProjetMediaStorage $mediaStorage): BinaryFileResponse
    {
        $path = $mediaStorage->getAbsoluteMediaPath($media);
        if (!is_file($path)) {
            throw $this->createNotFoundException('Le fichier média est introuvable.');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $media->getTypeMime());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $media->getNomOriginal() ?? $media->getNomFichier(),
        );
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
