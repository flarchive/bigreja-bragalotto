<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('bragalotto_picks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('event_id');
            $table->enum('selected_outcome', ['home', 'away', 'draw']);
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('bragalotto_events')->onDelete('cascade');

            // Unique constraint: one pick per user per event
            $table->unique(['user_id', 'event_id']);
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('bragalotto_picks');
    }
];
