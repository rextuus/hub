<?php

declare(strict_types=1);

namespace App\Tool\Advent\Command;

use App\Tool\Advent\Entity\AdventCalendar;
use App\Tool\Advent\Entity\AdventChoice;
use App\Tool\Advent\Entity\AdventDoor;
use App\Tool\Advent\Entity\AdventParticipant;
use App\Tool\Advent\Entity\AdventWinStage;
use App\Tool\Advent\Repository\AdventCalendarRepository;
use App\Tool\Advent\Service\AdventLinks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Legt einen Adventskalender aus einer YAML-Datei an bzw. aktualisiert ihn (gleicher Titel + Jahr): Türen nach
 * Nummer, Gewinnstufen und Teilnehmer nach Namen. Bestehende Antworten von Teilnehmern bleiben erhalten.
 * Aufbau: docs/advent/beispiel.yaml. Bilder sind Pfade im Medienordner (advent/…).
 */
#[AsCommand(name: 'app:advent:import', description: 'Adventskalender aus YAML anlegen/aktualisieren (Beispiel: docs/advent/beispiel.yaml)')]
final class ImportAdventCommand
{
    public function __construct(
        private readonly AdventCalendarRepository $calendars,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly AdventLinks $links,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('YAML-Datei')] string $file,
        #[Option('Nur prüfen, nichts speichern')] bool $dryRun = false,
    ): int {
        if (!is_file($file)) {
            $io->error('Datei nicht gefunden: '.$file);

            return Command::FAILURE;
        }
        $data = Yaml::parseFile($file);

        $year = (int) ($data['year'] ?? date('Y'));
        $title = (string) ($data['title'] ?? 'Adventskalender '.$year);
        $calendar = $this->calendars->findOneBy(['title' => $title, 'year' => $year]) ?? new AdventCalendar($title, $year);
        $calendar->setStartDate(new \DateTimeImmutable((string) ($data['start'] ?? $year.'-12-01')))
            ->setTimezone($data['timezone'] ?? 'Europe/Berlin')
            ->setIntro($data['intro'] ?? null)
            ->setHost($data['host'] ?? null);

        foreach ($data['doors'] ?? [] as $row) {
            $number = (int) $row['number'];
            $door = $calendar->getDoor($number) ?? (new AdventDoor())->setNumber($number);
            $calendar->addDoor($door);
            $door->setQuestion($row['question'] ?? '')->setImage($row['image'] ?? null)->setPoints((int) ($row['points'] ?? 1));

            // Antworten nach Position abgleichen (vorhandene behalten, damit gegebene Antworten gültig bleiben)
            $existing = array_values($door->getChoices()->toArray());
            foreach (array_values($row['choices'] ?? []) as $i => $c) {
                $choice = $existing[$i] ?? new AdventChoice();
                $door->addChoice($choice);
                $choice->setPosition(($i + 1) * 10)->setText($c['text'] ?? '')->setCorrect((bool) ($c['correct'] ?? false))
                    ->setResponse($c['response'] ?? null)->setResponseImage($c['image'] ?? null)->setResponseVideoUrl($c['video'] ?? null);
            }
            foreach (\array_slice($existing, \count($row['choices'] ?? [])) as $surplus) {
                $door->removeChoice($surplus);
            }
        }

        $stages = array_values($calendar->getWinStages()->toArray());
        foreach (array_values($data['win_stages'] ?? []) as $i => $s) {
            $stage = $stages[$i] ?? new AdventWinStage();
            $calendar->addWinStage($stage);
            $stage->setMinPoints((int) $s['min_points'])->setLabel($s['label'])->setPrize($s['prize']);
        }

        foreach ($data['participants'] ?? [] as $name) {
            $known = $calendar->getParticipants()->findFirst(static fn (int $k, AdventParticipant $p): bool => $p->getName() === $name);
            if (null === $known) {
                $calendar->addParticipant(new AdventParticipant((string) $name));
            }
        }

        $errors = [];
        foreach ([$calendar, ...$calendar->getDoors(), ...$calendar->getWinStages(), ...$calendar->getParticipants()] as $entity) {
            foreach ($this->validator->validate($entity) as $violation) {
                $errors[] = \sprintf('%s – %s: %s', $entity, $violation->getPropertyPath() ?: '·', $violation->getMessage());
            }
        }
        if ([] !== $errors) {
            $io->error('Die Datei hat Fehler:');
            $io->listing($errors);

            return Command::FAILURE;
        }

        $io->definitionList(
            ['Kalender' => (string) $calendar],
            ['Start' => $calendar->getStartDate()->format('d.m.Y').' ('.$calendar->getTimezone().')'],
            ['Türen' => $calendar->getDoors()->count().' von '.AdventCalendar::DOORS],
            ['Gewinnstufen' => $calendar->getWinStages()->count()],
        );
        if ($dryRun) {
            $io->note('Probelauf – nichts gespeichert.');

            return Command::SUCCESS;
        }

        $this->em->persist($calendar);
        $this->em->flush();

        $io->table(['Teilnehmer', 'Persönlicher Link'], array_map(
            fn (AdventParticipant $p): array => [$p->getName(), $this->links->participantUrl($p)],
            $calendar->getParticipants()->getValues(),
        ));
        $io->success('Gespeichert.');

        return Command::SUCCESS;
    }
}
