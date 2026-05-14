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
    public function index(Request $request, Security $security, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('voter_name');
            if ($name) {
                $session->set('esc_voter_name', $name);
                return $this->redirectToRoute('app_esc_voting_vote');
            }
        }

        $voterName = $session->get('esc_voter_name');
        $user = $security->getUser();

        $conn = $entityManager->getConnection();
        $ballotCount = $conn->fetchOne('SELECT COUNT(id) FROM esc_voting_ballot');
        $voteCount = $conn->fetchOne('SELECT COUNT(id) FROM esc_voting_vote');

        return $this->render('tool/esc_voting/index.html.twig', [
            'voter_name' => $voterName,
            'user' => $user,
            'ballot_count' => $ballotCount,
            'vote_count' => $voteCount,
        ]);
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
                    $country,
                    (int)$points,
                    $voter,
                    $sessionId
                );
                $vote->ballot = $ballot;
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
        $countries = $countryRepository->findAll();
        $conn = $entityManager->getConnection();

        $sql = '
            SELECT c.id, c.name, c.country_code as countryCode, SUM(v.points) as totalPoints
            FROM esc_voting_country c
            LEFT JOIN esc_voting_vote v ON c.id = v.country_id
            GROUP BY c.id, c.name, c.country_code
            ORDER BY totalPoints DESC, c.name ASC
        ';

        $results = $conn->fetchAllAssociative($sql);

        // Fetch detailed votes for each country to show who voted
        foreach ($results as &$result) {
            $voteDetailsSql = '
                SELECT v.points, vt.name as voterName, u.email as userEmail
                FROM esc_voting_vote v
                JOIN esc_voting_voter vt ON v.voter_id = vt.id
                LEFT JOIN "user" u ON vt.user_id = u.id
                WHERE v.country_id = :countryId
                ORDER BY v.points DESC
            ';
            $votes = $conn->fetchAllAssociative($voteDetailsSql, ['countryId' => $result['id']]);

            foreach ($votes as &$vote) {
                $identifier = $vote['userEmail'] ?: ($vote['voterName'] ?: 'A');
                $vote['voterInitial'] = strtoupper(mb_substr($identifier, 0, 1));
                $vote['voterDisplayName'] = $vote['voterName'] ?: explode('@', $vote['userEmail'])[0];
            }
            $result['votes'] = $votes;
        }

        $voterCountSql = 'SELECT COUNT(id) FROM esc_voting_ballot';
        $voterCount = $conn->fetchOne($voterCountSql);

        $voteCountSql = 'SELECT COUNT(id) FROM esc_voting_vote';
        $voteCount = $conn->fetchOne($voteCountSql);

        return $this->render('tool/esc_voting/overview.html.twig', [
            'results' => $results,
            'ballot_count' => $voterCount,
            'vote_count' => $voteCount,
        ]);
    }
}
