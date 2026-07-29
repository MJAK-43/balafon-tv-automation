<?php

namespace App\Http\Controllers\Api\V1\Taxonomy;

use App\Domains\Taxonomy\Services\MediaTagService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaTagController extends Controller
{
    public function __construct(
        private readonly MediaTagService $tags,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->tags->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:media_tags,slug'],
        ]);

        return response()->json($this->tags->create($payload), 201);
    }

    public function show(string $uuid): JsonResponse
    {
        $tag = $this->tags->findByUuid($uuid);
        abort_if($tag === null, 404, 'Media tag not found.');

        return response()->json($tag);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $tag = $this->tags->findByUuid($uuid);
        abort_if($tag === null, 404, 'Media tag not found.');

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:media_tags,slug,'.$tag->id],
        ]);

        return response()->json($this->tags->update($tag, $payload));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $tag = $this->tags->findByUuid($uuid);
        abort_if($tag === null, 404, 'Media tag not found.');

        $this->tags->delete($tag);

        return response()->json(status: 204);
    }
}
