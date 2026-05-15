<?php

namespace App\Tool\EscVoting\Controller;

use App\Entity\User;
use App\Tool\EscVoting\Entity\Participant;
use App\Tool\EscVoting\Entity\ParticipantNote;
use App\Tool\EscVoting\Repository\EscEditionRepository;
use App\Tool\EscVoting\Repository\ParticipantRepository;
use App\Tool\EscVoting\Repository\VoterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EscNoteController extends AbstractController
{
    #[Route('/esc-voting/notes', name: 'app_esc_voting_notes')]
    public function index(
        Request $request,
        Security $security,
        VoterRepository $voterRepository,
        EscEditionRepository $escEditionRepository,
        ParticipantRepository $participantRepository
    ): Response {
        $activeEdition = $escEditionRepository->findActive();
        if (!$activeEdition) {
            $this->addFlash('warning', 'Keine aktive ESC-Edition gefunden.');
            return $this->redirectToRoute('app_esc_voting_index');
        }

        $user = $security->getUser();
        $voterName = $request->getSession()->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');
        $sessionId = $request->getSession()->getId();

        if (!$voterName && !($user instanceof User)) {
            $this->addFlash('info', 'Bitte gib erst deinen Namen ein, um den Notizblock zu nutzen.');
            return $this->redirectToRoute('app_esc_voting_index');
        }

        $voter = $voterRepository->findOrCreateVoter($user instanceof User ? $user : null, $voterName, $sessionId);

        $participants = $participantRepository->findBy(['edition' => $activeEdition], ['startOrder' => 'ASC']);
        if (empty($participants)) {
            $this->addFlash('warning', 'Keine Teilnehmer für diese Edition gefunden.');
            return $this->redirectToRoute('app_esc_voting_index');
        }

        return $this->render('tool/esc_voting/notes.html.twig', [
            'active_edition' => $activeEdition,
            'participants' => $participants,
            'voter' => $voter,
        ]);
    }

    #[Route('/esc-voting/notes/save', name: 'app_esc_voting_notes_save', methods: ['POST'])]
    public function save(
        Request $request,
        Security $security,
        VoterRepository $voterRepository,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $participantId = $data['participantId'] ?? null;
        if (!$participantId) {
            return new JsonResponse(['error' => 'Missing participantId'], Response::HTTP_BAD_REQUEST);
        }

        $user = $security->getUser();
        $voterName = $request->getSession()->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');
        $sessionId = $request->getSession()->getId();

        $voter = $voterRepository->findOrCreateVoter($user instanceof User ? $user : null, $voterName, $sessionId);
        $participant = $participantRepository->find($participantId);

        if (!$participant) {
            return new JsonResponse(['error' => 'Participant not found'], Response::HTTP_NOT_FOUND);
        }

        $note = $entityManager->getRepository(ParticipantNote::class)->findOneBy([
            'voter' => $voter,
            'participant' => $participant,
        ]);

        if (!$note) {
            $note = new ParticipantNote();
            $note->setVoter($voter);
            $note->setParticipant($participant);
            $entityManager->persist($note);
        }

        if (isset($data['ratingSong'])) $note->setRatingSong($data['ratingSong']);
        if (isset($data['ratingPerformance'])) $note->setRatingPerformance($data['ratingPerformance']);
        if (isset($data['ratingVoice'])) $note->setRatingVoice($data['ratingVoice']);
        if (isset($data['ratingOutfit'])) $note->setRatingOutfit($data['ratingOutfit']);
        if (isset($data['ratingOverall'])) $note->setRatingOverall($data['ratingOverall']);
        if (isset($data['note'])) $note->setNote($data['note']);
        if (isset($data['isMissed'])) $note->setIsMissed($data['isMissed']);

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
