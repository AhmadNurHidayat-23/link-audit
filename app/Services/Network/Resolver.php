<?php

namespace App\Services\Network;

class Resolver
{
    public function lookup(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(fn ($r) => $r['ip'] ?? $r['ipv6'] ?? null, $records))));
    }
}
