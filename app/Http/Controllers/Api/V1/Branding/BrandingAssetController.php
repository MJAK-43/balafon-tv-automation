<?php

namespace App\Http\Controllers\Api\V1\Branding;

use App\Domains\Branding\Enums\BrandingAssetType;
use App\Domains\Branding\Services\BrandingAssetService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingAssetController extends Controller
{
    private const LOGO_POSITIONS = 'TOP_RIGHT,TOP_LEFT,BOTTOM_RIGHT,BOTTOM_LEFT';

    private const TEXT_FONTS = 'SEGOE_UI,TREBUCHET,GEORGIA,IMPACT,COURIER_NEW';

    public function __construct(
        private readonly BrandingAssetService $assets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'asset_type' => ['nullable', 'string', 'in:'.implode(',', BrandingAssetType::values())],
            'status' => ['nullable', 'string', 'in:ACTIVE,ARCHIVED'],
        ]);

        return response()->json($this->assets->list($filters));
    }

    public function storeLogo(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:png', 'max:20480'],
            'logo_position' => ['nullable', 'string', 'in:'.self::LOGO_POSITIONS],
            'logo_scale' => ['nullable', 'integer', 'min:10', 'max:50'],
        ]);

        return response()->json(
            $this->assets->createLogo(
                $request->file('file'),
                $payload['name'],
                $payload['logo_position'] ?? 'TOP_RIGHT',
                $payload['logo_scale'] ?? 20,
                $request->user()?->id,
            ),
            201,
        );
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'asset_type' => ['required', 'string', 'in:'.implode(',', BrandingAssetType::announcementValues())],
            'text_content' => ['required_if:asset_type,ANNOUNCEMENT_TEXT', 'nullable', 'string', 'max:5000'],
            'file' => ['required_if:asset_type,ANNOUNCEMENT_VIDEO', 'nullable', 'file', 'mimes:mp4,mov,avi,mkv', 'max:2097152'],
            'loop_enabled' => ['nullable', 'boolean'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_font' => ['nullable', 'in:'.self::TEXT_FONTS],
            'ticker_speed' => ['nullable', 'integer', 'min:40', 'max:300'],
        ]);

        $asset = $payload['asset_type'] === BrandingAssetType::ANNOUNCEMENT_TEXT->value
            ? $this->assets->createTextAnnouncement(
                $payload['name'],
                $payload['text_content'],
                $payload['loop_enabled'] ?? true,
                $payload['text_color'] ?? '#FFFFFF',
                $payload['text_background_color'] ?? '#07101D',
                $payload['text_font'] ?? 'SEGOE_UI',
                $payload['ticker_speed'] ?? 120,
                $request->user()?->id,
            )
            : $this->assets->createVideoAnnouncement(
                $request->file('file'),
                $payload['name'],
                $payload['loop_enabled'] ?? true,
                $request->user()?->id,
            );

        return response()->json($asset, 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $asset = $this->assets->findByUuid($uuid);
        abort_if($asset === null, 404, 'Graphic asset not found.');

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'text_content' => [
                $asset->asset_type === BrandingAssetType::ANNOUNCEMENT_TEXT->value ? 'required' : 'nullable',
                'string',
                'max:5000',
            ],
            'loop_enabled' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:ACTIVE,ARCHIVED'],
            'logo_position' => ['nullable', 'string', 'in:'.self::LOGO_POSITIONS],
            'logo_scale' => ['nullable', 'integer', 'min:10', 'max:50'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_font' => ['nullable', 'in:'.self::TEXT_FONTS],
            'ticker_speed' => ['nullable', 'integer', 'min:40', 'max:300'],
        ]);

        return response()->json($this->assets->update($asset, $payload, $request->user()?->id));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $asset = $this->assets->findByUuid($uuid);
        abort_if($asset === null, 404, 'Graphic asset not found.');

        $this->assets->delete($asset);

        return response()->json(status: 204);
    }

    public function preview(string $uuid): BinaryFileResponse
    {
        $asset = $this->assets->findByUuid($uuid);
        abort_if($asset === null, 404, 'Graphic asset not found.');

        return $this->assets->preview($asset);
    }
}
