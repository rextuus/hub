<?php

namespace App\Controller\Admin;

use App\Repository\ProjectRepository;
use App\Routing\MainHostContextListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Startseite von WH-Company: nur auf der Hauptdomain (app.main_host_pattern), sonst gehört „/“ dem Tool der
 * jeweiligen Domain. Bewusst schlicht: die großen Projekte, ein Weg zur Liste aller Projekte; die Anmeldung ist
 * nur über das Logo erreichbar.
 */
#[Route(
    host: '{'.MainHostContextListener::PARAMETER.'}',
    requirements: [MainHostContextListener::PARAMETER => '%app.main_host_pattern%'],
    defaults: [MainHostContextListener::PARAMETER => '%app.main_host%'],
)]
class LandingPageController extends AbstractController
{
    #[Route('/', name: 'app_landing_page', methods: ['GET'])]
    public function index(ProjectRepository $projects): Response
    {
        return $this->render('landing_page/index.html.twig', [
            'featured' => $projects->findVisible(null !== $this->getUser(), true),
        ]);
    }

    #[Route('/projekte', name: 'app_projects', methods: ['GET'])]
    public function projects(ProjectRepository $projects): Response
    {
        return $this->render('landing_page/projects.html.twig', [
            'projects' => $projects->findVisible(null !== $this->getUser()),
        ]);
    }
}
