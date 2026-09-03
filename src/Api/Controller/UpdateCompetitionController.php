<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\CompetitionSerializer;
use Bigreja\Bragalotto\Competition;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateCompetitionController extends AbstractShowController
{
    public $serializer = CompetitionSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $id          = Arr::get($request->getQueryParams(), 'id') ?? $request->getAttribute('id');
        $competition = Competition::findOrFail($id);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::snake($key)] = $value;
        }

        $competition->fill($attributes);
        $competition->save();

        return $competition;
    }
}
