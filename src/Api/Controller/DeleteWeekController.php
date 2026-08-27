<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Bigreja\Bragalotto\Week;

class DeleteWeekController extends AbstractDeleteControllerWithRelationCheck
{
    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return Week::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationName(): string
    {
        return 'events';
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorMessageKey(): string
    {
        // GÜNCELLENDİ: lib.validation.errors.week_in_use -> lib.messages.in_use
        return 'bigreja-bragalotto.lib.messages.in_use';
    }
}