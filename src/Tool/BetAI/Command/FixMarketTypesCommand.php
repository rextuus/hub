<?php

namespace App\Tool\BetAI\Command;

use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Enum\BetMarketType;
use App\Tool\BetAI\Repository\BetSuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:bet-ai:fix-market-types',
    description: 'Fixes unknown market types in BetSuggestion entities',
)]
class FixMarketTypesCommand extends Command
{
    public function __construct(
        private BetSuggestionRepository $betSuggestionRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $suggestions = $this->betSuggestionRepository->findBy(['marketType' => BetMarketType::UNKNOWN]);

        if (empty($suggestions)) {
            $io->success('Keine BetSuggestions mit unbekanntem Markttyp gefunden.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Gefundene Einträge: %d', count($suggestions)));

        $updated = 0;
        foreach ($suggestions as $suggestion) {
            $newMarketType = BetMarketType::fromMarketName($suggestion->getMarket());

            if ($newMarketType !== BetMarketType::UNKNOWN) {
                $io->text(sprintf('Aktualisiere ID %d: %s -> %s', $suggestion->getId(), $suggestion->getMarket(), $newMarketType->value));
                $suggestion->setMarketType($newMarketType);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d Einträge aktualisiert.', $updated));
        } else {
            $io->warning('Keine passenden Markttypen für die gefundenen Einträge gefunden.');
        }

        return Command::SUCCESS;
    }
}
