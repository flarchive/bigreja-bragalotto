<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('bragalotto_weeks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('external_id', 100)->nullable();
            $table->string('name');
            $table->unsignedInteger('competition_id')->nullable();
            $table->integer('week_number')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('bragalotto_weeks');
    }
];
