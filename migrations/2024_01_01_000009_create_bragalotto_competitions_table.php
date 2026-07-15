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
            $table->foreign('season_id')
                  ->references('id')
                  ->on('bragalotto_seasons')
                  ->onDelete('cascade');
        });

        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->unsignedInteger('competition_id')->nullable()->after('season_id');

            $table->foreign('competition_id')
                  ->references('id')
                  ->on('bragalotto_competitions')
                  ->onDelete('set null');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropColumn('competition_id');
        });
        $schema->dropIfExists('bragalotto_competitions');
    },
];
