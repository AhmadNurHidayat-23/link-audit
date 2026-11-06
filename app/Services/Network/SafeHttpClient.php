<?php

namespace App\Services\Network;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

class SafeHttpClient
{
    public function __construct(private PublicTarget $targets, private NetworkTransport $transport) {}

    public function check(string $url, ScanBudget $budget): array
    {
        $chain = [];
        $visited = [];
        $duration = 0;
        $current = $url;
        try {
            for ($hop = 0; $hop <= 3; $hop++) {
                $target = $this->targets->resolve($current);
                if (isset($visited[$target->url])) {
                    return $this->result($url, $target->url, null, 'redirect_error', 'Lingkaran redirect terdeteksi.', $chain, $duration);
                }$visited[$target->url] = true;
                $response = $this->transport->get($target, $budget->take());
                $duration += $response['duration_ms'];
                $chain[] = ['url' => $target->url, 'status' => $response['status']];
                $status = $response['status'];
                if (in_array($status, [301, 302, 303, 307, 308], true)) {
                    if (empty($response['headers']['location'])) {
                        return $this->result($url, $target->url, $status, 'redirect_error', 'Redirect tanpa Location.', $chain, $duration);
                    }$current = $this->absolute($target->url, $response['headers']['location']);
                    if ($hop === 3) {
                        return $this->result($url, $target->url, $status, 'redirect_error', 'Melebihi tiga redirect.', $chain, $duration);
                    }

                    continue;
                }
                $kind = $status >= 200 && $status < 300 ? 'ok' : ($status >= 400 && $status < 500 ? 'broken' : ($status >= 500 ? 'server_error' : 'other'));
                $result = $this->result($url, $target->url, $status, $kind, '', $chain, $duration);
                $result['body'] = $response['body'];
                $result['content_type'] = $response['headers']['content-type'] ?? '';

                return $result;
            }
        } catch (UnsafeTarget $e) {
            return $this->result($url, $current, null, 'blocked', $e->getMessage(), $chain, $duration);
        } catch (ResourceLimit $e) {
            return $this->result($url, $current, null, 'limited', $e->getMessage(), $chain, $duration);
        } catch (\Throwable $e) {
            return $this->result($url, $current, null, 'connection_error', 'Permintaan gagal: '.$e->getMessage(), $chain, $duration);
        }

        return $this->result($url, $current, null, 'redirect_error', 'Redirect tidak dapat diselesaikan.', $chain, $duration);
    }

    public function absolute(string $base, string $relative): string
    {
        $uri = UriResolver::resolve(new Uri($base), new Uri($relative));

        return (string) $uri->withFragment('')->withPath($uri->getPath() === '' ? '/' : $uri->getPath());
    }

    private function result(string $url, string $final, ?int $status, string $kind, string $message, array $chain, int $duration): array
    {
        return ['url' => $url, 'final_url' => $final, 'status_code' => $status, 'kind' => $kind, 'message' => $message, 'chain' => $chain, 'duration_ms' => $duration, 'body' => '', 'content_type' => ''];
    }
}
