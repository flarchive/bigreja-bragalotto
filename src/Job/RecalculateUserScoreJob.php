<?php

namespace Bigreja\Bragalotto\Job;

use Flarum\Queue\AbstractJob;
use Bigreja\Bragalotto\PickemScoringService; // Import the scoring service
use Flarum\User\User;
// Unused imports removed
// use Illuminate\Database\DatabaseManager;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Support\Carbon;

class RecalculateUserScoreJob extends AbstractJob
{
    /**
     * @var int
     */
    protected $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Inject the service directly into the handle method.
     * Flarum's Queue system will automatically resolve this dependency.
     *
     * @param PickemScoringService $scoringService
     */
    public function handle(PickemScoringService $scoringService)
    {
        if (User::find($this->userId) === null) {
            // If user doesn't exist, stop the job.
            return;
        }

        // The old logic (delete all scores, then create a new one) is removed.
        // We now call the centralized service, which performs a safe "upsert"
        // (update or create). This ensures logical consistency across the app.
        $scoringService->recalculateUserScore($this->userId);
    }
}