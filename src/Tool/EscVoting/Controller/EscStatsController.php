<?php

namespace App\Tool\EscVoting\Controller;

use App\Tool\EscVoting\Repository\EscEditionRepository;
use App\Tool\EscVoting\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/esc-voting/stats')]
class EscStatsController extends AbstractController
{
    #[Route('', name: 'app_esc_voting_stats')]
    public function index(
        EscEditionRepository $editionRepository,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $activeEdition = $editionRepository->findOneBy(['isActive' => true]);

        if (!$activeEdition) {
            return $this->render('tool/esc_voting/stats.html.twig', [
                'activeEdition' => null,
            ]);
        }

        // 1. Hot or Not Ranking
        $hotOrNotQuery = $entityManager->createQuery(
            'SELECT p.id as participantId, AVG(pn.ratingHotOrNot) as avgRating, COUNT(pn.id) as voteCount
             FROM App\Tool\EscVoting\Entity\ParticipantNote pn
             JOIN pn.participant p
             WHERE p.edition = :edition
             AND pn.ratingHotOrNot > 0
             GROUP BY p.id
             ORDER BY avgRating DESC'
        )->setParameter('edition', $activeEdition);

        $hotOrNotResults = $hotOrNotQuery->getResult();
        $hotOrNotRanking = [];
        foreach ($hotOrNotResults as $res) {
            $participant = $participantRepository->find($res['participantId']);

            // Get voters for this participant
            $votersQuery = $entityManager->createQuery(
                'SELECT v.name, pn.ratingHotOrNot
                 FROM App\Tool\EscVoting\Entity\ParticipantNote pn
                 JOIN pn.voter v
                 WHERE pn.participant = :participant
                 AND pn.ratingHotOrNot > 0
                 ORDER BY pn.ratingHotOrNot DESC'
            )->setParameter('participant', $participant);
            $voters = $votersQuery->getResult();

            $hotOrNotRanking[] = [
                'participant' => $participant,
                'avg' => round($res['avgRating'], 2),
                'count' => $res['voteCount'],
                'voters' => $voters
            ];
        }

        // 2. Besonderheiten (Feuerwerk, Gadgets, Tänzer)
        $featuresQuery = $entityManager->createQuery(
            'SELECT p.id as participantId,
                    SUM(CASE WHEN pn.hasFireworks = true THEN 1 ELSE 0 END) as fireworksCount,
                    SUM(CASE WHEN pn.hasGadgets = true THEN 1 ELSE 0 END) as gadgetsCount,
                    SUM(CASE WHEN pn.hasExtraDancers = true THEN 1 ELSE 0 END) as dancersCount
             FROM App\Tool\EscVoting\Entity\ParticipantNote pn
             JOIN pn.participant p
             WHERE p.edition = :edition
             GROUP BY p.id'
        )->setParameter('edition', $activeEdition);

        $featuresResults = $featuresQuery->getResult();

        $fireworksRanking = [];
        $gadgetsRanking = [];
        $dancersRanking = [];

        foreach ($featuresResults as $res) {
            $participant = $participantRepository->find($res['participantId']);

            if ($res['fireworksCount'] > 0) {
                $voters = $entityManager->createQuery(
                    'SELECT v.name FROM App\Tool\EscVoting\Entity\ParticipantNote pn
                     JOIN pn.voter v WHERE pn.participant = :participant AND pn.hasFireworks = true'
                )->setParameter('participant', $participant)->getResult();
                $fireworksRanking[] = ['participant' => $participant, 'count' => $res['fireworksCount'], 'voters' => array_column($voters, 'name')];
            }
            if ($res['gadgetsCount'] > 0) {
                $voters = $entityManager->createQuery(
                    'SELECT v.name FROM App\Tool\EscVoting\Entity\ParticipantNote pn
                     JOIN pn.voter v WHERE pn.participant = :participant AND pn.hasGadgets = true'
                )->setParameter('participant', $participant)->getResult();
                $gadgetsRanking[] = ['participant' => $participant, 'count' => $res['gadgetsCount'], 'voters' => array_column($voters, 'name')];
            }
            if ($res['dancersCount'] > 0) {
                $voters = $entityManager->createQuery(
                    'SELECT v.name FROM App\Tool\EscVoting\Entity\ParticipantNote pn
                     JOIN pn.voter v WHERE pn.participant = :participant AND pn.hasExtraDancers = true'
                )->setParameter('participant', $participant)->getResult();
                $dancersRanking[] = ['participant' => $participant, 'count' => $res['dancersCount'], 'voters' => array_column($voters, 'name')];
            }
        }

        // Sort features rankings
        usort($fireworksRanking, fn($a, $b) => $b['count'] <=> $a['count']);
        usort($gadgetsRanking, fn($a, $b) => $b['count'] <=> $a['count']);
        usort($dancersRanking, fn($a, $b) => $b['count'] <=> $a['count']);

        return $this->render('tool/esc_voting/stats.html.twig', [
            'activeEdition' => $activeEdition,
            'hotOrNotRanking' => array_slice($hotOrNotRanking, 0, 10),
            'fireworksRanking' => array_slice($fireworksRanking, 0, 5),
            'gadgetsRanking' => array_slice($gadgetsRanking, 0, 5),
            'dancersRanking' => array_slice($dancersRanking, 0, 5),
        ]);
    }
}
