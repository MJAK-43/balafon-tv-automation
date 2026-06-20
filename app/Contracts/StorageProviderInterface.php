<?php

namespace App\Contracts;

interface StorageProviderInterface
{
    public function put(string $path, string $contents): string;
}
