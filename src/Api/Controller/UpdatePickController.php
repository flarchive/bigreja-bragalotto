<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\PickSerializer;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdatePickController extends AbstractShowController
{
    public $serializer = PickSerializer::class;
    public $include = ['event', 'event.homeTeam', 'event.awayTeam', 'user'];

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Dispatcher $bus
     * @param Translator $translator
     */
    public function __construct(Dispatcher $bus, Translator $translator)
    {
        $this->bus = $bus;
        $this->translator = $translator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');
        
        $pick = Pick::with('event')->findOrFail($id);
        
        // 1. Önce Zaman Aşımı Kontrolü (Cutoff)
        // Policy'den önce burayı kontrol ediyoruz ki özel hata mesajımız dönsün.
        if (!$pick->event->canPick()) {
            // GÜNCELLENDİ: Özel 'cutoff_passed' yerine genel 'invalid_outcome' kullanıyoruz.
            throw new ValidationException([
                'message' => $this->translator->trans('bigreja-bragalotto.lib.messages.invalid_outcome')
            ]);
        }

        // 2. Sonra Yetki Kontrolü (Kullanıcı kendi tahminini mi düzenliyor?)
        $actor->assertCan('edit', $pick);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $selectedOutcome = Arr::get($data, 'selectedOutcome');

        if (!$selectedOutcome) {
             return $pick;
        }

        // Beraberlik kontrolü
        if ($selectedOutcome === Event::RESULT_DRAW && !$pick->event->allow_draw) {
            throw new ValidationException([
                'message' => $this->translator->trans('bigreja-bragalotto.lib.messages.invalid_outcome')
            ]);
        }
        
        // Geçerli sonuç tipi kontrolü
        $validOutcomes = [Event::RESULT_HOME, Event::RESULT_AWAY, Event::RESULT_DRAW];
        if (!in_array($selectedOutcome, $validOutcomes)) {
            throw new ValidationException([
                'message' => $this->translator->trans('bigreja-bragalotto.lib.messages.invalid_outcome')
            ]);
        }

        $recalculateScore = false;

        if ($pick->selected_outcome !== $selectedOutcome) {
            $pick->selected_outcome = $selectedOutcome;
            $pick->is_correct = null; 

            // Normalde canPick() kontrolü süresi geçmiş maçları engeller ama
            // yine de ekstra güvenlik olarak kalabilir.
            if ($pick->event->isFinished()) {
                $recalculateScore = true;
            }
        }
        
        $pick->save();

        if ($recalculateScore) {
            $this->bus->dispatch(
                new RecalculateUserScoreJob($pick->user_id)
            );
        }

        $pick->load(['event.homeTeam', 'event.awayTeam', 'event.week', 'user']);

        return $pick;
    }
}