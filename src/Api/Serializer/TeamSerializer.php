<?php

namespace Bigreja\Bragalotto\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Bigreja\Bragalotto\Team;

class TeamSerializer extends AbstractSerializer
{
    protected $type = 'bragalotto-teams';

    protected function getDefaultAttributes($team)
    {
        return [
            'id' => (string) $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'logoPath' => $team->logo_path,
            'logoUrl' => $team->logo_url, 
            'createdAt' => $this->formatDate($team->created_at),
            'updatedAt' => $this->formatDate($team->updated_at),
        ];
    }
}