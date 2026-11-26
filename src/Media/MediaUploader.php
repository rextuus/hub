<?php

declare(strict_types=1);

namespace App\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Legt hochgeladene Bilder im Medienordner ab (zufälliger Name, Unterordner je Tool) und gibt den Pfad relativ
 * zum Medienordner zurück. Für eigene Upload-Formulare; EasyAdmin-Felder nutzen MediaImageField.
 */
final readonly class MediaUploader
{
    public const array IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    public const int MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(
        private MediaStorage $storage,
    ) {
    }

    /**
     * @throws \InvalidArgumentException mit deutscher Meldung, wenn die Datei kein zulässiges Bild ist
     */
    public function storeImage(UploadedFile $file, string $directory): string
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException(\sprintf('„%s“: %s', $file->getClientOriginalName(), $file->getErrorMessage()));
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException(\sprintf('„%s“ ist größer als 20 MB.', $file->getClientOriginalName()));
        }
        $extension = self::IMAGE_TYPES[(string) $file->getMimeType()] ?? null;
        if (null === $extension || false === @getimagesize($file->getPathname())) {
            throw new \InvalidArgumentException(\sprintf('„%s“ ist kein JPG, PNG, WebP oder GIF.', $file->getClientOriginalName()));
        }

        $relativeDir = trim($directory, '/');
        if (null === MediaStorage::normalize($relativeDir)) {
            throw new \InvalidArgumentException('Ungültiger Zielordner.');
        }
        $name = bin2hex(random_bytes(16)).'.'.$extension;
        $file->move($this->storage->ensureDirectory($relativeDir), $name);

        return $relativeDir.'/'.$name;
    }
}
