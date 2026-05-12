<?php

namespace App\Repositories;

use App\Models\Translation;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TranslationRepository implements TranslationRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Translation::query()->with('tags:id,name');

        if (! empty($filters['locale'])) {
            $query->where('locale', $filters['locale']);
        }

        if (! empty($filters['key'])) {
            $query->where('key', 'like', $filters['key'].'%');
        }

        if (! empty($filters['content'])) {
            $query->where('content', 'like', '%'.$filters['content'].'%');
        }

        if (! empty($filters['tags'])) {
            $tags = (array) $filters['tags'];
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            }, '=', count($tags));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function find(int $id): ?Translation
    {
        return Translation::with('tags:id,name')->find($id);
    }

    public function create(array $data, array $tagIds = []): Translation
    {
        return DB::transaction(function () use ($data, $tagIds) {
            $translation = Translation::create($data);

            if (! empty($tagIds)) {
                $translation->tags()->sync($tagIds);
            }

            return $translation->load('tags:id,name');
        });
    }

    public function update(Translation $translation, array $data, ?array $tagIds = null): Translation
    {
        return DB::transaction(function () use ($translation, $data, $tagIds) {
            $translation->fill($data)->save();

            if ($tagIds !== null) {
                $translation->tags()->sync($tagIds);
            }

            return $translation->load('tags:id,name');
        });
    }

    public function delete(Translation $translation): bool
    {
        return (bool) $translation->delete();
    }

    public function exportByLocale(string $locale): array
    {
        return DB::table('translations')
            ->where('locale', $locale)
            ->pluck('content', 'key')
            ->all();
    }
}
