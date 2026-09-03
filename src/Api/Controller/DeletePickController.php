<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Translation\Translator; // Translator eklendi
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeletePickController extends AbstractDeleteController
{
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

    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $pick = Pick::findOrFail($id);
        
        // 1. Önce zaman aşımı kontrolü yap (Özel hata mesajı için)
        if (!$pick->event->canPick()) {
            throw new ValidationException([
                'message' => $this->translator->trans('bigreja-bragalotto.lib.messages.invalid_outcome')
            ]);
        }

        // 2. Sonra yetki kontrolü yap (Sahiplik vb.)
        // Policy de canPick kontrolü yapıyor ama yukarıdaki blok sayesinde
        // süre dolmuşsa kod buraya gelmeden exception fırlatacak.
        $actor->assertCan('delete', $pick);

        // Silme işleminden önce user ID'yi al
        $userId = $pick->user_id;

        $pick->delete();

        // Puanı yeniden hesapla
        $this->bus->dispatch(
            new RecalculateUserScoreJob($userId)
        );
    }
}