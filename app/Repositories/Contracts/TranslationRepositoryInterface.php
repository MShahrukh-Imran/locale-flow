<?php

namespace App\Repositories\Contracts;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TranslationRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator;

    public function find(int $id): ?Translation;

    public function create(array $data, array $tagIds = []): Translation;

    public function update(Translation $translation, array $data, ?array $tagIds = null): Translation;

    public function delete(Translation $translation): bool;

    public function exportByLocale(string $locale): array;
}
