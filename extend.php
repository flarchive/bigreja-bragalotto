<?php

namespace Bigreja\Bragalotto;

use Flarum\Extend;
use Flarum\User\User;
use Bigreja\Bragalotto\Api\Controller;
use Bigreja\Bragalotto\Api\Resource;
use Bigreja\Bragalotto\Access\EventPolicy;
use Bigreja\Bragalotto\Access\PickPolicy;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\UserScore;
use Bigreja\Bragalotto\Listener;
use Bigreja\Bragalotto\Notification;
use Bigreja\Bragalotto\Command\ImportLigaPortugalCommand;
use Flarum\Api\Context;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Schema;

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

    // API Resources
    new Extend\ApiResource(Resource\TeamResource::class),
    new Extend\ApiResource(Resource\CompetitionResource::class),
    new Extend\ApiResource(Resource\SeasonResource::class),
    new Extend\ApiResource(Resource\WeekResource::class),
    new Extend\ApiResource(Resource\EventResource::class),
    new Extend\ApiResource(Resource\PickResource::class),
    new Extend\ApiResource(Resource\UserScoreResource::class),

    // API Routes
    (new Extend\Routes('api'))
        ->post('/bragalotto-events/{id}/result', 'bragalotto.events.result', Controller\EnterEventResultController::class)
        ->post('/bragalotto/recalculate-all-scores', 'bragalotto.recalculate_scores', Controller\RecalculateAllScoresController::class),

    // Forum Attributes
    (new Extend\ApiResource(ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('bragalotto.canManage')
                ->get(fn (object $forum, Context $context) => $context->getActor()->can('bragalotto.manage')),
            Schema\Boolean::make('bragalotto.canView')
                ->get(fn (object $forum, Context $context) => $context->getActor()->can('bragalotto.view')),
            Schema\Boolean::make('bragalotto.makePicks')
                ->get(fn (object $forum, Context $context) => $context->getActor()->can('bragalotto.makePicks')),
        ]),

    (new Extend\Settings())
        ->serializeToForum('bragalotto.reverseDisplay', 'bigreja-bragalotto.reverse_display', 'boolval'),

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
        ->listen(\Illuminate\Database\Eloquent\Events\Saved::class, Listener\UpdateUserScoresListener::class)
        ->listen(\Illuminate\Database\Eloquent\Events\Saved::class, Listener\SendResultNotificationsListener::class),

    // Notifications
    (new Extend\Notification())
        ->type(Notification\EventResultBlueprint::class, ['alert']),
];