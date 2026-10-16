<?php

namespace Bigreja\Bragalotto\Search;

use Bigreja\Bragalotto\Event;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class EventSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Event::query();
    }
}