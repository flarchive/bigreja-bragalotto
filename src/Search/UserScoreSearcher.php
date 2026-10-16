<?php

namespace Bigreja\Bragalotto\Search;

use Bigreja\Bragalotto\UserScore;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class UserScoreSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return UserScore::query();
    }
}