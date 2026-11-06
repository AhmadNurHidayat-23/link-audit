<?php

namespace App\Services\Network;

class NetworkTransport
{
    public function get(Target $target, int $timeout): array
    {
        $body = '';
        $headers = [];
        $headerBytes = 0;
        $limited = false;
        $handle = curl_init($target->url);
        $ip = str_contains($target->ip, ':') ? '['.$target->ip.']' : $target->ip;
        curl_setopt_array($handle, [CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*', CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS_STR => 'http,https', CURLOPT_CONNECTTIMEOUT_MS => min(2000, $timeout), CURLOPT_TIMEOUT_MS => $timeout, CURLOPT_NOSIGNAL => true, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_RESOLVE => [$target->host.':'.$target->port.':'.$ip], CURLOPT_USERAGENT => 'LinkAudit/1.0', CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.1'], CURLOPT_ENCODING => 'identity', CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use (&$body, &$limited) {
            if (strlen($body) + strlen($chunk) > 524288) {
                $limited = true;

                return 0;
            }$body .= $chunk;

            return strlen($chunk);
        }, CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$headers, &$headerBytes, &$limited) {
            $headerBytes += strlen($line);
            if ($headerBytes > 65536) {
                $limited = true;

                return 0;
            }if (str_starts_with($line, 'HTTP/')) {
                $headers = [];
            } elseif (str_contains($line, ':')) {
                [$key,$value] = explode(':', $line, 2);
                $key = strtolower(trim($key));
                if (! isset($headers[$key])) {
                    $headers[$key] = trim($value);
                }
            }

            return strlen($line);
        }]);
        $ok = curl_exec($handle);
        $error = curl_errno($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $primary = (string) curl_getinfo($handle, CURLINFO_PRIMARY_IP);
        $duration = (int) round(curl_getinfo($handle, CURLINFO_TOTAL_TIME) * 1000);
        curl_close($handle);
        if ($limited) {
            throw new ResourceLimit('Respons melampaui batas 512 KB atau header 64 KB.');
        }if ($ok === false) {
            throw new \RuntimeException($error === CURLE_OPERATION_TIMEDOUT ? 'Koneksi melewati batas waktu.' : 'Koneksi HTTP/TLS gagal (kode '.$error.').');
        }if (@inet_pton($primary) !== @inet_pton($target->ip)) {
            throw new UnsafeTarget('Alamat koneksi tidak sesuai alamat publik yang diverifikasi.');
        }

        return ['status' => $status, 'headers' => $headers, 'body' => $body, 'duration_ms' => $duration];
    }
}
