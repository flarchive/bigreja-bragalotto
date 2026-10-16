<?php

namespace Bigreja\Bragalotto\Validator;

use Flarum\Foundation\AbstractValidator;
use Illuminate\Validation\Rule;

class TeamValidator extends AbstractValidator
{
    protected function getRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('bragalotto_teams', 'slug'),
            ],
            'logoPath' => ['nullable', 'string', 'url', 'max:255'],
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