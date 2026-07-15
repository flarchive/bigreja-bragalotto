<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('bragalotto_teams', function (Blueprint $table) {
            $table->string('external_id', 100)->nullable()->unique()->after('id');
        });
        $schema->table('bragalotto_seasons', function (Blueprint $table) {
            $table->string('external_id', 100)->nullable()->unique()->after('id');
        });
        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->string('external_id', 100)->nullable()->after('id');
        });
        $schema->table('bragalotto_events', function (Blueprint $table) {
            $table->string('external_id', 100)->nullable()->unique()->after('id');
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('bragalotto_teams', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
        $schema->table('bragalotto_seasons', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
        $schema->table('bragalotto_weeks', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
        $schema->table('bragalotto_events', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    },
];
