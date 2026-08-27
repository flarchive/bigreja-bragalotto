<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('bragalotto_competitions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('external_id', 100)->nullable();
            $table->unsignedInteger('season_id');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            
            $table->unique(['external_id', 'season_id']);
        });

    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('bragalotto_competitions');
    },
];
