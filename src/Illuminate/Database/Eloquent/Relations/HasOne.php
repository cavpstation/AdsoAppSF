<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Database\Eloquent\Hydratable;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

class HasOne extends HasOneOrMany
{
    use SupportsDefaultModels;

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array   $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchOne($models, $results, $relation);
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param  \Illuminate\Contracts\Database\Eloquent\Hydratable|Model  $parent
     * @return \Illuminate\Contracts\Database\Eloquent\Hydratable|Model
     */
    public function newRelatedInstanceFor(Hydratable $parent)
    {
        return $this->related->newInstance()->setAttribute(
            $this->getForeignKeyName(), $parent->getAttribute($this->localKey)
        );
    }
}
