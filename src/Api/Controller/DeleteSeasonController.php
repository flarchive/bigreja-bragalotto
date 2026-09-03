<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Bigreja\Bragalotto\Season;

class DeleteSeasonController extends AbstractDeleteControllerWithRelationCheck
{
    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return Season::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationName(): string
    {
        return 'weeks';
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorMessageKey(): string
    {
        // GÜNCELLENDİ: lib.validation.errors.season_in_use -> lib.messages.in_use
        return 'bigreja-bragalotto.lib.messages.in_use';
    }
}