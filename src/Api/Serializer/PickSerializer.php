<?php

namespace Bigreja\Bragalotto\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Bigreja\Bragalotto\Pick;

class PickSerializer extends AbstractSerializer
{
    protected $type = 'bragalotto-picks';

    /**
     * Get the default set of serialized attributes for a model.
     *
     * @param Pick $pick
     * @return array
     */
    protected function getDefaultAttributes($pick)
    {
        return [
            'id' => (string) $pick->id,
            'userId' => $pick->user_id,
            'eventId' => $pick->event_id,
            'selectedOutcome' => $pick->selected_outcome,
            'isCorrect' => $pick->is_correct,
            'createdAt' => $this->formatDate($pick->created_at),
            'updatedAt' => $this->formatDate($pick->updated_at),
        ];
    }

    /**
     * Get user relationship
     */
    public function user($pick)
    {
        if (!$pick->user) {
            return null;
        }
        return $this->hasOne($pick, BasicUserSerializer::class, 'user');
    }

    /**
     * Get event relationship
     */
    public function event($pick)
    {
        if (!$pick->event) {
            return null;
        }
        return $this->hasOne($pick, EventSerializer::class, 'event');
    }
}