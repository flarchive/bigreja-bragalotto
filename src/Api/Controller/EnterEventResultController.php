<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Foundation\ValidationException;
use Illuminate\Contracts\Translation\Translator;
use Bigreja\Bragalotto\Event;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Bigreja\Bragalotto\Job\ProcessEventResultsJob;

class EnterEventResultController implements RequestHandlerInterface
{
    protected Translator $translator;
    protected Dispatcher $bus;

    public function __construct(Translator $translator, Dispatcher $bus)
    {
        $this->translator = $translator;
        $this->bus = $bus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $event = Event::with(['homeTeam', 'awayTeam', 'week'])->findOrFail($id);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $homeScore = Arr::get($data, 'homeScore');
        $awayScore = Arr::get($data, 'awayScore');

        // 1. Skor Doğrulama
        if ($homeScore === null || $awayScore === null) {
            throw new ValidationException([
                'scores' => $this->translator->trans('bigreja-bragalotto.lib.messages.scores_required')
            ]);
        }

        // 2. Skorları Ata
        $event->home_score = (int) $homeScore;
        $event->away_score = (int) $awayScore;

        // 3. Statü Kontrolü ve Güncelleme
        // Modeldeki 'booted' metodu result'ı otomatik hesaplayacak.
        // Ancak statüyü 'finished' olmaya zorlamak, özellikle 'closed' durumundaki maçlar için önemlidir.
        if ($event->status !== Event::STATUS_FINISHED) {
            $event->status = Event::STATUS_FINISHED;
        }

        // Skip re-processing if this finished event already has the same result.
        if ($event->isDirty(['home_score', 'away_score', 'status'])) {
            $event->save();

            // 4. Bildirim ve Puan Hesaplama İşini Kuyruğa At
            $this->bus->dispatch(
                new ProcessEventResultsJob($event->id)
            );
        }

        // Güncel veriyi döndür
        $event->load(['homeTeam', 'awayTeam', 'week']);

        return new JsonResponse([
            'data' => [
                'type' => 'bragalotto-events',
                'id' => (string) $event->id,
                'attributes' => [
                    'weekId' => $event->week_id,
                    'homeTeamId' => $event->home_team_id,
                    'awayTeamId' => $event->away_team_id,
                    'matchDate' => $event->match_date ? $event->match_date->toIso8601String() : null,
                    'cutoffDate' => $event->cutoff_date ? $event->cutoff_date->toIso8601String() : null,
                    'allowDraw' => (bool) $event->allow_draw,
                    'status' => $event->status,
                    'homeScore' => $event->home_score,
                    'awayScore' => $event->away_score,
                    'result' => $event->result,
                    'canPick' => $event->canPick(),
                    'createdAt' => $event->created_at ? $event->created_at->toIso8601String() : null,
                    'updatedAt' => $event->updated_at ? $event->updated_at->toIso8601String() : null,
                ],
            ],
        ]);
    }
}