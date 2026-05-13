<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    public function __construct(private readonly TranslationRepositoryInterface $repository) {}

    public function list(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function get(int $id): ?Translation
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Translation
    {
        $tagIds = $this->resolveTagIds($data['tags'] ?? []);
        unset($data['tags']);

        return $this->repository->create($data, $tagIds);
    }

    public function update(Translation $translation, array $data): Translation
    {
        $tagIds = null;
        if (array_key_exists('tags', $data)) {
            $tagIds = $this->resolveTagIds($data['tags'] ?? []);
            unset($data['tags']);
        }

        return $this->repository->update($translation, $data, $tagIds);
    }

    public function delete(Translation $translation): bool
    {
        return $this->repository->delete($translation);
    }

    public function export(string $locale): array
    {
        return $this->repository->exportByLocale($locale);
    }

    public function exportJson(string $locale): string
    {
        return Cache::rememberForever(
            Translation::cacheKey($locale),
            fn () => json_encode($this->repository->exportByLocale($locale), JSON_UNESCAPED_UNICODE),
        );
    }

    private function resolveTagIds(array $names): array
    {
        $ids = [];

        foreach (array_unique(array_filter(array_map('trim', $names))) as $name) {
            $ids[] = Tag::firstOrCreate(['name' => $name])->id;
        }

        return $ids;
    }
}
