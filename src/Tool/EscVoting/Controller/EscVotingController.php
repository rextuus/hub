<?php

namespace App\Tool\EscVoting\Controller;

use App\Tool\EscVoting\Repository\ParticipantRepository;
use App\Tool\EscVoting\Entity\Participant;
use App\Tool\EscVoting\Repository\CountryRepository;
use App\Tool\EscVoting\Entity\Voter;
use App\Tool\EscVoting\Entity\Vote;
use App\Tool\EscVoting\Entity\Ballot;
use App\Entity\User;
use App\Tool\EscVoting\Repository\EscEditionRepository;
use App\Tool\EscVoting\Repository\VoterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class EscVotingController extends AbstractController
{
    #[Route('/esc-voting', name: 'app_esc_voting_index', methods: ['GET', 'POST'])]
    public function index(Request $request, Security $security, EntityManagerInterface $entityManager, VoterRepository $voterRepository, EscEditionRepository $escEditionRepository): Response
    {
        $activeEdition = $escEditionRepository->findActive();

        $session = $request->getSession();
        $user = $security->getUser();
        $sessionId = $session->getId();

        $voterName = $session->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');

        if ($request->isMethod('POST')) {
            $name = $request->request->get('voter_name');
            if ($name) {
                $session->set('esc_voter_name', $name);
                $response = $this->redirectToRoute('app_esc_voting_vote');
                $response->headers->setCookie(new Cookie('esc_voter_name', $name, strtotime('+30 days')));
                return $response;
            }
        }

        // Find existing voter
        $voter = null;
        if ($user instanceof User) {
            $voter = $voterRepository->findOneBy(['user' => $user]);
        } elseif ($voterName) {
            $voter = $voterRepository->findOneBy(['name' => $voterName, 'sessionId' => $sessionId]);
            if (!$voter) {
                // Try finding by name only if sessionId changed but we have a cookie
                $voter = $voterRepository->findOneBy(['name' => $voterName], ['id' => 'DESC']);
            }
        }

        $hasExistingBallot = false;
        $isEditable = true;
        if ($voter) {
            $conn = $entityManager->getConnection();
            $sql = 'SELECT id, is_editable FROM esc_voting_ballot WHERE voter_id = :voterId';
            $params = ['voterId' => $voter->id];
            if ($activeEdition) {
                $sql .= ' AND edition_id = :editionId';
                $params['editionId'] = $activeEdition->getId();
            }
            $existingBallot = $conn->fetchAssociative($sql, $params);
            if ($existingBallot) {
                $hasExistingBallot = true;
                $isEditable = (bool)$existingBallot['is_editable'];
            }
        }

        $conn = $entityManager->getConnection();
        $ballotCountSql = 'SELECT COUNT(b.id) FROM esc_voting_ballot b';
        $voteCountSql = 'SELECT COUNT(v.id) FROM esc_voting_vote v';
        $countParams = [];
        if ($activeEdition) {
            $ballotCountSql .= ' WHERE b.edition_id = :editionId';
            $voteCountSql .= ' JOIN esc_voting_ballot b ON v.ballot_id = b.id WHERE b.edition_id = :editionId';
            $countParams['editionId'] = $activeEdition->getId();
        }

        $ballotCount = $conn->fetchOne($ballotCountSql, $countParams);
        $voteCount = $conn->fetchOne($voteCountSql, $countParams);

        $response = $this->render('tool/esc_voting/index.html.twig', [
            'voter_name' => $voterName,
            'user' => $user,
            'ballot_count' => $ballotCount,
            'vote_count' => $voteCount,
            'has_existing_ballot' => $hasExistingBallot,
            'is_editable' => $isEditable,
            'active_edition' => $activeEdition,
        ]);

        if ($voterName && !$request->cookies->has('esc_voter_name')) {
            $response->headers->setCookie(new Cookie('esc_voter_name', $voterName, strtotime('+30 days')));
        }

        return $response;
    }

    #[Route('/esc-voting/vote/edit', name: 'app_esc_voting_vote_edit')]
    public function edit(Request $request, Security $security, VoterRepository $voterRepository, EntityManagerInterface $entityManager, EscEditionRepository $escEditionRepository): Response
    {
        $activeEdition = $escEditionRepository->findActive();
        $user = $security->getUser();
        $sessionId = $request->getSession()->getId();
        $voterName = $request->getSession()->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');

        $voter = null;
        if ($user instanceof User) {
            $voter = $voterRepository->findOneBy(['user' => $user]);
        } elseif ($voterName) {
            $voter = $voterRepository->findOneBy(['name' => $voterName, 'sessionId' => $sessionId]);
            if (!$voter) {
                $voter = $voterRepository->findOneBy(['name' => $voterName], ['id' => 'DESC']);
            }
        }

        if ($voter) {
            $conn = $entityManager->getConnection();
            // Get latest ballot
            $sql = 'SELECT b.id, b.is_editable FROM esc_voting_ballot b WHERE b.voter_id = :voterId';
            $params = ['voterId' => $voter->id];
            if ($activeEdition) {
                $sql .= ' AND b.edition_id = :editionId';
                $params['editionId'] = $activeEdition->getId();
            }
            $sql .= ' ORDER BY b.id DESC LIMIT 1';
            $ballotData = $conn->fetchAssociative($sql, $params);

            if ($ballotData) {
                if (!$ballotData['is_editable']) {
                    $this->addFlash('error', 'Deine Stimmen wurden bereits abgegeben und können nicht mehr bearbeitet werden.');
                    return $this->redirectToRoute('app_esc_voting_index');
                }

                $ballotId = $ballotData['id'];
                $sql = 'SELECT v.participant_id, v.points FROM esc_voting_vote v WHERE v.ballot_id = :ballotId';
                $votes = $conn->fetchAllAssociative($sql, ['ballotId' => $ballotId]);

                $choices = [];
                foreach ($votes as $vote) {
                    $choices[$vote['points']] = (string)$vote['participant_id'];
                }

                $request->getSession()->set('esc_voting_choices', $choices);
                if ($voter->name) {
                    $request->getSession()->set('esc_voter_name', $voter->name);
                }
            }
        }

        return $this->redirectToRoute('app_esc_voting_vote');
    }

    #[Route('/esc-voting/vote', name: 'app_esc_voting_vote')]
    public function vote(Request $request, ParticipantRepository $participantRepository, EscEditionRepository $escEditionRepository): Response
    {
        $activeEdition = $escEditionRepository->findActive();
        $initialChoices = $request->getSession()->get('esc_voting_choices', []);
        if (empty($initialChoices)) {
            $initialChoices = (object)[];
        }

        $participants = [];
        if ($activeEdition) {
            $participants = $participantRepository->findBy(['edition' => $activeEdition], ['startOrder' => 'ASC']);
        }

        $voterName = $request->getSession()->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');
        return $this->render('tool/esc_voting/vote.html.twig', [
            'participants' => $participants,
            'allowed_points' => [1, 2, 3, 4, 5, 6, 7, 8, 10, 12],
            'initial_choices' => $initialChoices,
            'voter_name' => $voterName,
            'active_edition' => $activeEdition,
        ]);
    }

    #[Route('/esc-voting/vote/update-choices', name: 'app_esc_voting_vote_update_choices', methods: ['POST'])]
    public function updateChoices(Request $request): Response
    {
        $choices = $request->request->all('choices');
        $request->getSession()->set('esc_voting_choices', $choices);

        return $this->json(['status' => 'success']);
    }

    #[Route('/esc-voting/vote/submit', name: 'app_esc_voting_vote_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager,
        VoterRepository $voterRepository,
        EscEditionRepository $escEditionRepository,
        Security $security
    ): Response {
        $activeEdition = $escEditionRepository->findActive();
        $votesData = $request->request->all('votes');
        $user = $security->getUser();
        $sessionId = $request->getSession()->getId();
        $voterName = $request->getSession()->get('esc_voter_name') ?: $request->cookies->get('esc_voter_name');

        // Find or create Voter
        $voter = null;
        if ($user instanceof User) {
            $voter = $voterRepository->findOneBy(['user' => $user]);
        } elseif ($voterName) {
            $voter = $voterRepository->findOneBy(['name' => $voterName, 'sessionId' => $sessionId]);
            if (!$voter) {
                $voter = $voterRepository->findOneBy(['name' => $voterName], ['id' => 'DESC']);
            }
        }

        if (!$voter) {
            $voter = new Voter(
                $voterName,
                $user instanceof User ? $user : null,
                $sessionId
            );
            $entityManager->persist($voter);
        }

        // Find existing ballot for this voter and edition
        $ballot = $entityManager->getRepository(Ballot::class)->findOneBy([
            'voter' => $voter,
            'edition' => $activeEdition
        ]);

        if ($ballot) {
            if (!$ballot->isEditable()) {
                $this->addFlash('error', 'Deine Stimmen wurden bereits abgegeben und können nicht mehr bearbeitet werden.');
                return $this->redirectToRoute('app_esc_voting_index');
            }
            // Remove old votes
            foreach ($ballot->votes as $oldVote) {
                $entityManager->remove($oldVote);
            }
            $ballot->votes->clear();
        } else {
            $ballot = new Ballot($voter);
            $ballot->edition = $activeEdition;
            // By default, new ballots are editable, but we could set it explicitly if needed
            $ballot->setIsEditable(true);
            $entityManager->persist($ballot);
        }

        foreach ($votesData as $points => $participantId) {
            if (!$participantId) continue;

            $participant = $participantRepository->find($participantId);
            if ($participant) {
                $vote = new Vote(
                    participant: $participant,
                    points: (int)$points,
                    voter: $voter,
                    ballot: $ballot,
                    sessionId: $sessionId
                );
                $entityManager->persist($vote);
            }
        }

        $entityManager->flush();

        // Clear choices from session after successful submit
        $request->getSession()->remove('esc_voting_choices');

        $response = $this->redirectToRoute('app_esc_voting_overview');
        if ($voterName) {
            $response->headers->setCookie(new Cookie('esc_voter_name', $voterName, strtotime('+30 days')));
        }

        return $response;
    }

    #[Route('/esc-voting/overview', name: 'app_esc_voting_overview')]
    public function overview(EntityManagerInterface $entityManager, ParticipantRepository $participantRepository, EscEditionRepository $escEditionRepository): Response
    {
        $activeEdition = $escEditionRepository->findActive();

        if ($activeEdition && !$activeEdition->isClosed() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Die Ergebnisse werden erst nach Abschluss des ESC veröffentlicht.');
            return $this->redirectToRoute('app_esc_voting_index');
        }

        $conn = $entityManager->getConnection();

        // 1. Get all participants for active edition
        $participants = [];
        if ($activeEdition) {
            $participantsSql = '
                SELECT p.id, p.artist, p.song, p.start_order as startOrder, c.name as countryName, c.country_code as countryCode
                FROM esc_voting_participant p
                JOIN esc_voting_country c ON p.country_id = c.id
                WHERE p.edition_id = :editionId
                ORDER BY p.start_order ASC
            ';
            $participants = $conn->fetchAllAssociative($participantsSql, ['editionId' => $activeEdition->getId()]);
        }

        // 2. Get all ballots with their votes
        $ballotsSql = '
            SELECT b.id as ballotId, v.name as voterName, u.email as userEmail
            FROM esc_voting_ballot b
            JOIN esc_voting_voter v ON b.voter_id = v.id
            LEFT JOIN `user` u ON v.user_id = u.id
        ';
        $ballotsParams = [];
        if ($activeEdition) {
            $ballotsSql .= ' WHERE b.edition_id = :editionId';
            $ballotsParams['editionId'] = $activeEdition->getId();
        }
        $ballotsSql .= ' ORDER BY b.id ASC';
        $ballotsData = $conn->fetchAllAssociative($ballotsSql, $ballotsParams);

        $ballots = [];
        foreach ($ballotsData as $bData) {
            $votesSql = 'SELECT v.participant_id as participantId, v.points FROM esc_voting_vote v WHERE v.ballot_id = :ballotId';
            $votes = $conn->fetchAllAssociative($votesSql, ['ballotId' => $bData['ballotId']]);

            $identifier = $bData['userEmail'] ?: ($bData['voterName'] ?: 'Anonym');
            $displayName = $bData['voterName'] ?: explode('@', $bData['userEmail'])[0];

            $ballots[] = [
                'id' => $bData['ballotId'],
                'voterName' => $displayName,
                'voterInitial' => strtoupper(mb_substr($identifier, 0, 1)),
                'votes' => $votes,
            ];
        }

        $voterCount = count($ballots);
        $voteCountSql = 'SELECT COUNT(v.id) FROM esc_voting_vote v';
        $voteCountParams = [];
        if ($activeEdition) {
            $voteCountSql .= ' JOIN esc_voting_ballot b ON v.ballot_id = b.id WHERE b.edition_id = :editionId';
            $voteCountParams['editionId'] = $activeEdition->getId();
        }
        $voteCount = $conn->fetchOne($voteCountSql, $voteCountParams);

        return $this->render('tool/esc_voting/overview.html.twig', [
            'participants' => $participants,
            'ballots' => $ballots,
            'ballot_count' => $voterCount,
            'vote_count' => $voteCount,
            'active_edition' => $activeEdition,
        ]);
    }
}
