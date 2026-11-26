<?php

declare(strict_types=1);

namespace App\Media\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

/**
 * Upload-Feld für EasyAdmin in den gemeinsamen Medienordner (var/media, Volume hub_media_data).
 * Gespeichert wird der Pfad relativ zum Medienordner („advent/3f9c…e1.jpg“), angezeigt wird über imgproxy
 * (templates/admin/fields/media_image.html.twig). Dateinamen sind zufällig: nicht erratbar, und eine neue Datei
 * bekommt eine neue URL (lange Cache-Zeiten von imgproxy bleiben richtig).
 */
final class MediaImageField
{
    public const string UPLOAD_DIR = 'var/media';

    public static function new(string $property, string $label, string $directory, int $maxMegabytes = 20): ImageField
    {
        return ImageField::new($property, $label)
            ->setUploadDir(self::UPLOAD_DIR)
            ->setUploadedFileNamePattern(trim($directory, '/').'/[randomhash].[extension]')
            ->setFileConstraints(new Image(
                maxSize: $maxMegabytes.'M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                maxPixels: 50_000_000,
            ))
            ->setTemplatePath('admin/fields/media_image.html.twig');
    }

    /**
     * Kurzes Video (MP4/WebM), z. B. die bewegte Kachel „About“ auf der Startseite. Kein imgproxy, PHP liefert es aus.
     */
    public static function video(string $property, string $label, string $directory, int $maxMegabytes = 20): ImageField
    {
        return ImageField::new($property, $label)
            ->setUploadDir(self::UPLOAD_DIR)
            ->setUploadedFileNamePattern(trim($directory, '/').'/[randomhash].[extension]')
            ->setFileConstraints(new File(maxSize: $maxMegabytes.'M', mimeTypes: ['video/mp4', 'video/webm']))
            ->setTemplatePath('admin/fields/media_video.html.twig');
    }
}
