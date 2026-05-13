<?php

namespace App\Controller\Admin;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LandingPageController extends AbstractController
{
    #[Route('/', name: 'app_landing_page')]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('landing_page/index.html.twig', [
            'projects' => $projectRepository->findAll(),
        ]);
    }
}
