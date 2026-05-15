<?php

namespace App\Tool\EscVoting\Controller;

use App\Entity\User;
use App\Tool\EscVoting\Entity\ParticipantNote;
use App\Tool\EscVoting\Repository\EscEditionRepository;
use App\Tool\EscVoting\Repository\ParticipantRepository;
use App\Tool\EscVoting\Repository\VoterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EscPrecalculationController extends AbstractController
{
    #[Route('/esc-voting/precalculation', name: 'app_esc_voting_precalculation')]
    public function index(
        Request $request,
        Security $security,
        VoterRepository $voterRepository,
        EscEditionRepository $escEditionRepository,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $activeEdition = $escEditionRepository->findActive();
        if (!$activeEdition) {
            $this->addFlash('warning', 'Keine aktive ESC-Edition gefunden.');
            return $this->redirectToRoute('app_esc_voting_index');
        }

        $user = $security->getUser();
        $voterName = $request->getSession()->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');
        $sessionId = $request->getSession()->getId();

        $voter = $voterRepository->findOrCreateVoter($user instanceof User ? $user : null, $voterName, $sessionId);

        $notes = $entityManager->getRepository(ParticipantNote::class)->findBy(['voter' => $voter]);

        // Filter notes by active edition participants
        $filteredNotes = array_filter($notes, function (ParticipantNote $note) use ($activeEdition) {
            return $note->getParticipant()->getEdition() === $activeEdition;
        });

        // Pre-calculation logic
        usort($filteredNotes, function (ParticipantNote $a, ParticipantNote $b) {
            // Sort by Overall Rating desc
            $comp = ($b->getRatingOverall() ?? 0) <=> ($a->getRatingOverall() ?? 0);
            if ($comp !== 0) return $comp;

            // Then by average of other categories
            $avgA = (($a->getRatingSong() ?? 0) + ($a->getRatingPerformance() ?? 0) + ($a->getRatingVoice() ?? 0) + ($a->getRatingOutfit() ?? 0)) / 4;
            $avgB = (($b->getRatingSong() ?? 0) + ($b->getRatingPerformance() ?? 0) + ($b->getRatingVoice() ?? 0) + ($b->getRatingOutfit() ?? 0)) / 4;

            $comp = $avgB <=> $avgA;
            if ($comp !== 0) return $comp;

            // Finally by start order (original sequence)
            return $a->getParticipant()->getStartOrder() <=> $b->getParticipant()->getStartOrder();
        });

        $escPoints = [12, 10, 8, 7, 6, 5, 4, 3, 2, 1];
        $precalculatedVotes = [];

        foreach ($filteredNotes as $index => $note) {
            if ($index < 10) {
                $precalculatedVotes[$escPoints[$index]] = $note->getParticipant();
            }
        }

        $participants = $participantRepository->findBy(['edition' => $activeEdition], ['startOrder' => 'ASC']);
        $participantToIndex = [];
        foreach ($participants as $idx => $p) {
            $participantToIndex[$p->getId()] = $idx;
        }

        return $this->render('tool/esc_voting/precalculation.html.twig', [
            'active_edition' => $activeEdition,
            'precalculated_votes' => $precalculatedVotes,
            'notes' => $filteredNotes,
            'voter' => $voter,
            'participant_to_index' => $participantToIndex,
        ]);
    }

    #[Route('/esc-voting/precalculation/apply', name: 'app_esc_voting_precalculation_apply', methods: ['POST'])]
    public function apply(
        Request $request,
        Security $security,
        VoterRepository $voterRepository,
        EscEditionRepository $escEditionRepository,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $activeEdition = $escEditionRepository->findActive();
        if (!$activeEdition) {
            return $this->redirectToRoute('app_esc_voting_index');
        }

        $user = $security->getUser();
        $voterName = $request->getSession()->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');
        $sessionId = $request->getSession()->getId();
        $voter = $voterRepository->findOrCreateVoter($user instanceof User ? $user : null, $voterName, $sessionId);

        $votesData = $request->request->all('votes');

        // We reuse the logic from EscVotingController::submit basically
        // But we might want to just set the session first so the user can see it on the voting page?
        // Or directly save it? The user said "adapt to your voting zettel".
        // Usually "Zettel" refers to the voting page where you can still change things.

        $request->getSession()->set('esc_voting_choices', $votesData);
        $this->addFlash('success', 'Deine Notizen wurden in deinen Stimmzettel übernommen. Du kannst sie hier noch einmal prüfen und dann absenden.');

        return $this->redirectToRoute('app_esc_voting_vote');
    }
}
