<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\EventSerializer;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Validator\EventValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Carbon\Carbon;

class CreateEventController extends AbstractCreateController
{
    public $serializer = EventSerializer::class;
    public $include = ['homeTeam', 'awayTeam', 'week'];

    protected $validator;

    public function __construct(EventValidator $validator)
    {
        $this->validator = $validator;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::snake($key)] = $value;
        }

        if ($matchDate = Arr::get($attributes, 'match_date')) {
            $attributes['match_date'] = Carbon::parse($matchDate);
        }
        if ($cutoffDate = Arr::get($attributes, 'cutoff_date')) {
            $attributes['cutoff_date'] = Carbon::parse($cutoffDate);
        }

        $attributes['allow_draw'] = Arr::get($attributes, 'allow_draw', false);
        $attributes['status'] = Arr::get($attributes, 'status', 'scheduled');

        $this->validator->assertValid($attributes);

        return Event::create($attributes);
    }
}