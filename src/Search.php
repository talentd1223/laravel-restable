<?php

namespace BinarCode\LaravelRestable;

use BinarCode\LaravelRestable\Exceptions\InvalidClass;
use BinarCode\LaravelRestable\Filters\SearchableCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Search
{
    /**
     * Model used for search.
     *
     * @var Model|Restable $model
     */
    private Model $model;

    public function __construct(
        private Request $request,
        private Builder $builder,
        Model $model
    )
    {
        $this->model = $model;
    }

    public static function apply(Request $request, string $modelClass): Builder
    {
        if (!is_subclass_of($modelClass, Restable::class)) {
            throw InvalidClass::shouldBe(Restable::class, $modelClass);
        }

        return static::query($request, $modelClass::query());
    }

    public static function query(Request $request, Builder $builder): Builder
    {
        /** * @var Restable $model */
        $model = $builder->getModel();

        if (!$model instanceof Restable) {
            throw InvalidClass::shouldBe(Restable::class, $model::class);
        }

        $search = new static($request, $builder, $model);

        return $model::restableQuery(
            $search->search($request, $search->match($request, $builder))
        );
    }

    public function paginate(): LengthAwarePaginator
    {
        return $this->builder->paginate($this->getPerPage());
    }

    public function search(Request $request, Builder $builder): Builder
    {
        if (empty($search = $this->request->input('search'))) {
            return $builder;
        }


        return $builder->where(function(Builder $query) use ($search) {
            SearchableCollection::make($this->model::searchables())
                ->mapIntoFilter($this->model)
                ->apply($this->request, $query, $search);
        });
    }

    public function match(Request $request, Builder $query): Builder
    {
        $this->model::collectMatches($request, $this->model)->apply($request, $query);

        return $query;
    }

    public function getPerPage(): int
    {
        return (int)($this->request->input('perPage') ?? $this->model::perPage());
    }
}