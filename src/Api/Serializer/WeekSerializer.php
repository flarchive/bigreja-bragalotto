<?php

namespace Bigreja\Bragalotto\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Bigreja\Bragalotto\Week;

class WeekSerializer extends AbstractSerializer
{
    protected $type = 'bragalotto-weeks';

    protected function getDefaultAttributes($week)
    {
        return [
            'id' => (string) $week->id,
            'name' => $week->name,
            'competitionId' => $week->competition_id,
            'weekNumber' => $week->week_number,
            'startDate' => $this->formatDate($week->start_date),
            'endDate' => $this->formatDate($week->end_date),
            'createdAt' => $this->formatDate($week->created_at),
            'updatedAt' => $this->formatDate($week->updated_at),
        ];
    }

    public function competition($week)
    {
        if (!$week->competition) {
            return null;
        }
        return $this->hasOne($week, CompetitionSerializer::class);
    }

    public function events($week)
    {
        if (!$week->events) {
            return null;
        }
        return $this->hasMany($week, EventSerializer::class);
    }
}