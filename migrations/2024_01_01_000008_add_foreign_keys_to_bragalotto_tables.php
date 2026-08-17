<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {

        $schema->table('bragalotto_events', function (Blueprint $table) {
            $table->foreign('week_id')->references('id')->on('bragalotto_weeks')->onDelete('cascade');
            $table->foreign('home_team_id')->references('id')->on('bragalotto_teams')->onDelete('cascade');
            $table->foreign('away_team_id')->references('id')->on('bragalotto_teams')->onDelete('cascade');
        });

        $schema->table('bragalotto_picks', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('bragalotto_events')->onDelete('cascade');
        });

        $schema->table('bragalotto_user_scores', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('bragalotto_seasons')->onDelete('cascade');
        });
        
        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->foreign('competition_id')
                  ->references('id')
                  ->on('bragalotto_competitions')
                  ->onDelete('set null');
        });

        $schema->table('bragalotto_competitions', function (Blueprint $table) {
            $table->foreign('season_id')
                  ->references('id')
                  ->on('bragalotto_seasons')
                  ->onDelete('cascade');

        });

    },
    'down' => function (Builder $schema) {
        $schema->table('bragalotto_user_scores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['season_id']);
        });

        $schema->table('bragalotto_picks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['event_id']);
        });

        $schema->table('bragalotto_events', function (Blueprint $table) {
            $table->dropForeign(['week_id']);
            $table->dropForeign(['home_team_id']);
            $table->dropForeign(['away_team_id']);
        });

        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
        });

        $schema->table('bragalotto_competitions', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
        });
        
    }
];
