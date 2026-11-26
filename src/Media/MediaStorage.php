<?php

declare(strict_types=1);

namespace App\Media;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Der gemeinsame Medienordner von hub (Volume hub_media_data, im Container /srv/app/var/media, lokal var/media).
 * Je Tool ein Unterordner: bet_ai/logos, fotokalender, advent, projekte.
 *
 * Pfade in der Datenbank sind relativ zu diesem Ordner („fotokalender/2022/januar/dsc_0685.jpg“) oder – bei
 * BetAI aus historischen Gründen – mit Präfix „/media/“. Beides versteht normalize().
 */
final readonly class MediaStorage
{
    public const string URL_PREFIX = '/media/';

    public function __construct(
        #[Autowire('%app.media_dir%')]
        private string $mediaDir,
    ) {
    }

    public function getRoot(): string
    {
        return rtrim($this->mediaDir, '/');
    }

    /**
     * Relativer Pfad ohne führenden Schrägstrich, oder null bei externen URLs und unzulässigen Pfaden
     * (.., Steuerzeichen, Backslash).
     */
    public static function normalize(?string $path): ?string
    {
        if (null === $path || '' === trim($path)) {
            return null;
        }
        $path = trim($path);
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) || str_starts_with($path, '//')) {
            return null;
        }
        if (str_starts_with($path, self::URL_PREFIX)) {
            $path = substr($path, \strlen(self::URL_PREFIX));
        }
        $path = ltrim($path, '/');

        if ('' === $path || str_contains($path, '\\') || preg_match('/[\x00-\x1f]/', $path)) {
            return null;
        }
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return null;
            }
        }

        return $path;
    }

    public static function isExternal(?string $path): bool
    {
        return null !== $path && (bool) preg_match('#^(https?:)?//#i', trim($path));
    }

    /**
     * Absoluter Pfad einer vorhandenen Datei im Medienordner, sonst null.
     */
    public function resolve(?string $path): ?string
    {
        $relative = self::normalize($path);
        if (null === $relative) {
            return null;
        }
        $absolute = $this->getRoot().'/'.$relative;

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * Unterordner für Uploads (EasyAdmin ImageField erwartet einen Pfad relativ zum Projektordner).
     */
    public function ensureDirectory(string $relativeDir): string
    {
        $dir = $this->getRoot().'/'.trim($relativeDir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException(\sprintf('Ordner %s lässt sich nicht anlegen.', $dir));
        }

        return $dir;
    }
}
