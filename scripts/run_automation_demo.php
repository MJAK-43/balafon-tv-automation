<?php

declare(strict_types=1);

use App\Domains\Automation\Services\BroadcastAutomationService;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Channel\Models\Channel;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Models\Playlist;
use App\Domains\Scheduling\Models\Schedule;
use App\Domains\Vmix\Models\VmixCommandLog;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
$basePath = 'C:\\wamp64\\www\\inovixora\\balafon-tv-automation\\storage\\app\\media-demo';

$channel = Channel::query()->firstOrCreate(
    ['code' => 'BTVAUTO'],
    [
        'uuid' => (string) Str::uuid(),
        'name' => 'Balafon TV Automation',
        'timezone' => 'Africa/Douala',
        'description' => 'Automation demo channel',
        'status' => 'active',
    ],
);

$assets = collect([
    ['title' => 'Journal 20H', 'path' => $basePath.'\\journal-20h.wav', 'type' => 'PROGRAM'],
    ['title' => 'Publicite Orange', 'path' => $basePath.'\\publicite-orange.wav', 'type' => 'ADVERTISEMENT'],
    ['title' => 'Film Camerounais', 'path' => $basePath.'\\film-camerounais.wav', 'type' => 'MOVIE'],
])->map(function (array $item) use ($user): MediaAsset {
    return MediaAsset::query()->updateOrCreate(
        ['file_path' => $item['path']],
        [
            'uuid' => (string) Str::uuid(),
            'title' => $item['title'],
            'description' => 'Automation demo asset',
            'media_type' => $item['type'],
            'duration_seconds' => 2,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ],
    );
});

$playlist = Playlist::query()->updateOrCreate(
    ['title' => 'Playlist Soiree Automation Demo'],
    [
        'uuid' => (string) Str::uuid(),
        'description' => 'Real vMix automation demo playlist',
        'status' => 'READY',
    ],
);

$playlist->items()->delete();
foreach ($assets->values() as $index => $asset) {
    $playlist->items()->create([
        'uuid' => (string) Str::uuid(),
        'position' => $index + 1,
        'media_asset_id' => $asset->id,
    ]);
}

Schedule::query()
    ->where('channel_id', $channel->id)
    ->where('playlist_id', $playlist->id)
    ->where('status', 'SCHEDULED')
    ->update(['status' => 'COMPLETED']);

$schedule = Schedule::query()->create([
    'uuid' => (string) Str::uuid(),
    'channel_id' => $channel->id,
    'playlist_id' => $playlist->id,
    'starts_at' => Carbon::now()->subSecond(),
    'ends_at' => null,
    'status' => 'SCHEDULED',
    'created_by' => $user->id,
    'updated_by' => $user->id,
]);

/** @var BroadcastAutomationService $automation */
$automation = $app->make(BroadcastAutomationService::class);

$ticks = [];
$ticks[] = ['phase' => 'startDueSchedules', 'started' => $automation->startDueSchedules()];

for ($i = 0; $i < 10; $i++) {
    usleep(1000000);
    $automation->monitorActiveRuns();
    $ticks[] = [
        'phase' => 'monitor',
        'tick' => $i + 1,
        'run_state' => optional($automation->controlCenter()['current_run'])->state,
    ];
}

$latestRun = $automation->controlCenter()['recent_runs']->first();
$runId = $latestRun?->id;

$result = [
    'schedule_uuid' => $schedule->uuid,
    'broadcast_run_state' => $latestRun?->state,
    'broadcast_run_started_at' => optional($latestRun?->started_at)?->toIso8601String(),
    'broadcast_run_completed_at' => optional($latestRun?->completed_at)?->toIso8601String(),
    'items' => $latestRun?->items?->map(fn ($item) => [
        'sequence' => $item->sequence,
        'media_title' => $item->mediaAsset->title,
        'state' => $item->state,
        'started_at' => optional($item->started_at)?->toIso8601String(),
        'completed_at' => optional($item->completed_at)?->toIso8601String(),
    ])->values()->all(),
    'vmix_logs' => VmixCommandLog::query()->where('broadcast_run_id', $runId)->latest('id')->limit(20)->get([
        'command_name',
        'status',
        'duration_ms',
        'error_message',
        'executed_at',
    ])->toArray(),
    'audit_logs' => AuditLog::query()->where('entity_type', 'broadcast_run')->where('entity_id', (string) $runId)->latest('id')->limit(20)->get([
        'action',
        'entity_type',
        'entity_id',
        'created_at',
    ])->toArray(),
    'ticks' => $ticks,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
