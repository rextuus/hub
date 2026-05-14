<?php

namespace App\Tool\EscVoting\Controller;

use App\Tool\EscVoting\Repository\CountryRepository;
use App\Tool\EscVoting\Entity\Voter;
use App\Tool\EscVoting\Entity\Vote;
use App\Tool\EscVoting\Entity\Ballot;
use App\Entity\User;
use App\Tool\EscVoting\Repository\VoterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class EscVotingController extends AbstractController
{
    #[Route('/esc-voting', name: 'app_esc_voting_index', methods: ['GET', 'POST'])]
    public function index(Request $request, Security $security, EntityManagerInterface $entityManager, VoterRepository $voterRepository): Response
    {
        $session = $request->getSession();
        $user = $security->getUser();
        $sessionId = $session->getId();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('voter_name');
            if ($name) {
                $session->set('esc_voter_name', $name);
                return $this->redirectToRoute('app_esc_voting_vote');
            }
        }

        $voterName = $session->get('esc_voter_name');

        // Find existing voter
        $voter = null;
        if ($user instanceof User) {
            $voter = $voterRepository->findOneBy(['user' => $user]);
        } elseif ($voterName) {
            $voter = $voterRepository->findOneBy(['name' => $voterName, 'sessionId' => $sessionId]);
        }

        $hasExistingBallot = false;
        if ($voter) {
            $conn = $entityManager->getConnection();
            $hasExistingBallot = (bool)$conn->fetchOne('SELECT 1 FROM esc_voting_ballot WHERE voter_id = :voterId', ['voterId' => $voter->id]);
        }

        $conn = $entityManager->getConnection();
        $ballotCount = $conn->fetchOne('SELECT COUNT(id) FROM esc_voting_ballot');
        $voteCount = $conn->fetchOne('SELECT COUNT(id) FROM esc_voting_vote');

        return $this->render('tool/esc_voting/index.html.twig', [
            'voter_name' => $voterName,
            'user' => $user,
            'ballot_count' => $ballotCount,
            'vote_count' => $voteCount,
            'has_existing_ballot' => $hasExistingBallot,
        ]);
    }

    #[Route('/esc-voting/vote/edit', name: 'app_esc_voting_vote_edit')]
    public function edit(Request $request, Security $security, VoterRepository $voterRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $security->getUser();
        $sessionId = $request->getSession()->getId();
        $voterName = $request->getSession()->get('esc_voter_name');

        $voter = null;
        if ($user instanceof User) {
            $voter = $voterRepository->findOneBy(['user' => $user]);
        } elseif ($voterName) {
            $voter = $voterRepository->findOneBy(['name' => $voterName, 'sessionId' => $sessionId]);
        }

        if ($voter) {
            $conn = $entityManager->getConnection();
            // Get latest ballot
            $ballotId = $conn->fetchOne('SELECT id FROM esc_voting_ballot WHERE voter_id = :voterId ORDER BY id DESC LIMIT 1', ['voterId' => $voter->id]);

            if ($ballotId) {
                $votes = $conn->fetchAllAssociative('SELECT country_id, points FROM esc_voting_vote WHERE ballot_id = :ballotId', ['ballotId' => $ballotId]);

                $choices = [];
                foreach ($votes as $vote) {
                    $choices[$vote['points']] = $vote['country_id'];
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
    public function vote(Request $request, CountryRepository $countryRepository): Response
    {
        $initialChoices = $request->getSession()->get('esc_voting_choices', []);
        if (empty($initialChoices)) {
            $initialChoices = (object)[];
        }

        return $this->render('tool/esc_voting/vote.html.twig', [
            'countries' => $countryRepository->findBy([], ['startOrder' => 'ASC']),
            'allowed_points' => [1, 2, 3, 4, 5, 6, 7, 8, 10, 12],
            'initial_choices' => $initialChoices,
            'voter_name' => $request->getSession()->get('esc_voter_name'),
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
        CountryRepository $countryRepository,
        EntityManagerInterface $entityManager,
        VoterRepository $voterRepository,
        Security $security
    ): Response {
        $votesData = $request->request->all('votes');
        $user = $security->getUser();
        $sessionId = $request->getSession()->getId();
        $voterName = $request->getSession()->get('esc_voter_name');

        // Find or create Voter
        $voter = null;
        if ($user instanceof User) {
            $voter = $voterRepository->findOneBy(['user' => $user]);
        } elseif ($voterName) {
            $voter = $voterRepository->findOneBy(['name' => $voterName, 'sessionId' => $sessionId]);
        }

        if (!$voter) {
            $voter = new Voter(
                $voterName,
                $user instanceof User ? $user : null,
                $sessionId
            );
            $entityManager->persist($voter);
        }

        $ballot = new Ballot($voter);
        $entityManager->persist($ballot);

        foreach ($votesData as $points => $countryId) {
            if (!$countryId) continue;

            $country = $countryRepository->find($countryId);
            if ($country) {
                $vote = new Vote(
                    country: $country,
                    points: (int)$points,
                    voter: $voter,
                    ballot: $ballot,
                    sessionId: $sessionId
                );
                $entityManager->persist($vote);
            }
        }

        $entityManager->flush();

        // Clear choices and voter name from session after successful submit
        $request->getSession()->remove('esc_voting_choices');
        $request->getSession()->remove('esc_voter_name');

        return $this->redirectToRoute('app_esc_voting_overview');
    }

    #[Route('/esc-voting/overview', name: 'app_esc_voting_overview')]
    public function overview(EntityManagerInterface $entityManager, CountryRepository $countryRepository): Response
    {
        $conn = $entityManager->getConnection();

        // 1. Get all countries
        $countriesSql = 'SELECT id, name, country_code as countryCode, start_order as startOrder FROM esc_voting_country ORDER BY start_order ASC';
        $countries = $conn->fetchAllAssociative($countriesSql);

        // 2. Get all ballots with their votes
        $ballotsSql = '
            SELECT b.id as ballotId, v.name as voterName, u.email as userEmail
            FROM esc_voting_ballot b
            JOIN esc_voting_voter v ON b.voter_id = v.id
            LEFT JOIN "user" u ON v.user_id = u.id
            ORDER BY b.id ASC
        ';
        $ballotsData = $conn->fetchAllAssociative($ballotsSql);

        $ballots = [];
        foreach ($ballotsData as $bData) {
            $votesSql = 'SELECT country_id as countryId, points FROM esc_voting_vote WHERE ballot_id = :ballotId';
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
        $voteCountSql = 'SELECT COUNT(id) FROM esc_voting_vote';
        $voteCount = $conn->fetchOne($voteCountSql);

        return $this->render('tool/esc_voting/overview.html.twig', [
            'countries' => $countries,
            'ballots' => $ballots,
            'ballot_count' => $voterCount,
            'vote_count' => $voteCount,
        ]);
    }
}
