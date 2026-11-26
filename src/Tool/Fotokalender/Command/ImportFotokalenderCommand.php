<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Command;

use App\Media\MediaStorage;
use App\Tool\Fotokalender\Entity\PhotoCalendar;
use App\Tool\Fotokalender\Entity\PhotoCalendarMonth;
use App\Tool\Fotokalender\Entity\PhotoCalendarPhoto;
use App\Tool\Fotokalender\Model\Month;
use App\Tool\Fotokalender\Repository\PhotoCalendarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Legt einen Foto-Kalender aus Ordnern im Medienordner an: fotokalender/<jahr>/<monat>/ (januar, februar, maerz …),
 * darin titel.jpg als Titelbild und beliebig viele weitere Fotos (alphabetisch). Monate und Fotos, die es schon
 * gibt (gleicher Pfad), bleiben unverändert – der Befehl lässt sich also gefahrlos wiederholen.
 * Ohne --mottos werden die des Kalenders 2022 verwendet.
 */
#[AsCommand(name: 'app:fotokalender:import', description: 'Foto-Kalender aus dem Medienordner (fotokalender/<jahr>/<monat>/) anlegen')]
final class ImportFotokalenderCommand
{
    public const array MOTTOS_2022 = [
        1 => 'Schlafmütze', 2 => 'Bildschirm', 3 => 'Berufe', 4 => 'Zirkus', 5 => 'Regenbogen',
        6 => 'Zeitreise', 7 => 'Urlaub', 8 => 'Sportler', 9 => 'Märchen',
    ];

    public function __construct(
        private readonly MediaStorage $storage,
        private readonly PhotoCalendarRepository $calendars,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Jahr (Ordner fotokalender/<jahr>)')] int $year = 2022,
        #[Option('Titel des Kalenders')] string $title = 'Foto-Kalender 2022',
    ): int {
        $root = $this->storage->getRoot().'/fotokalender/'.$year;
        if (!is_dir($root)) {
            $io->error(\sprintf('Ordner %s fehlt. Erst die Fotos in den Medienordner kopieren (docs/deployment.md).', $root));

            return Command::FAILURE;
        }

        $calendar = $this->calendars->findOneBy(['year' => $year]) ?? new PhotoCalendar($year, $title);
        $this->em->persist($calendar);

        $rows = [];
        foreach (Month::SLUGS as $number => $slug) {
            $dir = $root.'/'.$slug;
            if (!is_dir($dir)) {
                continue;
            }
            $sheet = $calendar->getMonth($number);
            if (null === $sheet) {
                $sheet = (new PhotoCalendarMonth())->setMonth($number)->setMotto(self::MOTTOS_2022[$number] ?? Month::name($number));
                $calendar->addMonth($sheet);
            }
            $relativeDir = \sprintf('fotokalender/%d/%s', $year, $slug);
            if (null === $sheet->getCover() && is_file($dir.'/titel.jpg')) {
                $sheet->setCover($relativeDir.'/titel.jpg');
            }

            $known = array_map(static fn (PhotoCalendarPhoto $p): ?string => $p->getImage(), $sheet->getPhotos()->toArray());
            $added = 0;
            $position = \count($known) * 10;
            $files = (new Finder())->files()->in($dir)->depth(0)->name('/\.(jpe?g|png|webp)$/i')->notName('titel.jpg')->sortByName();
            foreach ($files as $file) {
                $path = $relativeDir.'/'.$file->getFilename();
                if (\in_array($path, $known, true)) {
                    continue;
                }
                $sheet->addPhoto((new PhotoCalendarPhoto())->setImage($path)->setPosition($position += 10));
                ++$added;
            }
            $rows[] = [Month::name($number), $sheet->getMotto(), $sheet->getCover() ? 'ja' : 'nein', $added];
        }

        $this->em->flush();
        $io->table(['Monat', 'Motto', 'Titelbild', 'neue Fotos'], $rows);
        $io->success(\sprintf('%s: %d Monatsblätter.', $calendar, $calendar->getMonths()->count()));

        return Command::SUCCESS;
    }
}
