<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('bragalotto_user_scores', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('season_id')->nullable();
            $table->integer('total_points')->default(0);
            $table->integer('total_picks')->default(0);
            $table->integer('correct_picks')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('bragalotto_seasons')->onDelete('cascade');

            // Unique constraint: one score record per user per season
            $table->unique(['user_id', 'season_id']);
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('bragalotto_user_scores');
    }
];
