<?php

namespace Bigreja\Bragalotto\Search;

use Bigreja\Bragalotto\Week;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class WeekSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Week::query();
    }
}