<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\Foundation\ValidationException;
use Illuminate\Contracts\Translation\Translator;
use Bigreja\Bragalotto\Api\Serializer\EventSerializer;
use Bigreja\Bragalotto\Event;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Illuminate\Contracts\Bus\Dispatcher;
use Bigreja\Bragalotto\Job\ProcessEventResultsJob;

class EnterEventResultController extends AbstractShowController
{
    public $serializer = EventSerializer::class;
    public $include = ['homeTeam', 'awayTeam', 'week'];

    protected $translator;
    protected $bus;

    public function __construct(Translator $translator, Dispatcher $bus)
    {
        $this->translator = $translator;
        $this->bus = $bus;
    }

    protected function data(ServerRequestInterface $request, Document $document)
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

        $event->save();

        // 4. Bildirim ve Puan Hesaplama İşini Kuyruğa At
        $this->bus->dispatch(
            new ProcessEventResultsJob($event->id)
        );

        // Güncel veriyi döndür
        $event->load(['homeTeam', 'awayTeam', 'week']);

        return $event;
    }
}