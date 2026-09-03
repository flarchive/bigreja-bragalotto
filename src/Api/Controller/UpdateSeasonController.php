<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\SeasonSerializer;
use Bigreja\Bragalotto\Season;
use Bigreja\Bragalotto\Validator\SeasonValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str; // EKLENDİ
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Carbon\Carbon;

class UpdateSeasonController extends AbstractShowController
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

        $id = Arr::get($request->getQueryParams(), 'id');
        $season = Season::findOrFail($id);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        // --- YENİ MANTIK ---
        // 1. Gelen camelCase (startDate) veriyi snake_case (start_date) veriye dönüştür
        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::snake($key)] = $value;
        }

        // 2. Modeli ve veriyi doğrula
        $this->validator->model = $season;
        $this->validator->assertValid($attributes);

        // 3. Tarih alanlarını Carbon nesnesine dönüştür
        if ($startDate = Arr::get($attributes, 'start_date')) {
            $attributes['start_date'] = Carbon::parse($startDate);
        }
        if ($endDate = Arr::get($attributes, 'end_date')) {
            $attributes['end_date'] = Carbon::parse($endDate);
        }

        // 4. Modeli $fillable kullanarak doldur ve kaydet
        $season->fill($attributes);
        $season->save();
        // --- YENİ MANTIK SONU ---

        return $season;
    }
}