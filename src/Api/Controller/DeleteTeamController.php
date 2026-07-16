<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Team;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Contracts\Translation\Translator;

class DeleteTeamController extends AbstractDeleteController
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $team = Team::findOrFail($id);

        if ($team->homeEvents()->exists() || $team->awayEvents()->exists()) {
            throw new ValidationException([
                // GÜNCELLENDİ: lib.validation.errors.team_in_use -> lib.messages.in_use
                'message' => $this->translator->trans('bigreja-bragalotto.lib.messages.in_use')
            ]);
        }

        $team->delete();
    }
}