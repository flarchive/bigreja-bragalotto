<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('bragalotto_events', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('week_id')->nullable();
            $table->unsignedInteger('home_team_id');
            $table->unsignedInteger('away_team_id');
            $table->dateTime('match_date');
            $table->dateTime('cutoff_date');
            $table->boolean('allow_draw')->default(false);
            $table->enum('status', ['scheduled', 'closed', 'finished'])->default('scheduled');
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->enum('result', ['home', 'away', 'draw'])->nullable();
            $table->timestamps();

            $table->foreign('week_id')->references('id')->on('bragalotto_weeks')->onDelete('cascade');
            $table->foreign('home_team_id')->references('id')->on('bragalotto_teams')->onDelete('cascade');
            $table->foreign('away_team_id')->references('id')->on('bragalotto_teams')->onDelete('cascade');
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('bragalotto_events');
    }
];
