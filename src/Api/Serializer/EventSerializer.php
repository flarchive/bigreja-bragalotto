<?php

namespace Bigreja\Bragalotto\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Bigreja\Bragalotto\Event;

class EventSerializer extends AbstractSerializer
{
    protected $type = 'bragalotto-events';

    /**
     * Get the default set of serialized attributes for a model.
     *
     * @param Event $event
     * @return array
     */
    protected function getDefaultAttributes($event)
    {
        return [
            'id' => (string) $event->id,
            'weekId' => $event->week_id,
            'homeTeamId' => $event->home_team_id,
            'awayTeamId' => $event->away_team_id,
            'matchDate' => $this->formatDate($event->match_date),
            'cutoffDate' => $this->formatDate($event->cutoff_date),
            'allowDraw' => (bool) $event->allow_draw,
            'status' => $event->status,
            'homeScore' => $event->home_score,
            'awayScore' => $event->away_score,
            'result' => $event->result,
            'canPick' => $event->canPick(),
            'createdAt' => $this->formatDate($event->created_at),
            'updatedAt' => $this->formatDate($event->updated_at),
        ];
    }

    /**
     * Get week relationship
     */
    public function week($event)
    {
        if (!$event->week) {
            return null;
        }
        return $this->hasOne($event, WeekSerializer::class, 'week');
    }

    /**
     * Get home team relationship
     */
    public function homeTeam($event)
    {
        if (!$event->homeTeam) {
            return null;
        }
        return $this->hasOne($event, TeamSerializer::class, 'homeTeam');
    }

    /**
     * Get away team relationship
     */
    public function awayTeam($event)
    {
        if (!$event->awayTeam) {
            return null;
        }
        return $this->hasOne($event, TeamSerializer::class, 'awayTeam');
    }

    /**
     * Get picks relationship
     */
    public function picks($event)
    {
        // DÜZELTME: Bir maçın hiç tahmini (pick) olmayabilir.
        // Bu null kontrolü, Flarum'un çökmesini engeller.
        if (!$event->picks) {
            return null;
        }
        return $this->hasMany($event, PickSerializer::class, 'picks');
    }
}