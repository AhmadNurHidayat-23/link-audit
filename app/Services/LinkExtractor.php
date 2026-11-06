<?php

namespace App\Services;

use App\Services\Network\SafeHttpClient;

class LinkExtractor
{
    public function __construct(private SafeHttpClient $client) {}

    public function extract(string $html, string $base): array
    {
        if (trim($html) === '') {
            return [];
        }
        $doc = new \DOMDocument;
        @$doc->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $bases = $doc->getElementsByTagName('base');
        if ($bases->length && $bases->item(0)->hasAttribute('href')) {
            try {
                $base = $this->client->absolute($base, $bases->item(0)->getAttribute('href'));
            } catch (\Throwable) {
            }
        }$found = [];
        foreach ($doc->getElementsByTagName('a') as $node) {
            $href = trim($node->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }try {
                $url = $this->client->absolute($base, $href);
                if (! in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) {
                    continue;
                }$found[$url] = true;
                if (count($found) >= 100) {
                    break;
                }
            } catch (\Throwable) {
            }
        }

        return array_keys($found);
    }
}
