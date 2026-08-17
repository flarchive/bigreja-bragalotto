<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\EventSerializer;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Validator\EventValidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Carbon\Carbon;

class UpdateEventController extends AbstractShowController
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

        $id = Arr::get($request->getQueryParams(), 'id');
        $event = Event::with(['homeTeam', 'awayTeam', 'week'])->findOrFail($id);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[Str::snake($key)] = $value;
        }

        $this->validator->model = $event;
        $this->validator->assertValid($attributes);

        if ($matchDate = Arr::get($attributes, 'match_date')) {
            $attributes['match_date'] = Carbon::parse($matchDate);
        }
        if ($cutoffDate = Arr::get($attributes, 'cutoff_date')) {
            $attributes['cutoff_date'] = Carbon::parse($cutoffDate);
        }

        $event->fill($attributes);
        $event->save();

        $event->load(['homeTeam', 'awayTeam', 'week']);

        return $event;
    }
}