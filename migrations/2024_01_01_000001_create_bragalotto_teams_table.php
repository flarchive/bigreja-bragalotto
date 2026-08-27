<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('bragalotto_teams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('external_id', 100)->nullable();
            $table->string('name');
            $table->string('full_name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('bragalotto_teams');
    }
];
