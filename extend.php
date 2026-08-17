<?php

namespace Bigreja\Bragalotto;

use Flarum\Extend;
use Flarum\User\User;
use Bigreja\Bragalotto\Api\Controller;
use Bigreja\Bragalotto\Api\Serializer;
use Flarum\Api\Serializer\ForumSerializer;
use Bigreja\Bragalotto\Access\EventPolicy;
use Bigreja\Bragalotto\Access\PickPolicy;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\UserScore;
use Bigreja\Bragalotto\Listener;
use Bigreja\Bragalotto\Notification;
use Bigreja\Bragalotto\Command\ImportLigaPortugalCommand;

return [
    // Console Commands
    (new Extend\Console())
        ->command(ImportLigaPortugalCommand::class),

    // Frontend Assets
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less')
        ->route('/bragalotto', 'bragalotto'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    // Localization
    new Extend\Locales(__DIR__.'/resources/locale'),

    // API Routes
    (new Extend\Routes('api'))
        // Teams (Admin)
        ->get('/bragalotto-teams', 'bragalotto.teams.index', Controller\ListTeamsController::class)
        ->post('/bragalotto-teams', 'bragalotto.teams.create', Controller\CreateTeamController::class)
        ->patch('/bragalotto-teams/{id}', 'bragalotto.teams.update', Controller\UpdateTeamController::class)
        ->delete('/bragalotto-teams/{id}', 'bragalotto.teams.delete', Controller\DeleteTeamController::class)

        // Competitions (Admin)
        ->get('/bragalotto-competitions', 'bragalotto.competitions.index', Controller\ListCompetitionsController::class)
        ->post('/bragalotto-competitions', 'bragalotto.competitions.create', Controller\CreateCompetitionController::class)
        ->patch('/bragalotto-competitions/{id}', 'bragalotto.competitions.update', Controller\UpdateCompetitionController::class)
        ->delete('/bragalotto-competitions/{id}', 'bragalotto.competitions.delete', Controller\DeleteCompetitionController::class)
        
        // Seasons (Admin)
        ->get('/bragalotto-seasons', 'bragalotto.seasons.index', Controller\ListSeasonsController::class)
        ->post('/bragalotto-seasons', 'bragalotto.seasons.create', Controller\CreateSeasonController::class)
        ->patch('/bragalotto-seasons/{id}', 'bragalotto.seasons.update', Controller\UpdateSeasonController::class)
        ->delete('/bragalotto-seasons/{id}', 'bragalotto.seasons.delete', Controller\DeleteSeasonController::class)
        
        // Weeks (Admin)
        ->get('/bragalotto-weeks', 'bragalotto.weeks.index', Controller\ListWeeksController::class)
        ->post('/bragalotto-weeks', 'bragalotto.weeks.create', Controller\CreateWeekController::class)
        ->patch('/bragalotto-weeks/{id}', 'bragalotto.weeks.update', Controller\UpdateWeekController::class)
        ->delete('/bragalotto-weeks/{id}', 'bragalotto.weeks.delete', Controller\DeleteWeekController::class)
        
        // Events (Matches)
        ->get('/bragalotto-events', 'bragalotto.events.index', Controller\ListEventsController::class)
        ->post('/bragalotto-events', 'bragalotto.events.create', Controller\CreateEventController::class)
        ->patch('/bragalotto-events/{id}', 'bragalotto.events.update', Controller\UpdateEventController::class)
        ->delete('/bragalotto-events/{id}', 'bragalotto.events.delete', Controller\DeleteEventController::class)
	    ->post('/bragalotto-events/{id}/result', 'bragalotto.events.result', Controller\EnterEventResultController::class)

        // Picks
        ->get('/bragalotto-picks', 'bragalotto.picks.index', Controller\ListPicksController::class)
        ->post('/bragalotto-picks', 'bragalotto.picks.create', Controller\CreatePickController::class)
        ->patch('/bragalotto-picks/{id}', 'bragalotto.picks.update', Controller\UpdatePickController::class)
        ->delete('/bragalotto-picks/{id}', 'bragalotto.picks.delete', Controller\DeletePickController::class)
        
        // Leaderboard
        ->get('/bragalotto-user-scores', 'bragalotto.leaderboard.index', Controller\ListLeaderboardController::class)

        // Public (Forum) data for filters
        ->get('/bragalotto-public-seasons', 'bragalotto.public.seasons.index', Controller\ListPublicSeasonsController::class)
        ->get('/bragalotto-public-teams', 'bragalotto.public.teams.index', Controller\ListPublicTeamsController::class)
        ->get('/bragalotto-public-weeks', 'bragalotto.public.weeks.index', Controller\ListPublicWeeksController::class)
        ->get('/bragalotto-public-competitions', 'bragalotto.public.competitions.index', Controller\ListPublicCompetitionsController::class)

        // Admin Tools
        ->post('/bragalotto/recalculate-all-scores', 'bragalotto.recalculate_scores', Controller\RecalculateAllScoresController::class),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(function ($serializer) { 
            $actor = $serializer->getActor();
            $settings = resolve(\Flarum\Settings\SettingsRepositoryInterface::class);
            
            return [
                'bragalotto.canManage' => $actor->can('bragalotto.manage'),
                'bragalotto.canView' => $actor->can('bragalotto.view'),
                'bragalotto.makePicks' => $actor->can('bragalotto.makePicks'),
                'bragalotto.reverseDisplay' => (bool) $settings->get('bigreja-bragalotto.reverse_display'),
            ];
        }),

    // Policies
    (new Extend\Policy())
        ->modelPolicy(Event::class, EventPolicy::class)
        ->modelPolicy(Pick::class, PickPolicy::class),

    // User Model Relationships
    (new Extend\Model(User::class))
        ->relationship('bragalottoPicks', function ($model) {
            return $model->hasMany(Pick::class, 'user_id');
        })
        ->relationship('bragalottoScores', function ($model) {
            return $model->hasMany(UserScore::class, 'user_id');
        }),

    // Event Listeners
    (new Extend\Event())
        ->listen(\Illuminate\Database\Events\Saved::class, Listener\UpdateUserScoresListener::class)
        ->listen(\Illuminate\Database\Events\Saved::class, Listener\SendResultNotificationsListener::class),

    // Notifications
    (new Extend\Notification())
        ->type(Notification\EventResultBlueprint::class, Serializer\EventSerializer::class, ['alert']),
];