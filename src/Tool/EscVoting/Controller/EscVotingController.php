<?php

namespace App\Tool\EscVoting\Controller;

use App\Tool\EscVoting\Repository\CountryRepository;
use App\Tool\EscVoting\Entity\Vote;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class EscVotingController extends AbstractController
{
    #[Route('/esc-voting', name: 'app_esc_voting_index')]
    public function index(): Response
    {
        return $this->render('tool/esc_voting/index.html.twig');
    }

    #[Route('/esc-voting/vote', name: 'app_esc_voting_vote')]
    public function vote(CountryRepository $countryRepository): Response
    {
        return $this->render('tool/esc_voting/vote.html.twig', [
            'countries' => $countryRepository->findBy([], ['startOrder' => 'ASC']),
            'allowed_points' => [1, 2, 3, 4, 5, 6, 7, 8, 10, 12],
        ]);
    }

    #[Route('/esc-voting/vote/submit', name: 'app_esc_voting_vote_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        CountryRepository $countryRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $votesData = $request->request->all('votes');
        $user = $security->getUser();
        $sessionId = $request->getSession()->getId();

        foreach ($votesData as $points => $countryId) {
            if (!$countryId) continue;

            $country = $countryRepository->find($countryId);
            if ($country) {
                $vote = new Vote(
                    $country,
                    (int)$points,
                    $user instanceof User ? $user : null,
                    $sessionId
                );
                $entityManager->persist($vote);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_esc_voting_overview');
    }

    #[Route('/esc-voting/overview', name: 'app_esc_voting_overview')]
    public function overview(): Response
    {
        return $this->render('tool/esc_voting/overview.html.twig');
    }
}
