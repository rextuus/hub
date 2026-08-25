<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandingPageController extends AbstractController
{
    #[Route('/', name: 'app_landing_page')]
    public function index(ProjectRepository $projectRepository, Security $security): Response
    {
        $projects = $projectRepository->findAll();

        if (!$security->getUser()) {
            $projects = array_filter($projects, function ($project) {
                return $project->getRoute() !== 'app_bet_ai_index';
            });
        }

        return $this->render('landing_page/index.html.twig', [
            'projects' => $projects,
        ]);
    }
}
