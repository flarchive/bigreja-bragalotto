<?php

namespace Bigreja\Bragalotto\Validator;

use Flarum\Foundation\AbstractValidator;
use Illuminate\Validation\Rule;

class SeasonValidator extends AbstractValidator
{
    protected function getRules(): array
    {
        $required = 'required';

        return [
            'name' => [$required, 'string', 'max:100'],
            'slug' => [
                $required,
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('bragalotto_seasons', 'slug'),
            ],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
        ];
    }

    protected function getMessages(): array
    {
        return [
            // GÜNCELLENDİ: lib.messages.slug_unique
            'slug.unique' => 'bigreja-bragalotto.lib.messages.slug_unique',
        ];
    }
}