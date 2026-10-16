<?php

namespace Bigreja\Bragalotto\Search;

use Bigreja\Bragalotto\Competition;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class CompetitionSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Competition::query();
    }
}