<?php

namespace Bigreja\Bragalotto\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Bigreja\Bragalotto\Competition;

class CompetitionSerializer extends AbstractSerializer
{
    protected $type = 'bragalotto-competitions';

    protected function getDefaultAttributes($competition)
    {
        return [
            'id'          => (string) $competition->id,
            'externalId'  => $competition->external_id,
            'seasonId'    => $competition->season_id,
            'name'        => $competition->name,
            'slug'        => $competition->slug,
            'createdAt'   => $this->formatDate($competition->created_at),
            'updatedAt'   => $this->formatDate($competition->updated_at),
        ];
    }

    public function season($competition)
    {
        return $this->hasOne($competition, SeasonSerializer::class);
    }
}
