<?php

namespace Bigreja\Bragalotto\Access;

use Flarum\User\User;
use Flarum\User\Access\AbstractPolicy; 
use Bigreja\Bragalotto\Event;

class EventPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param string $ability
     * @return bool|void
     */
    public function can(User $actor, string $ability)
    {
        // 'bragalotto.view' iznine sahip olanlar
        // 'view' (görüntüleme) yetkisine sahiptir.
        if ($actor->can('bragalotto.view') && $ability === 'view') {
            return true;
        }
    }

    /**
     * This will be called by the Serializer for each event.
     * * @param User $actor
     * @param Event $event
     * @return bool
     */
    public function view(User $actor, Event $event)
    {
        return $actor->can('bragalotto.view');
    }
}