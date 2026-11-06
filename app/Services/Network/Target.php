<?php

namespace App\Services\Network;

readonly class Target
{
    public function __construct(public string $url, public string $host, public string $ip, public int $port) {}
}
