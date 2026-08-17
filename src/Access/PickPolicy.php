<?php

namespace Bigreja\Bragalotto\Access;

use Flarum\User\User;
use Flarum\User\Access\AbstractPolicy;
use Bigreja\Bragalotto\Pick; 

class PickPolicy extends AbstractPolicy 
{
    /**
     * @param User $actor
     * @param string $ability
     * @return bool|void
     */
    public function can(User $actor, string $ability)
    {
        if ($actor->can('bragalotto.makePicks') && $ability === 'create') {
            return true;
        }

        if ($actor->can('bragalotto.makePicks') && $ability === 'view') {
            return true;
        }
    }

    /**
     * @param User $actor
     * @param Pick $pick
     * @return bool
     */
    public function edit(User $actor, Pick $pick)
    {
        // Check if the user owns the pick
        // AND the event is still in a 'pickable' state
        return $actor->id === $pick->user_id && $pick->event->canPick();
    }

    /**
     * @param User $actor
     * @param Pick $pick
     * @return bool
     */
    public function delete(User $actor, Pick $pick)
    {
        // Deletion logic is the same as editing
        return $this->edit($actor, $pick);
    }

    /**
     * @param User $actor
     * @param Pick $pick
     * @return bool
     */
    public function view(User $actor, Pick $pick)
    {
        return $actor->id === $pick->user_id || $actor->can('bragalotto.manage');
    }
}