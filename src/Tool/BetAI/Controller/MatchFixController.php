<?php

namespace App\Tool\BetAI\Controller;

use App\Tool\BetAI\Entity\BetMatch;
use App\Tool\BetAI\Entity\Team;
use App\Tool\BetAI\Entity\TeamAlias;
use App\Tool\BetAI\Form\AssignTeamType;
use App\Tool\BetAI\Repository\TeamAliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tool/bet-ai/match-fix')]
class MatchFixController extends AbstractController
{
    #[Route('/{id}', name: 'app_bet_ai_match_fix')]
    public function index(BetMatch $match, Request $request, EntityManagerInterface $entityManager, TeamAliasRepository $dictionaryRepository): Response
    {
        $form = $this->createForm(AssignTeamType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $team = $data['team'];

            if ($form->get('assignHome')->isClicked()) {
                $match->setHomeTeam($team);
                $this->updateDictionary($match->getRawHomeTeamName(), $team, $dictionaryRepository, $entityManager);
            } elseif ($form->get('assignAway')->isClicked()) {
                $match->setAwayTeam($team);
                $this->updateDictionary($match->getRawAwayTeamName(), $team, $dictionaryRepository, $entityManager);
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $match->getGameWeek()->id]);
        }

        return $this->render('tool/bet_ai/match/fix.html.twig', [
            'match' => $match,
            'form' => $form,
        ]);
    }

    private function updateDictionary(string $rawName, Team $team, TeamAliasRepository $dictionaryRepository, EntityManagerInterface $entityManager): void
    {
        $dictionary = $dictionaryRepository->findOneBy(['rawName' => $rawName]);
        if (!$dictionary) {
            $dictionary = new TeamAlias();
            $dictionary->setRawName($rawName);
        }
        $dictionary->setTeam($team);
        $entityManager->persist($dictionary);
        $entityManager->flush();
    }
}
