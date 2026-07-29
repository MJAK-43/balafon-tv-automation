<?php

namespace App\Http\Controllers\Api\V1\Playlist;

use App\Domains\Branding\Enums\BrandingAssetType;
use App\Domains\Playlist\Enums\PlaylistStatus;
use App\Domains\Playlist\Services\PlaylistService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlists,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($this->playlists->paginate($filters, $filters['per_page'] ?? 15));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:'.implode(',', PlaylistStatus::values())],
            'items' => ['array'],
            'items.*.position' => ['required', 'integer', 'min:1'],
            'items.*.media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'items.*.logo_id' => [
                'nullable',
                'integer',
                Rule::exists('branding_assets', 'id')
                    ->where('asset_type', BrandingAssetType::LOGO->value),
            ],
            'items.*.announcement_id' => [
                'nullable',
                'integer',
                Rule::exists('branding_assets', 'id')
                    ->whereIn('asset_type', BrandingAssetType::announcementValues()),
            ],
        ]);

        return response()->json($this->playlists->create($payload), 201);
    }

    public function show(string $uuid): JsonResponse
    {
        $playlist = $this->playlists->findByUuid($uuid);
        abort_if($playlist === null, 404, 'Playlist not found.');

        return response()->json($playlist->load(['items.mediaAsset', 'items.logo', 'items.announcement']));
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $playlist = $this->playlists->findByUuid($uuid);
        abort_if($playlist === null, 404, 'Playlist not found.');

        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:'.implode(',', PlaylistStatus::values())],
            'items' => ['array'],
            'items.*.position' => ['required', 'integer', 'min:1'],
            'items.*.media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'items.*.logo_id' => [
                'nullable',
                'integer',
                Rule::exists('branding_assets', 'id')
                    ->where('asset_type', BrandingAssetType::LOGO->value),
            ],
            'items.*.announcement_id' => [
                'nullable',
                'integer',
                Rule::exists('branding_assets', 'id')
                    ->whereIn('asset_type', BrandingAssetType::announcementValues()),
            ],
        ]);

        return response()->json($this->playlists->update($playlist, $payload));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $playlist = $this->playlists->findByUuid($uuid);
        abort_if($playlist === null, 404, 'Playlist not found.');

        $this->playlists->delete($playlist);

        return response()->json(status: 204);
    }
}
