<?php

namespace App\Tool\EscVoting\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestComponentController extends AbstractController
{
    #[Route('/esc-voting/test-component', name: 'app_esc_voting_test_component')]
    public function index(): Response
    {
        return $this->render('tool/esc_voting/test_component.html.twig');
    }
}
