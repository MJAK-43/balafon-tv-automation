<?php

namespace App\Http\Controllers\Api\V1\Channel;

use App\Domains\Channel\Services\ChannelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function __construct(
        private readonly ChannelService $channels,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->channels->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:channels,code'],
            'timezone' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        return response()->json($this->channels->create($payload), 201);
    }

    public function show(string $uuid): JsonResponse
    {
        $channel = $this->channels->findByUuid($uuid);
        abort_if($channel === null, 404, 'Channel not found.');

        return response()->json($channel);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $channel = $this->channels->findByUuid($uuid);
        abort_if($channel === null, 404, 'Channel not found.');

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:channels,code,'.$channel->id],
            'timezone' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        return response()->json($this->channels->update($channel, $payload));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $channel = $this->channels->findByUuid($uuid);
        abort_if($channel === null, 404, 'Channel not found.');

        $this->channels->delete($channel);

        return response()->json(status: 204);
    }
}
