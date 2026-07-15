<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\SeasonSerializer;
use Bigreja\Bragalotto\Season;
use Bigreja\Bragalotto\Validator\SeasonValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Carbon\Carbon;

class CreateSeasonController extends AbstractCreateController
{
    public $serializer = SeasonSerializer::class;

    protected $validator;

    public function __construct(SeasonValidator $validator)
    {
        $this->validator = $validator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        // --- YENİ MANTIK ---

        // 1. Specific logic: Auto-generate slug if empty
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug(Arr::get($data, 'name'));
        }

        // 2. Convert camelCase keys (startDate) to snake_case (start_date)
        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::snake($key)] = $value;
        }

        // 3. Handle date transformations
        if ($startDate = Arr::get($attributes, 'start_date')) {
            $attributes['start_date'] = Carbon::parse($startDate);
        }
        if ($endDate = Arr::get($attributes, 'end_date')) {
            $attributes['end_date'] = Carbon::parse($endDate);
        }

        // 4. Validate the converted attributes
        $this->validator->assertValid($attributes);

        // 5. Create the model using the $fillable array in Season.php
        $season = Season::create($attributes);
        // --- YENİ MANTIK SONU ---

        return $season;
    }
}