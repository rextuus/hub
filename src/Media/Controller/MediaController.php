<?php

declare(strict_types=1);

namespace App\Media\Controller;

use App\Media\Imgproxy\ImgproxyUrlSigner;
use App\Media\MediaStorage;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Originale aus dem Medienordner über PHP (mit Range-Anfragen, z. B. für Videos).
 *
 * - Öffentliche Ordner (Startseite, Fotokalender, Team-Logos) immer.
 * - Alles andere (vor allem advent/: Bilder zu Antworten, die noch nicht dran sind) nur lokal ohne imgproxy oder
 *   für Admins. In Produktion sehen Teilnehmer diese Bilder nur über signierte imgproxy-URLs, die erst nach dem
 *   Öffnen einer Tür in der Seite stehen.
 */
final readonly class MediaController
{
    public const array PUBLIC_DIRECTORIES = ['projekte/', 'fotokalender/', 'bet_ai/'];

    public function __construct(
        private MediaStorage $storage,
        private ImgproxyUrlSigner $imgproxy,
        private Security $security,
    ) {
    }

    #[Route('/media/{path}', name: 'app_media_original', requirements: ['path' => '.+'], methods: ['GET', 'HEAD'])]
    public function __invoke(string $path): BinaryFileResponse
    {
        $relative = MediaStorage::normalize($path);
        $file = $this->storage->resolve($relative);
        if (null === $relative || null === $file) {
            throw new NotFoundHttpException();
        }

        $public = array_any(self::PUBLIC_DIRECTORIES, static fn (string $dir): bool => str_starts_with($relative, $dir));
        if (!$public && $this->imgproxy->isEnabled() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($file, 200, [], $public, ResponseHeaderBag::DISPOSITION_INLINE, true, true);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if ($public) {
            $response->setPublic();
            $response->setMaxAge(604800);
        } else {
            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-store');
        }

        return $response;
    }
}
