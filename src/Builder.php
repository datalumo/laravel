<?php

namespace Datalumo\Laravel;

use Datalumo\PhpSdk\DataObjects\ChatResponse;
use Datalumo\PhpSdk\DataObjects\StreamResponse;
use Datalumo\PhpSdk\DataObjects\SummaryResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class Builder
{
    public ?float $threshold = null;

    public ?array $meta = null;

    public function __construct(
        public readonly Model $model,
        public readonly string $query,
        private readonly Engine $engine,
    ) {}

    /**
     * Set the similarity threshold (0-1).
     */
    public function threshold(float $threshold): static
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Filter results by metadata key-value pairs.
     */
    public function meta(array $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get the search results as Eloquent models.
     */
    public function get(): EloquentCollection
    {
        return $this->engine->mapToModels(
            $this->engine->search($this),
            $this,
        );
    }

    /**
     * Paginate the search results.
     */
    public function paginate(int $perPage = 15, string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        $page = $page ?? request()->input($pageName, 1);

        $results = $this->engine->paginate($this, $perPage, $page);
        $models = $this->engine->mapToModels($results, $this);

        return new Paginator(
            $models,
            $results->total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );
    }

    /**
     * Get the raw search results from the API (without mapping to models).
     */
    public function raw(): array
    {
        $results = $this->engine->search($this);

        return $results->data;
    }

    /**
     * Get an AI summary of the search results.
     */
    public function summarise(?string $format = null, ?string $locale = null): SummaryResponse
    {
        return $this->engine->summarise($this, $format, $locale);
    }

    /**
     * Chat with the integration using the search query as the message.
     */
    public function chat(?string $conversationId = null): ChatResponse
    {
        return $this->engine->chat($this, $conversationId);
    }

    /**
     * Stream an AI summary of the search results.
     */
    public function streamSummarise(?string $format = null, ?string $locale = null): StreamResponse
    {
        return $this->engine->streamSummarise($this, $format, $locale);
    }

    /**
     * Stream a chat response from the integration.
     */
    public function streamChat(?string $conversationId = null): StreamResponse
    {
        return $this->engine->streamChat($this, $conversationId);
    }
}
