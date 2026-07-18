<?php

namespace Datalumo\Laravel;

use Datalumo\Data\SearchResult;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator as SimplePaginator;

class Builder
{
    /** @var array<string, mixed> */
    public array $filters = [];

    public ?int $limit = null;

    public ?string $sessionId = null;

    public ?string $sort = null;

    public function __construct(
        public Model $model,
        public string $query = '',
    ) {}

    public function where(string $field, mixed $value): static
    {
        $this->filters[$field] = $value;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function session(?string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function sort(?string $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function raw(): SearchResult
    {
        return $this->engine()->search($this);
    }

    public function get(): EloquentCollection
    {
        $result = $this->raw();

        return $this->engine()->mapToModels($result, $this);
    }

    public function paginate(?int $perPage = null, string $pageName = 'page', ?int $page = null): Paginator
    {
        $perPage ??= $this->limit ?? (int) config('datalumo.search.limit', 15);
        $page ??= SimplePaginator::resolveCurrentPage($pageName);

        $this->limit($perPage);

        $items = $this->get();

        return new SimplePaginator(
            $items,
            $perPage,
            $page,
            [
                'path' => SimplePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );
    }

    protected function engine(): Engine
    {
        return $this->model->searchableUsing();
    }
}
