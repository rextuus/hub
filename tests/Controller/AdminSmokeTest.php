<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tool\Advent\Entity\AdventCalendar;
use App\Tool\Advent\Entity\AdventChoice;
use App\Tool\Advent\Entity\AdventDoor;
use App\Tool\Advent\Entity\AdventParticipant;
use App\Tool\Advent\Entity\AdventWinStage;
use App\Tool\Fotokalender\Entity\PhotoCalendar;
use App\Tool\Fotokalender\Entity\PhotoCalendarMonth;
use App\Tool\Fotokalender\Entity\PhotoCalendarPhoto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/** Alle neuen Admin-Seiten öffnen sich (Liste, Neu, Bearbeiten) – fängt Konfigurationsfehler in EasyAdmin ab. */
final class AdminSmokeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    /** @var list<object> */
    private array $cleanup = [];

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $admin = (new User())->setEmail('smoke-'.bin2hex(random_bytes(4)).'@example.test')->setRoles(['ROLE_ADMIN'])->setPassword('x');
        $this->em->persist($admin);

        $calendar = new AdventCalendar('Smoke '.bin2hex(random_bytes(3)), 2026);
        $door = (new AdventDoor())->setNumber(1)->setQuestion('Frage');
        $door->addChoice((new AdventChoice())->setText('Ja')->setCorrect(true));
        $door->addChoice((new AdventChoice())->setText('Nein'));
        $calendar->addDoor($door);
        $calendar->addWinStage((new AdventWinStage())->setMinPoints(0)->setLabel('0+')->setPrize('Kekse'));
        $calendar->addParticipant(new AdventParticipant('Smoke'));
        $this->em->persist($calendar);

        $photoCalendar = new PhotoCalendar(1999, 'Smoke');
        $month = (new PhotoCalendarMonth())->setMonth(1)->setMotto('Test');
        $month->addPhoto((new PhotoCalendarPhoto())->setImage('fotokalender/x.jpg'));
        $photoCalendar->addMonth($month);
        $this->em->persist($photoCalendar);
        $this->em->flush();

        $this->cleanup = [$calendar, $photoCalendar, $admin];
        $this->client->loginUser($admin);
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $entity) {
            $this->em->remove($this->em->find($entity::class, $entity->getId()));
        }
        $this->em->flush();
        parent::tearDown();
    }

    public function testAdminPagesRender(): void
    {
        $calendar = $this->cleanup[0];
        $ids = [
            'advent-calendar' => $calendar->getId(),
            'advent-door' => $calendar->getDoor(1)->getId(),
            'advent-choice' => $calendar->getDoor(1)->getChoices()->first()->getId(),
            'advent-win-stage' => $calendar->getWinStages()->first()->getId(),
            'advent-participant' => $calendar->getParticipants()->first()->getId(),
            'photo-calendar' => $this->cleanup[1]->getId(),
            'photo-calendar-month' => $this->cleanup[1]->getMonth(1)->getId(),
            'photo-calendar-photo' => $this->cleanup[1]->getMonth(1)->getPhotos()->first()->getId(),
        ];

        foreach ($ids as $crud => $id) {
            foreach (['/admin/'.$crud, '/admin/'.$crud.'/new', '/admin/'.$crud.'/'.$id.'/edit'] as $url) {
                $this->client->request('GET', $url);
                self::assertResponseIsSuccessful($url);
            }
        }

        foreach (['/admin/project', '/admin/project/new', '/admin/advent-answer'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }

        $this->client->request('GET', '/admin/advent-door/'.$ids['advent-door'].'/edit');
        self::assertSelectorExists('input[type=file][name*="[choices][0][responseImage]"]', 'Bild-Upload je Antwort im Tür-Formular');
    }

    public function testUploadsLandInTheMediaFolder(): void
    {
        $calendar = $this->cleanup[0];
        $door = $calendar->getDoor(1);

        // Bild zu einer Antwort im eingebetteten Formular der Tür
        $crawler = $this->client->request('GET', '/admin/advent-door/'.$door->getId().'/edit');
        $form = $crawler->filter('form[name=AdventDoor]')->form();
        $form['AdventDoor[choices][0][responseImage][file]']->upload($this->jpeg()->getPathname());
        $this->client->submit($form);
        self::assertResponseRedirects();

        $this->em->clear();
        $choice = $this->em->find(AdventChoice::class, $door->getChoices()->first()->getId());
        self::assertMatchesRegularExpression('#^advent/[a-f0-9]+\.jpg$#', (string) $choice->getResponseImage());
        $file = self::getContainer()->getParameter('app.media_dir').'/'.$choice->getResponseImage();
        self::assertFileExists($file);
        unlink($file);

        // Mehrere Fotos auf einmal für ein Monatsblatt
        $month = $this->cleanup[1]->getMonth(1);
        $crawler = $this->client->request('GET', '/admin/photo-calendar-month/upload-photos?entityId='.$month->getId());
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[enctype="multipart/form-data"]')->form();
        $this->client->request('POST', $form->getUri(), ['_token' => $form['_token']->getValue()], ['photos' => [$this->jpeg(), $this->jpeg()]]);
        self::assertResponseRedirects();

        $this->em->clear();
        $photos = $this->em->find(PhotoCalendarMonth::class, $month->getId())->getPhotos();
        self::assertCount(3, $photos);
        foreach ($photos as $photo) {
            if (str_starts_with((string) $photo->getImage(), 'fotokalender/1999/januar/')) {
                unlink(self::getContainer()->getParameter('app.media_dir').'/'.$photo->getImage());
            }
        }
    }

    private function jpeg(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hub').'.jpg';
        file_put_contents($path, base64_decode(self::JPEG));

        return new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true);
    }

    private const string JPEG = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAAeACgDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwC/RRRX56ffhRRRQAUUUUAFFFFABRRRQAUUUUAf/9k=';
}
