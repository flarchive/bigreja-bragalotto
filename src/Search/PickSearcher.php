<?php

namespace Bigreja\Bragalotto\Search;

use Bigreja\Bragalotto\Pick;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class PickSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Pick::query();
    }
}