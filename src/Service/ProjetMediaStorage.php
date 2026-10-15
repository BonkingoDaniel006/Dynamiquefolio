<?php

namespace App\Service;

use App\Entity\Projet;
use App\Entity\ProjetMedia;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProjetMediaStorage
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
    ];

    private readonly string $mediaRoot;

    public function __construct(
        string $projectDir,
        private readonly SluggerInterface $slugger,
        private readonly Filesystem $filesystem,
    ) {
        $this->mediaRoot = $projectDir.'/medias';
    }

    public function createSlug(Projet $projet): string
    {
        $slug = $this->slugger->slug((string) $projet->getTitre())->lower()->toString();

        return sprintf('%s-%d', $slug !== '' ? $slug : 'projet', $projet->getId());
    }

    public function ensureProjectDirectory(Projet $projet): void
    {
        $this->filesystem->mkdir($this->getProjectDirectory($projet));
    }

    public function renameProjectDirectory(Projet $projet, string $oldSlug): bool
    {
        $oldDirectory = $this->mediaRoot.'/'.$oldSlug;
        $newDirectory = $this->getProjectDirectory($projet);

        if ($oldDirectory === $newDirectory) {
            return false;
        }

        if (!is_dir($oldDirectory)) {
            if (file_exists($newDirectory)) {
                throw new \RuntimeException('Le dossier de destination des médias existe déjà.');
            }
            $this->filesystem->mkdir($newDirectory);

            return false;
        }

        if (file_exists($newDirectory)) {
            throw new \RuntimeException('Le dossier de destination des médias existe déjà.');
        }

        $this->filesystem->rename($oldDirectory, $newDirectory);

        return true;
    }

    /**
     * @param list<UploadedFile> $uploads
     *
     * @return list<ProjetMedia>
     */
    public function storeUploads(Projet $projet, array $uploads): array
    {
        $this->ensureProjectDirectory($projet);
        $storedPaths = [];
        $medias = [];
        $position = $projet->getMedias()->count();

        try {
            foreach ($uploads as $upload) {
                if (!$upload->isValid()) {
                    throw new FileException('Un des fichiers envoyés est invalide.');
                }

                $mimeType = $upload->getMimeType() ?? '';
                $extension = self::MIME_EXTENSIONS[$mimeType] ?? null;
                if ($extension === null) {
                    throw new FileException('Le format d’un fichier envoyé n’est pas pris en charge.');
                }

                $fileName = bin2hex(random_bytes(16)).'.'.$extension;
                $storedFile = $upload->move($this->getProjectDirectory($projet), $fileName);
                $storedPaths[] = $storedFile->getPathname();

                $media = (new ProjetMedia())
                    ->setNomFichier($fileName)
                    ->setNomOriginal($upload->getClientOriginalName())
                    ->setTypeMime($mimeType)
                    ->setPosition($position++);

                $projet->addMedia($media);
                $medias[] = $media;
            }
        } catch (\Throwable $exception) {
            $this->filesystem->remove($storedPaths);
            throw $exception;
        }

        return $medias;
    }

    public function deleteMediaFile(ProjetMedia $media): void
    {
        $projet = $media->getProjet();
        if ($projet === null || $media->getNomFichier() === null) {
            throw new \RuntimeException('Le média ne possède pas de chemin de stockage valide.');
        }

        $path = $this->getProjectDirectory($projet).'/'.$media->getNomFichier();
        if (!is_file($path)) {
            throw new \RuntimeException('Le fichier média est introuvable sur le disque.');
        }

        $this->filesystem->remove($path);
    }

    public function deleteProjectDirectory(Projet $projet): void
    {
        $this->filesystem->remove($this->getProjectDirectory($projet));
    }

    public function getAbsoluteMediaPath(ProjetMedia $media): string
    {
        $projet = $media->getProjet();
        if ($projet === null || $media->getNomFichier() === null) {
            throw new \RuntimeException('Le média ne possède pas de chemin de stockage valide.');
        }

        return $this->getProjectDirectory($projet).'/'.$media->getNomFichier();
    }

    private function getProjectDirectory(Projet $projet): string
    {
        if ($projet->getSlug() === '' || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $projet->getSlug()) !== 1) {
            throw new \RuntimeException('Le nom de dossier du projet n’est pas valide.');
        }

        return $this->mediaRoot.'/'.$projet->getSlug();
    }
}
