<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Startseite nur auf der Hauptdomain; andere Domains bleiben frei für Tools (z. B. kresh.wh-company.com, die Journey). */
final class HostRoutingTest extends WebTestCase
{
    public function testLandingPageAnswersOnMainHosts(): void
    {
        $client = self::createClient();
        foreach (['wh-company.com', 'www.wh-company.com', 'localhost'] as $host) {
            $client->request('GET', 'https://'.$host.'/');
            self::assertResponseIsSuccessful($host);
            self::assertSelectorExists('html[lang=de]');
        }
    }

    public function testLandingPageDoesNotAnswerOnOtherHosts(): void
    {
        $client = self::createClient();
        $client->request('GET', 'https://tool.wh-company.com/');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', 'https://tool.wh-company.com/projekte');
        self::assertResponseStatusCodeSame(404);
    }

    public function testLinksBackToTheMainDomainFromOtherHosts(): void
    {
        $client = self::createClient();
        // Fotokalender ist an keine Domain gebunden; sein Logo-Link zeigt von fremden Hosts auf wh-company.com
        // (kresh.wh-company.com gehört inzwischen allein der Journey, tests/Tool/Journey)
        $client->request('GET', 'https://tool.wh-company.com/calendar/overview.html');
        self::assertResponseRedirects('https://tool.wh-company.com/kalender', 301);

        $client->request('GET', 'https://localhost/calendar/detail/march.html');
        self::assertResponseRedirects('https://localhost/kalender/2022/maerz', 301);
    }

    public function testProjectsRequiringLoginAreHiddenFromGuests(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $hidden = (new Project())->setName('Geheim-'.bin2hex(random_bytes(3)))->setDescription('x')->setIcon('lucide:bot')
            ->setRoute('app_landing_page')->setRequiresLogin(true);
        $visible = (new Project())->setName('Offen-'.bin2hex(random_bytes(3)))->setDescription('x')->setIcon('lucide:star')
            ->setUrl('https://example.com');
        $em->persist($hidden);
        $em->persist($visible);
        $em->flush();

        $client->request('GET', 'https://wh-company.com/projekte');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.wh-projects__list', $visible->getName());
        self::assertSelectorTextNotContains('.wh-projects__list', $hidden->getName());

        $em->remove($em->find(Project::class, $hidden->getId()));
        $em->remove($em->find(Project::class, $visible->getId()));
        $em->flush();
    }

    public function testMediaRouteRejectsTraversal(): void
    {
        $client = self::createClient();
        $client->request('GET', '/media/projekte/..%2F..%2F.env');
        self::assertResponseStatusCodeSame(404);
        $client->request('GET', '/media/gibt-es-nicht.jpg');
        self::assertResponseStatusCodeSame(404);
    }

    public function testHealthzOnAnyHost(): void
    {
        $client = self::createClient();
        $client->request('GET', 'http://127.0.0.1/healthz');
        self::assertResponseIsSuccessful();
    }
}
