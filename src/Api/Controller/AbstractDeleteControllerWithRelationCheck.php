<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractDeleteControllerWithRelationCheck extends AbstractDeleteController
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * The Eloquent model class name.
     *
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * The name of the Eloquent relation to check for existence.
     *
     * @return string
     */
    abstract protected function getRelationName(): string;

    /**
     * The translation key for the validation error message.
     *
     * @return string
     */
    abstract protected function getErrorMessageKey(): string;

    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $id = $request->getQueryParams()['id'];
        $modelClass = $this->getModelClass();
        
        /** @var Model $model */
        $model = $modelClass::findOrFail($id);

        $relationName = $this->getRelationName();

        if ($model->{$relationName}()->exists()) {
            throw new ValidationException([
                'message' => $this->translator->trans($this->getErrorMessageKey())
            ]);
        }

        $model->delete();
    }
}