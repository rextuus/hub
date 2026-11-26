<?php

declare(strict_types=1);

namespace App\Media\Twig;

use App\Media\MediaUrlGenerator;
use Twig\Attribute\AsTwigFunction;

/**
 * {{ media_url(pfad, 'preset') }}, {{ media_srcset(pfad, 'preset') }}, {{ media_original(pfad) }}
 * – Presets in MediaUrlGenerator::PRESETS.
 */
final readonly class MediaExtension
{
    public function __construct(
        private MediaUrlGenerator $urls,
    ) {
    }

    #[AsTwigFunction('media_url')]
    public function url(?string $path, string $preset, ?int $width = null): string
    {
        return $this->urls->url($path, $preset, $width);
    }

    #[AsTwigFunction('media_srcset')]
    public function srcset(?string $path, string $preset): string
    {
        return $this->urls->srcset($path, $preset);
    }

    #[AsTwigFunction('media_original')]
    public function original(?string $path): string
    {
        return $this->urls->original($path);
    }
}
