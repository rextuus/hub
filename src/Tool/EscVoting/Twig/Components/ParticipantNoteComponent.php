<?php

namespace App\Tool\EscVoting\Twig\Components;

use App\Tool\EscVoting\Entity\Participant;
use App\Tool\EscVoting\Entity\ParticipantNote;
use App\Tool\EscVoting\Entity\Voter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('ParticipantNoteComponent', template: 'tool/esc_voting/components/participant_note.html.twig')]
class ParticipantNoteComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public Participant $participant;

    #[LiveProp]
    public Voter $voter;

    #[LiveProp(writable: true)]
    public ?int $ratingSong = 0;

    #[LiveProp(writable: true)]
    public ?int $ratingPerformance = 0;

    #[LiveProp(writable: true)]
    public ?int $ratingVoice = 0;

    #[LiveProp(writable: true)]
    public ?int $ratingOutfit = 0;

    #[LiveProp(writable: true)]
    public ?int $ratingOverall = 0;

    #[LiveProp(writable: true)]
    public ?int $ratingHotOrNot = 0;

    #[LiveProp(writable: true)]
    public ?string $note = '';

    #[LiveProp(writable: true)]
    public bool $isMissed = false;

    #[LiveProp(writable: true)]
    public bool $hasFireworks = false;

    #[LiveProp(writable: true)]
    public bool $hasGadgets = false;

    #[LiveProp(writable: true)]
    public bool $hasExtraDancers = false;

    #[LiveProp]
    public ?string $saveStatus = '';

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function mount(Participant $participant, Voter $voter): void
    {
        $this->participant = $participant;
        $this->voter = $voter;

        $note = $this->entityManager->getRepository(ParticipantNote::class)->findOneBy([
            'voter' => $voter,
            'participant' => $participant,
        ]);

        if ($note) {
            $this->ratingSong = $note->getRatingSong() ?? 0;
            $this->ratingPerformance = $note->getRatingPerformance() ?? 0;
            $this->ratingVoice = $note->getRatingVoice() ?? 0;
            $this->ratingOutfit = $note->getRatingOutfit() ?? 0;
            $this->ratingOverall = $note->getRatingOverall() ?? 0;
            $this->ratingHotOrNot = $note->getRatingHotOrNot() ?? 0;
            $this->note = $note->getNote() ?? '';
            $this->isMissed = $note->isMissed();
            $this->hasFireworks = $note->hasFireworks();
            $this->hasGadgets = $note->hasGadgets();
            $this->hasExtraDancers = $note->hasExtraDancers();
        }
    }

    #[LiveAction]
    public function save(): void
    {
        $this->saveStatus = 'Speichere...';

        $note = $this->entityManager->getRepository(ParticipantNote::class)->findOneBy([
            'voter' => $this->voter,
            'participant' => $this->participant,
        ]);

        if (!$note) {
            $note = new ParticipantNote();
            $note->setVoter($this->voter);
            $note->setParticipant($this->participant);
            $this->entityManager->persist($note);
        }

        $note->setRatingSong($this->ratingSong);
        $note->setRatingPerformance($this->ratingPerformance);
        $note->setRatingVoice($this->ratingVoice);
        $note->setRatingOutfit($this->ratingOutfit);
        $note->setRatingOverall($this->ratingOverall);
        $note->setRatingHotOrNot($this->ratingHotOrNot);
        $note->setNote($this->note);
        $note->setIsMissed($this->isMissed);
        $note->setHasFireworks($this->hasFireworks);
        $note->setHasGadgets($this->hasGadgets);
        $note->setHasExtraDancers($this->hasExtraDancers);

        $this->entityManager->flush();

        $this->saveStatus = 'Gespeichert';
    }

    #[LiveAction]
    public function setRating(#[LiveArg] string $category, #[LiveArg] int $rating): void
    {
        $prop = 'rating' . ucfirst($category);

        if (property_exists($this, $prop)) {
            if ($this->$prop === $rating) {
                $this->$prop = 0;
            } else {
                $this->$prop = $rating;
            }
            // Explicitly trigger save when rating changes
            $this->save();
        }
    }

    #[LiveAction]
    public function updateIsMissed(#[LiveArg] bool $value): void
    {
        $this->isMissed = $value;
        $this->save();
    }

    #[LiveAction]
    public function updateCheckbox(#[LiveArg] string $prop, #[LiveArg] bool $value): void
    {
        if (property_exists($this, $prop)) {
            $this->$prop = $value;
            $this->save();
        }
    }

    #[LiveAction]
    public function updateNote(): void
    {
        $this->save();
    }
}
