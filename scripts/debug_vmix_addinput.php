<?php

declare(strict_types=1);

use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;
use App\Domains\Vmix\Repositories\Contracts\VmixConnectionRepositoryInterface;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = $app->make(VmixConnectionRepositoryInterface::class)->allActive()->first();
$provider = $app->make(VmixProviderInterface::class);
$path = 'C:\\wamp64\\www\\inovixora\\balafon-tv-automation\\storage\\app\\media-demo\\journal-20h.wav';

$response = $provider->sendCommand($connection, 'AddInput', [
    'Value' => 'AudioFile|'.$path,
]);

$status = $provider->getStatus($connection)->toArray();

echo json_encode([
    'response' => $response,
    'xml' => $status['raw']['xml'] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
