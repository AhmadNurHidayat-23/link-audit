<?php

namespace App\Services;

use App\Models\Audit;
use App\Services\Network\SafeHttpClient;
use App\Services\Network\ScanBudget;

class AuditService
{
    public function __construct(private SafeHttpClient $client, private LinkExtractor $links) {}

    public function run(Audit $audit): void
    {
        $budget = new ScanBudget;
        $seen = [];
        $root = $this->client->check($audit->url, $budget);
        $this->store($audit, $root);
        $seen[$root['final_url']] = true;
        $found = [];
        if ($root['kind'] === 'ok' && (str_contains(strtolower($root['content_type']), 'text/html') || str_contains(strtolower($root['content_type']), 'application/xhtml+xml'))) {
            $found = $this->links->extract($root['body'], $root['final_url']);
        }$found = array_values(array_filter($found, fn ($url) => $url !== $root['final_url'] && $url !== $audit->url));
        $discovered = count($found) + 1;
        $checked = 1;
        foreach ($found as $url) {
            if ($checked >= $audit->maximum || $budget->exhausted()) {
                break;
            }if (isset($seen[$url])) {
                continue;
            }$seen[$url] = true;
            $this->store($audit, $this->client->check($url, $budget));
            $checked++;
        }$partial = $checked < $discovered || $audit->results()->whereIn('kind', ['limited'])->exists();
        $note = 'Tautan diambil hanya dari halaman awal (kedalaman 1), maksimal 100 tautan unik ditemukan. Tidak menjalankan JavaScript.';
        if ($partial) {
            $note .= ' Sebagian tautan tidak diperiksa karena batas URL, permintaan, atau waktu.';
        }$audit->update(['status' => $partial ? 'partial' : 'completed', 'discovered' => $discovered, 'requests' => $budget->requests, 'note' => $note, 'finished_at' => now()]);
    }

    private function store(Audit $a, array $r): void
    {
        unset($r['body'],$r['content_type']);
        $a->results()->create($r);
    }
}
