<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->unsignedInteger('season_id')->nullable()->after('id');
        });
    },
];
