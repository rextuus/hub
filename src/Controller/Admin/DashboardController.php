<?php

namespace App\Controller\Admin;

use App\Tool\Advent\Controller\Admin\AdventAnswerCrudController;
use App\Tool\Advent\Controller\Admin\AdventCalendarCrudController;
use App\Tool\Advent\Controller\Admin\AdventChoiceCrudController;
use App\Tool\Advent\Controller\Admin\AdventDoorCrudController;
use App\Tool\Advent\Controller\Admin\AdventParticipantCrudController;
use App\Tool\Advent\Controller\Admin\AdventWinStageCrudController;
use App\Tool\EscVoting\Controller\Admin\CountryCrudController;
use App\Tool\Fotokalender\Controller\Admin\PhotoCalendarCrudController;
use App\Tool\Fotokalender\Controller\Admin\PhotoCalendarMonthCrudController;
use App\Tool\Fotokalender\Controller\Admin\PhotoCalendarPhotoCrudController;
use App\Tool\EscVoting\Controller\Admin\VoteCrudController;
use App\Tool\EscVoting\Controller\Admin\VoterCrudController;
use App\Tool\EscVoting\Controller\Admin\BallotCrudController;
use App\Tool\EscVoting\Controller\Admin\EscEditionCrudController;
use App\Tool\EscVoting\Controller\Admin\ParticipantCrudController;
use App\Controller\Admin\BetSuggestionCrudController;
use App\Controller\Admin\TeamCrudController;
use App\Controller\Admin\BankrollCrudController;
use App\Controller\Admin\TransactionCrudController;
use App\Controller\Admin\GameWeekCrudController;
use App\Controller\Admin\AiResponseCrudController;
use App\Controller\Admin\BetAISettingCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly Packages $assets,
    ) {
    }

    public function index(): Response
    {
        return $this->redirectToRoute('admin_user_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle(\sprintf('<img src="%s" alt="" style="height: 28px; vertical-align: middle; margin-right: 8px;"> WH-Company', $this->assets->getUrl('images/brand/logo-mark.svg')))
            ->setFaviconPath($this->assets->getUrl('images/brand/favicon.svg'));
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(ProjectCrudController::class, 'Projekte', 'fas fa-layer-group');
        yield MenuItem::linkTo(UserCrudController::class, 'Benutzer', 'fas fa-user');
        yield MenuItem::linkToRoute('Startseite', 'fas fa-external-link-alt', 'app_landing_page');

        yield MenuItem::section('Fotokalender');
        yield MenuItem::linkTo(PhotoCalendarCrudController::class, 'Kalender', 'fas fa-book-open');
        yield MenuItem::linkTo(PhotoCalendarMonthCrudController::class, 'Monatsblätter', 'fas fa-calendar');
        yield MenuItem::linkTo(PhotoCalendarPhotoCrudController::class, 'Fotos', 'fas fa-images');

        yield MenuItem::section('Adventskalender');
        yield MenuItem::linkTo(AdventCalendarCrudController::class, 'Kalender', 'fas fa-gifts');
        yield MenuItem::linkTo(AdventDoorCrudController::class, 'Türen & Fragen', 'fas fa-door-open');
        yield MenuItem::linkTo(AdventWinStageCrudController::class, 'Gewinnstufen', 'fas fa-trophy');
        yield MenuItem::linkTo(AdventParticipantCrudController::class, 'Teilnehmer', 'fas fa-users');
        yield MenuItem::subMenu('Mehr', 'fas fa-ellipsis-h')->setSubItems([
            MenuItem::linkTo(AdventChoiceCrudController::class, 'Alle Antworten', 'fas fa-list'),
            MenuItem::linkTo(AdventAnswerCrudController::class, 'Gegebene Antworten', 'fas fa-check-double'),
        ]);

        yield MenuItem::section('ESC Voting');
        yield MenuItem::linkToRoute('Öffentliche Seite', 'fas fa-external-link-alt', 'app_esc_voting_index');
        yield MenuItem::linkTo(EscEditionCrudController::class, 'Editionen', 'fas fa-calendar-alt');

        yield MenuItem::subMenu('Stimmenverwaltung', 'fas fa-vote-yea')->setSubItems([
            MenuItem::linkTo(BallotCrudController::class, 'Stimmzettel', 'fas fa-envelope-open-text'),
            MenuItem::linkTo(VoteCrudController::class, 'Einzelstimmen', 'fas fa-star'),
        ]);

        yield MenuItem::subMenu('Stammdaten', 'fas fa-database')->setSubItems([
            MenuItem::linkTo(CountryCrudController::class, 'Länder', 'fas fa-globe'),
            MenuItem::linkTo(ParticipantCrudController::class, 'Teilnehmer', 'fas fa-music'),
            MenuItem::linkTo(VoterCrudController::class, 'Voter', 'fas fa-users'),
        ]);

        yield MenuItem::section('BetAI');
        yield MenuItem::linkTo(GameWeekCrudController::class, 'Spielwochen', 'fas fa-calendar-week');
        yield MenuItem::linkTo(BetSuggestionCrudController::class, 'Wettvorschläge', 'fas fa-futbol');
        yield MenuItem::linkTo(AiResponseCrudController::class, 'KI-Antworten', 'fas fa-robot');
        yield MenuItem::linkTo(TeamCrudController::class, 'Teams', 'fas fa-shield-alt');
        yield MenuItem::linkTo(BankrollCrudController::class, 'Bankroll', 'fas fa-money-bill-wave');
        yield MenuItem::linkTo(TransactionCrudController::class, 'Transaktionen', 'fas fa-exchange-alt');
        yield MenuItem::linkTo(BetAISettingCrudController::class, 'Einstellungen', 'fas fa-cog');
    }
}
