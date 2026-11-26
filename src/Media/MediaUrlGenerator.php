<?php

declare(strict_types=1);

namespace App\Media;

use App\Media\Imgproxy\ImgproxyUrlSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Bild-URLs für den Medienordner. Mit imgproxy (Produktion): signierte Pfade /img/<signatur>/rs:…/<quelle>.webp in
 * der Größe des Presets, ohne Metadaten. Ohne Schlüssel (lokal): das Original über MediaController.
 * Externe URLs (alte BetAI-Logos o. ä.) kommen unverändert zurück.
 */
final readonly class MediaUrlGenerator
{
    public const string IMGPROXY_PREFIX = '/img';

    /**
     * Breiten je Preset; „ratio“ = Höhe/Breite für zugeschnittene Bilder (fill), sonst wird nur eingepasst (fit).
     *
     * @var array<string, array{widths: list<int>, ratio?: float, quality?: int}>
     */
    public const array PRESETS = [
        'logo' => ['widths' => [96, 192], 'ratio' => 1.0],
        'admin' => ['widths' => [240]],
        // Startseite: hohe Streifen (Desktop) bzw. breite Bänder (Handy)
        'tile' => ['widths' => [480, 960, 1440]],
        'thumb' => ['widths' => [320, 640], 'ratio' => 0.75],
        'card' => ['widths' => [480, 800, 1200], 'ratio' => 1.35],
        'photo' => ['widths' => [960, 1600, 2400], 'quality' => 85],
        'advent' => ['widths' => [480, 960, 1440]],
    ];

    public function __construct(
        private ImgproxyUrlSigner $imgproxy,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function url(?string $path, string $preset, ?int $width = null): string
    {
        if (null === $path || '' === trim($path)) {
            return '';
        }
        $relative = MediaStorage::normalize($path);
        if (null === $relative) {
            return MediaStorage::isExternal($path) ? trim($path) : '';
        }

        if (!$this->imgproxy->isEnabled()) {
            return $this->original($relative);
        }

        $config = self::preset($preset);
        $width ??= $config['widths'][0];
        $options = isset($config['ratio'])
            ? [\sprintf('rs:fill:%d:%d', $width, (int) round($width * $config['ratio'])), 'g:sm']
            : [\sprintf('rs:fit:%d:%d', $width, $width)];
        $options[] = 'q:'.($config['quality'] ?? 82);

        return self::IMGPROXY_PREFIX.$this->imgproxy->sign('local:///'.$relative, $options, 'webp');
    }

    /**
     * srcset mit allen Breiten des Presets; ohne imgproxy nur das Original (mehr gibt es dann nicht).
     */
    public function srcset(?string $path, string $preset): string
    {
        $relative = MediaStorage::normalize($path);
        if (null === $relative || !$this->imgproxy->isEnabled()) {
            return '';
        }

        return implode(', ', array_map(
            fn (int $w): string => $this->url($relative, $preset, $w).' '.$w.'w',
            self::preset($preset)['widths'],
        ));
    }

    /**
     * Das Original (ungerechnet) – für Videos, GIFs und als Rückfall ohne imgproxy.
     */
    public function original(?string $path): string
    {
        $relative = MediaStorage::normalize($path);
        if (null === $relative) {
            return MediaStorage::isExternal($path) ? trim((string) $path) : '';
        }

        return $this->urlGenerator->generate('app_media_original', ['path' => $relative]);
    }

    /**
     * @return array{widths: list<int>, ratio?: float, quality?: int}
     */
    private static function preset(string $preset): array
    {
        return self::PRESETS[$preset] ?? throw new \InvalidArgumentException(\sprintf('Unbekanntes Bild-Preset „%s“.', $preset));
    }
}
