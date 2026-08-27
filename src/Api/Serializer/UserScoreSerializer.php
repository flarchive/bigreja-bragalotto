<?php

namespace Bigreja\Bragalotto\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Bigreja\Bragalotto\UserScore;

class UserScoreSerializer extends AbstractSerializer
{
    protected $type = 'bragalotto-user-scores';

    /**
     * @param UserScore $score
     * @return array
     */
    protected function getDefaultAttributes($score)
    {
        return [
            'id' => (string) $score->id,
            'userId' => $score->user_id,
            'seasonId' => $score->season_id,
            'totalPoints' => (int) $score->total_points,
            'totalPicks' => (int) $score->total_picks,
            'correctPicks' => (int) $score->correct_picks,
            'accuracy' => $score->getAccuracyPercentage(),
            'rank' => $score->calculatedRank ?? (int) $score->calculateRank(),
            'createdAt' => $score->created_at ? $this->formatDate($score->created_at) : null,
            'updatedAt' => $score->updated_at ? $this->formatDate($score->updated_at) : null,
        ];
    }

    public function user($score)
    {
        if (!$score->user) {
            return null;
        }
        return $this->hasOne($score, BasicUserSerializer::class, 'user');
    }

    public function season($score)
    {
        if (!$score->season) {
            return null;
        }
        return $this->hasOne($score, SeasonSerializer::class, 'season');
    }
}