<?php

namespace App\Http\Controllers\Api\V1\Scheduling;

use App\Domains\Scheduling\Enums\ScheduleStatus;
use App\Domains\Scheduling\Services\SchedulePlannerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly SchedulePlannerService $schedules,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'channel_id' => ['nullable', 'integer', 'exists:channels,id'],
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'timezone' => ['nullable', 'timezone'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'mode' => ['nullable', 'string'],
        ]);

        if (($filters['mode'] ?? null) === 'all') {
            return response()->json($this->schedules->list($filters));
        }

        return response()->json($this->schedules->paginate($filters, $filters['per_page'] ?? 15));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'playlist_id' => ['required', 'integer', 'exists:playlists,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', 'string', 'in:'.implode(',', ScheduleStatus::values())],
            'timezone' => ['nullable', 'timezone'],
        ]);

        return response()->json(
            $this->schedules->create($payload, $request->user()?->id),
            201,
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $schedule = $this->schedules->findByUuid($uuid);
        abort_if($schedule === null, 404, 'Schedule not found.');

        return response()->json($schedule);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $schedule = $this->schedules->findByUuid($uuid);
        abort_if($schedule === null, 404, 'Schedule not found.');

        $payload = $request->validate([
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'playlist_id' => ['required', 'integer', 'exists:playlists,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', 'string', 'in:'.implode(',', ScheduleStatus::values())],
            'timezone' => ['nullable', 'timezone'],
        ]);

        return response()->json(
            $this->schedules->update($schedule, $payload, $request->user()?->id),
        );
    }

    public function destroy(string $uuid): JsonResponse
    {
        $schedule = $this->schedules->findByUuid($uuid);
        abort_if($schedule === null, 404, 'Schedule not found.');

        $this->schedules->delete($schedule);

        return response()->json(status: 204);
    }

    public function duplicateDay(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'source_date' => ['required', 'date'],
            'target_date' => ['required', 'date'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        return response()->json(
            $this->schedules->duplicateDay(
                $payload['channel_id'],
                $payload['source_date'],
                $payload['target_date'],
                $payload['timezone'] ?? null,
                $request->user()?->id,
            ),
            201,
        );
    }

    public function duplicateWeek(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'channel_id' => ['required', 'integer', 'exists:channels,id'],
            'source_week_start' => ['required', 'date'],
            'target_week_start' => ['required', 'date'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        return response()->json(
            $this->schedules->duplicateWeek(
                $payload['channel_id'],
                $payload['source_week_start'],
                $payload['target_week_start'],
                $payload['timezone'] ?? null,
                $request->user()?->id,
            ),
            201,
        );
    }
}
