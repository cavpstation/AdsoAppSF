<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * HasOneThrough relation defines a one-to-one relationship between two models that uses a third
 * intermediate model as the connection:
 *
 * <code>
 * select * from Related
 * inner join Parent    on Parent.ForeignKey    = Related.PrimaryKey
 * inner join FarParent on FarParent.ForeignKey = Parent.PrimaryKey
 * where
 *     FarParent.PrimaryKey = ?
 * and FarParent.PrimaryKey is not null
 * </code>
 *
 *
 * @package Illuminate\Database\Eloquent\Relations
 * @author  Peter Scopes <peter.scopes@gmail.com>
 */
class HasOneThrough extends Relation
{
    /**
     * The distance parent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $farParent;

    /**
     * The foreign key of the far parent model.
     *
     * @var string
     */
    protected $farParentKey;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $parentKey;


    /**
     * Create a new has one through relationship instance.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model   $farParent
     * @param \Illuminate\Database\Eloquent\Model   $parent
     * @param string                                $farParentKey Foreign key to parent
     * @param string                                $parentKey    Foreign key to related
     */
    public function __construct(Builder $query, Model $farParent, Model $parent, $farParentKey, $parentKey)
    {
        $this->parentKey    = $parentKey;
        $this->farParentKey = $farParentKey;
        $this->farParent    = $farParent;

        parent::__construct($query, $parent);
    }

    /**
     * Get the far parent model of the relation.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getFarParent()
    {
        return $this->farParent;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $farParentLocalKey = $this->farParent->getQualifiedKeyName();

        $this->setJoin();

        if (static::$constraints) {
            $this->query->where($farParentLocalKey, '=', $this->farParent->getKey());

            $this->query->whereNotNull($farParentLocalKey);
        }
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parent
     * @param  array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationQuery(Builder $query, Builder $parent, $columns = ['*'])
    {
        $this->setJoin($query);

        $query->select($columns);

        $this->query->where($this->farParent->getQualifiedKeyName(), '=', $this->farParent->getKey());

        return $this->query->whereNotNull($this->farParent->getQualifiedKeyName());
    }

    /**
     * @param Builder|null $query
     */
    protected function setJoin(Builder $query = null)
    {
        $query = $query ?: $this->query;

        $relatedLocalKey     = $this->related->getQualifiedKeyName();
        $parentForeignKey    = $this->parent->getTable().'.'.$this->parentKey;
        $parentLocalKey      = $this->parent->getQualifiedKeyName();
        $farParentForeignKey = $this->farParent->getTable().'.'.$this->farParentKey;

        $query->join($this->parent->getTable(), $parentForeignKey, '=', $relatedLocalKey);
        $query->join($this->farParent->getTable(), $farParentForeignKey, '=', $parentLocalKey);
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $table = $this->parent->getTable();

        $this->query->whereIn($table.'.'.$this->parentKey, $this->getKeys($models, $this->related->getKeyName()));
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
            $model->setRelation($relation, null);
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array                                    $models   Parent models
     * @param  \Illuminate\Database\Eloquent\Collection $results  Related models
     * @param  string                                   $relation
     *
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            $key = $model->getKey();

            if (isset($dictionary[$key])) {
                $value = $dictionary[$key];

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        foreach ($results as $result) {
            $dictionary[$result->{$this->parentKey}] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->first();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        $columns = $this->query->getQuery()->columns ? [] : $columns;

        $select = $this->getSelectColumns($columns);

        $builder = $this->query->applyScopes();

        $models = $builder->addSelect($select)->getModels();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);

            return reset($models);
        }

        return null;
    }

    /**
     * Set the select clause for the relation query.
     *
     * @param  array  $columns
     * @return array
     */
    protected function getSelectColumns(array $columns = ['*'])
    {
        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        return array_merge($columns, [
            $this->parent->getTable().'.'.$this->parentKey,
            $this->farParent->getTable().'.'.$this->farParentKey
        ]);
    }
}