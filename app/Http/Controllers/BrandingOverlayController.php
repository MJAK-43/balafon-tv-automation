<?php

namespace App\Http\Controllers;

use App\Domains\Automation\Models\BroadcastRunItem;
use Illuminate\Contracts\View\View;

class BrandingOverlayController extends Controller
{
    private const FONT_FAMILIES = [
        'SEGOE_UI' => '"Segoe UI", sans-serif',
        'TREBUCHET' => '"Trebuchet MS", sans-serif',
        'GEORGIA' => 'Georgia, serif',
        'IMPACT' => 'Impact, sans-serif',
        'COURIER_NEW' => '"Courier New", monospace',
    ];

    public function __invoke(string $uuid): View
    {
        $item = BroadcastRunItem::query()->where('uuid', $uuid)->firstOrFail();
        $announcement = data_get($item->context, 'graphics.announcement');

        abort_unless(
            is_array($announcement)
            && ($announcement['asset_type'] ?? null) === 'ANNOUNCEMENT_TEXT',
            404,
        );

        $color = (string) ($announcement['text_color'] ?? '#FFFFFF');
        if (preg_match('/^#[0-9A-Fa-f]{6}([0-9A-Fa-f]{2})?$/', $color) !== 1) {
            $color = '#FFFFFF';
        }

        $fontKey = (string) ($announcement['text_font'] ?? 'SEGOE_UI');
        $backgroundColor = (string) ($announcement['text_background_color'] ?? '#07101D');
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $backgroundColor) !== 1) {
            $backgroundColor = '#07101D';
        }

        return view('overlays.ticker', [
            'text' => (string) ($announcement['text_content'] ?? ''),
            'textColor' => $color,
            'backgroundColor' => $this->toRgba($backgroundColor, 0.92),
            'fontFamily' => self::FONT_FAMILIES[$fontKey] ?? self::FONT_FAMILIES['SEGOE_UI'],
            'tickerSpeed' => min(300, max(40, (int) ($announcement['ticker_speed'] ?? 120))),
            'loopEnabled' => (bool) ($announcement['loop_enabled'] ?? true),
        ]);
    }

    private function toRgba(string $color, float $alpha): string
    {
        return sprintf(
            'rgba(%d, %d, %d, %.2F)',
            hexdec(substr($color, 1, 2)),
            hexdec(substr($color, 3, 2)),
            hexdec(substr($color, 5, 2)),
            $alpha,
        );
    }
}
