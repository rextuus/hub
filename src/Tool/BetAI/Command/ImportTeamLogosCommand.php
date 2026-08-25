<?php

namespace App\Tool\BetAI\Command;

use App\Tool\BetAI\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(name: 'app:bet-ai:import-logos', description: 'Import team logos from public/assets/bet_ai/logos')]
class ImportTeamLogosCommand extends Command
{
    private AsciiSlugger $slugger;

    private array $slugMapping = [
        'cologne' => '1-fc-koln',
        'union-berlin' => '1-fc-union-berlin',
        'mainz-05' => '1-fsv-mainz-05',
        'bayern-munich' => 'fc-bayern-munchen',
        'bayer-leverkusen' => 'bayer-04-leverkusen',
        'sc-paderborn' => 'sc-paderborn-07',
        'schalke-04' => 'fc-schalke-04',
        'borussia-moenchengladbach' => 'borussia-monchengladbach',
        'atletico-madrid' => 'atletico-de-madrid',
        'athletic-club-bilbao' => 'athletic-club',
        'real-valladolid' => 'real-valladolid-cf',
        'sporting-gijon' => 'real-sporting-de-gijon',
        'west-ham' => 'west-ham-united',
        'wrexham-afc' => 'wrexham',
        'tottenham' => 'tottenham-hotspur',
        'brighton' => 'brighton-hove-albion',
        'liverpool-fc' => 'liverpool',
        'paris-saint-germain-psg' => 'paris-saint-germain',
        'as-saint-etienne' => 'saint-etienne',
        'nancy-lorraine' => 'as-nancy',
        'juve-stabia' => 'ss-juve-stabia',
        'carrarese-calcio' => 'carrarese',
        'suditrol' => 'fc-sudtirol',
        'osnabrueck' => 'vfl-osnabruck',
        'fc-kaiserslautern' => '1-fc-kaiserslautern',
        'fc-nuernberg' => '1-fc-nurnberg',
        'st-pauli' => 'fc-st-pauli',
        'vfl-bochum' => 'vfl-bochum-1848',
        'fc-heidenheim' => '1-fc-heidenheim-1846',
        'spvgg-greuther-fuerth' => 'spvgg-greuther-furth',
        'malaga' => 'malaga-cf',
        'osasuna' => 'ca-osasuna',
        'sevilla' => 'sevilla-fc',
        'getafe' => 'getafe-cf',
        'barcelona' => 'fc-barcelona',
        'espanyol' => 'rcd-espanyol',
        'valencia' => 'valencia-cf',
        'elche' => 'elche-cf',
        'almeria' => 'ud-almeria',
        'ceuta' => 'ad-ceuta-fc',
        'oviedo' => 'real-oviedo',
        'las-palmas' => 'ud-las-palmas',
        'eibar' => 'sd-eibar',
        'castellon' => 'cd-castellon',
        'leganes' => 'cd-leganes',
        'sabadell' => 'ce-sabadell',
        'mallorca' => 'rcd-mallorca',
        'tenerife' => 'cd-tenerife',
        'cordoba' => 'cordoba-cf',
        'girona' => 'girona-fc',
        'albacete' => 'albacete-bp',
        'granada' => 'granada-cf',
        'newcastle' => 'newcastle-united',
        'ipswich' => 'ipswich-town',
        'toulouse' => 'toulouse-fc',
        'angers' => 'angers-sco',
        'rennes' => 'stade-rennais-fc',
        'auxerre' => 'aj-auxerre',
        'lille' => 'losc-lille',
        'troyes' => 'estac-troyes',
        'marseille' => 'olympique-de-marseille',
        'nice' => 'ogc-nice',
        'lorient-fc' => 'fc-lorient',
        'brest' => 'stade-brestois-29',
        'nantes' => 'fc-nantes',
        'montpellier' => 'montpellier-hsc',
        'annecy' => 'fc-annecy',
        'clermont-foot' => 'clermont-foot-63',
        'boulogne' => 'us-boulogne-co',
        'dunkerque' => 'usl-dunkerque',
        'catanzaro' => 'us-catanzaro-1929',
        'ascoli' => 'ascoli-calcio-1898-fc',
        'palermo' => 'palermo-fc',
        'empoli' => 'empoli-fc',
        'arezzo' => 'ss-arezzo',
        'benevento' => 'benevento-calcio',
        'sampdoria' => 'uc-sampdoria',
        'vicenza' => 'l-r-vicenza',
        'cremonese' => 'us-cremonese',
        'modena' => 'modena-fc',
        'padova' => 'calcio-padova',
        'pisa' => 'pisa-sc',
        'genoa' => 'genoa-cfc',
        'lazio' => 'ss-lazio',
        'atalanta' => 'atalanta-bc',
        'udinese' => 'udinese-calcio',
        'milan' => 'ac-milan',
        'fiorentina' => 'acf-fiorentina',
        'roma' => 'as-roma',
        'torino' => 'torino-fc',
        'venezia' => 'venezia-fc',
        'cagliari' => 'cagliari-calcio',
        'bologna' => 'bologna-fc-1909',
        'juventus' => 'juventus-fc',
        'inter' => 'inter-milan',
        'napoli' => 'ssc-napoli',
        'lecce' => 'us-lecce',
        'sassuolo' => 'us-sassuolo',
        'parma' => 'parma-calcio-1913',
        'energie-cottbus' => 'fc-energie-cottbus',
        'wolfsburg' => 'vfl-wolfsburg',
        'arminia-bielefeld' => 'dsc-arminia-bielefeld',
        'hoffenheim' => 'tsg-hoffenheim',
        'freiburg' => 'sc-freiburg',
        'werder-bremen' => 'sv-werder-bremen',
        'augsburg' => 'fc-augsburg',
        'deportivo-la-coru-a' => 'rc-deportivo',
    ];

