<?php

namespace App\Http\Controllers\Api\V1\Taxonomy;

use App\Domains\Taxonomy\Services\MediaCategoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaCategoryController extends Controller
{
    public function __construct(
        private readonly MediaCategoryService $categories,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->categories->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:media_categories,slug'],
        ]);

        return response()->json($this->categories->create($payload), 201);
    }

    public function show(string $uuid): JsonResponse
    {
        $category = $this->categories->findByUuid($uuid);
        abort_if($category === null, 404, 'Media category not found.');

        return response()->json($category);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $category = $this->categories->findByUuid($uuid);
        abort_if($category === null, 404, 'Media category not found.');

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:media_categories,slug,'.$category->id],
        ]);

        return response()->json($this->categories->update($category, $payload));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $category = $this->categories->findByUuid($uuid);
        abort_if($category === null, 404, 'Media category not found.');

        $this->categories->delete($category);

        return response()->json(status: 204);
    }
}
