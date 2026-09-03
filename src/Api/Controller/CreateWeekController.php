<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\WeekSerializer;
use Bigreja\Bragalotto\Week;
use Bigreja\Bragalotto\Validator\WeekValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str; // EKLENDİ
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Carbon\Carbon;

class CreateWeekController extends AbstractCreateController
{
    public $serializer = WeekSerializer::class;

    protected $validator;

    public function __construct(WeekValidator $validator)
    {
        $this->validator = $validator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        // --- YENİ MANTIK ---
        
        // 1. Convert camelCase keys (seasonId) to snake_case (season_id)
        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::snake($key)] = $value;
        }

        // 2. Handle date transformations
        if ($startDate = Arr::get($attributes, 'start_date')) {
            $attributes['start_date'] = Carbon::parse($startDate);
        }
        if ($endDate = Arr::get($attributes, 'end_date')) {
            $attributes['end_date'] = Carbon::parse($endDate);
        }

        // 3. Validate the converted attributes
        $this->validator->assertValid($attributes);

        // 4. Create the model using the $fillable array in Week.php
        $week = Week::create($attributes);
        // --- YENİ MANTIK SONU ---

        return $week;
    }
}