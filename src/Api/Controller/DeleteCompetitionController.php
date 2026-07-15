<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Bigreja\Bragalotto\Competition;

class DeleteCompetitionController extends AbstractDeleteControllerWithRelationCheck
{
    protected function getModelClass(): string
    {
        return Competition::class;
    }

    protected function getRelationName(): string
    {
        return 'weeks';
    }

    protected function getErrorMessageKey(): string
    {
        return 'bigreja-bragalotto.lib.messages.in_use';
    }
}
