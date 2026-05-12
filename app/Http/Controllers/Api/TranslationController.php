<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\IndexTranslationRequest;
use App\Http\Requests\Translation\StoreTranslationRequest;
use App\Http\Requests\Translation\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use App\Services\TranslationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TranslationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TranslationService $service) {}

    public function index(IndexTranslationRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $paginator = $this->service->list($request->filters(), $perPage);

        return $this->success([
            'items' => TranslationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->service->create($request->validated());

        return $this->success(new TranslationResource($translation), 'Translation created', 201);
    }

    public function show(Translation $translation): JsonResponse
    {
        $translation->load('tags:id,name');

        return $this->success(new TranslationResource($translation));
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): JsonResponse
    {
        $updated = $this->service->update($translation, $request->validated());

        return $this->success(new TranslationResource($updated), 'Translation updated');
    }

    public function destroy(Translation $translation): JsonResponse
    {
        $this->service->delete($translation);

        return $this->success(null, 'Translation deleted');
    }

    public function export(string $locale)
    {
        $t0 = microtime(true);
        $json = $this->service->exportJson($locale);
        $ms = (int) ((microtime(true) - $t0) * 1000);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=0, must-revalidate',
            'X-Locale' => $locale,
            'X-Compute-Ms' => (string) $ms,
        ]);
    }
}