    public function __construct(
        private TeamRepository $teamRepository,
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
        parent::__construct();
        $this->slugger = new AsciiSlugger();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Nur Simulation, ohne Datenbank-Updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $logoDir = $this->projectDir . '/public/assets/bet_ai/logos';

        if (!is_dir($logoDir)) {
            $io->error('Verzeichnis nicht gefunden: ' . $logoDir);
            return Command::FAILURE;
        }

        $finder = new Finder();
        $finder->files()->in($logoDir)->name(['*.png']);

        $teams = $this->teamRepository->findAll();
        $teamMap = [];
        foreach ($teams as $team) {
            $slug = $this->slugger->slug($team->getName())->lower()->toString();
            $teamMap[$slug] = $team;
        }

        $stats = [
            'png' => 0,
            'matched' => 0,
            'unmatched' => []
        ];

        $processedTeams = [];
        foreach ($finder as $file) {
            $dirName = basename(dirname($file->getRelativePathname()));

            // Ignoriere Verzeichnisse, die auf Ligen hindeuten
            if (preg_match('/(league|serie|bundesliga|championship|regionalliga|national-league|ligue)/', $dirName)) {
                continue;
            }

            // Try to resolve slug from dirName using mapping or direct match
            $dirSlug = $this->slugger->slug($dirName)->lower()->toString();
            $fileSlug = $this->slugMapping[$dirName] ?? $dirSlug;

            $team = null;
            if (isset($teamMap[$fileSlug])) {
                $team = $teamMap[$fileSlug];
            } else {
                // Try fuzzy match
                foreach ($teamMap as $slug => $t) {
                    if (str_contains($slug, $fileSlug) || str_contains($fileSlug, $slug)) {
                        $team = $t;
                        break;
                    }
                }
            }

            if ($team) {
                // If we already have a logo for this team, skip if we already have one
                if (isset($processedTeams[$team->getId()])) {
                    continue;
                }

                $team->setProfileImgUrl('/assets/bet_ai/logos/' . $file->getRelativePathname());
                $processedTeams[$team->getId()] = true;
                $stats['matched']++;
                $stats['png']++;
                $io->note('Logo für ' . $team->getName() . ' zugewiesen: ' . $file->getRelativePathname());
            } else {
                $stats['unmatched'][] = $dirName;
            }
        }

        // Calculate teams without logos after processing
        $teamsWithoutLogo = array_filter($teams, fn($team) => empty($team->getProfileImgUrl()));

        if (!$dryRun) {
            $this->entityManager->flush();
            $io->success($stats['matched'] . ' Logos erfolgreich zugewiesen.');
        } else {
            $io->warning('Dry-run Modus: Keine Änderungen wurden in der Datenbank gespeichert.');
        }

        $io->table(
            ['Typ', 'Anzahl'],
            [
                ['Gematched (Teams)', $stats['matched']],
                ['PNG Logos', $stats['png']],
                ['Teams ohne Logo', count($teamsWithoutLogo)],
            ]
        );

        if (!empty($stats['unmatched'])) {
            $io->section('Nicht zugeordnete Verzeichnisse (Vorschläge für mapping):');
            $io->listing(array_unique($stats['unmatched']));
        }

        if (!empty($teamsWithoutLogo)) {
            $io->section('Teams ohne Logo:');
            $io->listing(array_map(fn($team) => $team->getName(), $teamsWithoutLogo));
        }

        return Command::SUCCESS;
    }
}
