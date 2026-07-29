<?php

namespace Tests\Feature\Automation;

use App\Domains\Automation\Models\BroadcastRunItem;
use App\Domains\Branding\Models\BrandingAsset;
use App\Domains\Channel\Models\Channel;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Models\Playlist;
use App\Domains\Vmix\DTOs\VmixStatusDTO;
use App\Domains\Vmix\Models\VmixCommandLog;
use App\Domains\Vmix\Models\VmixConnection;
use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomationTickTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_tick_executes_a_playlist_to_completion_with_mock_vmix(): void
    {
        config()->set('balafon.vmix.driver', 'mock');
        config()->set('balafon.automation.prepare_threshold_ms', 2000);
        config()->set('balafon.automation.transition_lead_ms', 1000);

        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/auto-a.wav', 'a');
        File::put($mediaRoot.'/auto-b.wav', 'b');

        $channel = Channel::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Balafon TV',
            'code' => 'BTV',
            'timezone' => 'Africa/Douala',
            'description' => 'Automation channel',
            'status' => 'active',
        ]);

        $mediaA = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Journal 20H',
            'description' => null,
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/auto-a.wav',
            'duration_seconds' => 3,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $mediaB = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Publicite Orange',
            'description' => null,
            'media_type' => 'ADVERTISEMENT',
            'file_path' => $mediaRoot.'/auto-b.wav',
            'duration_seconds' => 3,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $logo = BrandingAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Logo Balafon',
            'asset_type' => 'LOGO',
            'file_path' => $mediaRoot.'/auto-a.wav',
            'logo_position' => 'TOP_RIGHT',
            'logo_scale' => 20,
            'loop_enabled' => true,
            'status' => 'ACTIVE',
        ]);
        $announcement = BrandingAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Alerte info',
            'asset_type' => 'ANNOUNCEMENT_TEXT',
            'text_content' => 'Information en direct.',
            'text_color' => '#FFD700',
            'text_background_color' => '#123456',
            'text_font' => 'IMPACT',
            'ticker_speed' => 190,
            'loop_enabled' => true,
            'status' => 'ACTIVE',
        ]);

        $playlist = Playlist::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Playlist Soiree',
            'description' => 'Automation test',
            'status' => 'READY',
        ]);

        $playlist->items()->createMany([
            [
                'uuid' => (string) Str::uuid(),
                'position' => 1,
                'media_asset_id' => $mediaA->id,
                'logo_id' => $logo->id,
                'announcement_id' => $announcement->id,
            ],
            ['uuid' => (string) Str::uuid(), 'position' => 2, 'media_asset_id' => $mediaB->id],
        ]);

        $this->actingAs($user)->postJson('/api/v1/schedules', [
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => now()->subSeconds(5)->toDateTimeString(),
            'ends_at' => null,
            'status' => 'SCHEDULED',
        ])->assertCreated();

        $this->actingAs($user)->postJson('/api/v1/automation/tick')
            ->assertOk()
            ->assertJsonPath('started_runs', 1);

        $runItem = BroadcastRunItem::query()->where('sequence', 1)->firstOrFail();
        $this->get("/overlays/ticker/{$runItem->uuid}")
            ->assertOk()
            ->assertSee('Information en direct.')
            ->assertSee('#FFD700', false)
            ->assertSee('rgba(18, 52, 86, 0.92)', false)
            ->assertSee('Impact, sans-serif', false)
            ->assertSee('data-speed="190"', false)
            ->assertSee('distance / speed', false);

        $this->travel(2)->seconds();
        $this->actingAs($user)->postJson('/api/v1/automation/tick')->assertOk();

        $this->travel(4)->seconds();
        $this->actingAs($user)->postJson('/api/v1/automation/tick')->assertOk();

        $controlCenter = $this->actingAs($user)->getJson('/api/v1/automation/control-center')
            ->assertOk()
            ->json();

        $this->assertNull($controlCenter['current_run']);
        $this->assertSame('COMPLETED', $controlCenter['recent_runs'][0]['state']);
        $this->assertGreaterThan(0, VmixCommandLog::query()->count());
        $this->assertDatabaseCount('broadcast_runs', 1);
        $this->assertDatabaseHas('broadcast_run_items', [
            'sequence' => 1,
            'state' => 'COMPLETED',
        ]);
        $this->assertDatabaseHas('broadcast_run_items', [
            'sequence' => 2,
            'state' => 'COMPLETED',
        ]);
        $this->assertDatabaseHas('vmix_command_logs', ['command_name' => 'OverlayInput1In', 'status' => 'success']);
        $this->assertDatabaseHas('vmix_command_logs', ['command_name' => 'SetZoom', 'status' => 'success']);
        $this->assertDatabaseHas('vmix_command_logs', ['command_name' => 'SetPanX', 'status' => 'success']);
        $this->assertDatabaseHas('vmix_command_logs', ['command_name' => 'SetPanY', 'status' => 'success']);
        $this->assertDatabaseHas('vmix_command_logs', ['command_name' => 'OverlayInput2In', 'status' => 'success']);
        $this->assertDatabaseHas('vmix_command_logs', ['command_name' => 'OverlayInput1Out', 'status' => 'success']);
        $this->assertDatabaseHas('vmix_command_logs', ['command_name' => 'OverlayInput2Out', 'status' => 'success']);

        $mediaBAdd = VmixCommandLog::query()
            ->where('command_name', 'AddInput')
            ->get()
            ->first(fn (VmixCommandLog $log): bool => str_contains(
                (string) data_get($log->request_payload, 'parameters.Value'),
                'auto-b.wav',
            ));
        $mediaBPlay = VmixCommandLog::query()
            ->where('broadcast_run_item_id', BroadcastRunItem::query()->where('sequence', 2)->value('id'))
            ->where('command_name', 'Play')
            ->first();
        $mediaBActive = VmixCommandLog::query()
            ->where('broadcast_run_item_id', BroadcastRunItem::query()->where('sequence', 2)->value('id'))
            ->where('command_name', 'ActiveInput')
            ->first();
        $mediaARemove = VmixCommandLog::query()
            ->where('broadcast_run_item_id', $runItem->id)
            ->where('command_name', 'RemoveInput')
            ->first();

        $this->assertNotNull($mediaBAdd);
        $this->assertNotNull($mediaBPlay);
        $this->assertNotNull($mediaBActive);
        $this->assertNotNull($mediaARemove);
        $this->assertTrue($mediaBAdd->id < $mediaBPlay->id, 'The next media must be added before playback starts.');
        $this->assertTrue($mediaBPlay->id < $mediaBActive->id, 'The next media must start before switching Program.');
        $this->assertTrue($mediaBActive->id < $mediaARemove->id, 'The old media must remain available until Program has switched.');

        $panX = VmixCommandLog::query()
            ->where('broadcast_run_item_id', $runItem->id)
            ->where('command_name', 'SetPanX')
            ->firstOrFail();
        $panY = VmixCommandLog::query()
            ->where('broadcast_run_item_id', $runItem->id)
            ->where('command_name', 'SetPanY')
            ->firstOrFail();

        $this->assertSame(0.74, data_get($panX->request_payload, 'parameters.Value'));
        $this->assertSame(0.74, data_get($panY->request_payload, 'parameters.Value'));

        $browserInput = VmixCommandLog::query()
            ->where('command_name', 'AddInput')
            ->get()
            ->first(fn (VmixCommandLog $log): bool => str_starts_with(
                (string) data_get($log->request_payload, 'parameters.Value'),
                'Browser|',
            ));

        $this->assertNotNull($browserInput);
        $this->assertStringContainsString(
            "/overlays/ticker/{$runItem->uuid}",
            data_get($browserInput->request_payload, 'parameters.Value'),
        );
    }

    public function test_automation_tick_marks_schedule_as_failed_when_vmix_start_fails(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/auto-fail.wav', 'x');

        $channel = Channel::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Balafon TV',
            'code' => 'BTV',
            'timezone' => 'Europe/Paris',
            'description' => 'Automation channel',
            'status' => 'active',
        ]);

        $media = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Broken Clip',
            'description' => null,
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/auto-fail.wav',
            'duration_seconds' => 1,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $playlist = Playlist::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Playlist Failure',
            'description' => 'Automation failure test',
            'status' => 'READY',
        ]);

        $playlist->items()->create([
            'uuid' => (string) Str::uuid(),
            'position' => 1,
            'media_asset_id' => $media->id,
        ]);

        $failingProvider = new class implements VmixProviderInterface
        {
            public function getStatus(VmixConnection $connection): VmixStatusDTO
            {
                return new VmixStatusDTO(true, '29.x-test', 'trial', null, null, false, false, false, false, [], ['xml' => '<vmix />']);
            }

            public function getInputs(VmixConnection $connection): array
            {
                return [];
            }

            public function healthCheck(VmixConnection $connection): bool
            {
                return true;
            }

            public function sendCommand(VmixConnection $connection, string $command, array $parameters = []): array
            {
                return [
                    'status' => 'failed',
                    'response_code' => 500,
                    'response_body' => 'Simulated vMix failure',
                    'request_url' => 'http://vmix.test/api',
                    'duration_ms' => 10,
                    'error_message' => 'vMix command returned an unsuccessful response.',
                ];
            }
        };

        $this->app->instance(VmixProviderInterface::class, $failingProvider);

        $schedule = $this->actingAs($user)->postJson('/api/v1/schedules', [
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => now()->subSeconds(5)->toDateTimeString(),
            'ends_at' => null,
            'status' => 'SCHEDULED',
        ])->assertCreated()->json();

        $this->actingAs($user)->postJson('/api/v1/automation/tick')
            ->assertOk()
            ->assertJsonPath('started_runs', 0);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule['id'],
            'status' => 'FAILED',
        ]);
        $this->assertDatabaseHas('broadcast_runs', [
            'schedule_id' => $schedule['id'],
            'state' => 'FAILED',
        ]);
        $this->assertDatabaseHas('vmix_command_logs', [
            'schedule_id' => $schedule['id'],
            'command_name' => 'AddInput',
            'status' => 'failed',
        ]);
    }
}
