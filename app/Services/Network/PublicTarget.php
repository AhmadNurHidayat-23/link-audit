<?php

namespace App\Services\Network;

class PublicTarget
{
    public function __construct(private Resolver $resolver) {}

    public function resolve(string $url): Target
    {
        if (strlen($url) > 1800 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new UnsafeTarget('URL berisi karakter yang tidak diizinkan.');
        }$p = parse_url($url);
        if (! $p || ! isset($p['scheme'],$p['host']) || isset($p['user']) || isset($p['pass']) || ! in_array(strtolower($p['scheme']), ['http', 'https'], true)) {
            throw new UnsafeTarget('Gunakan URL HTTP/HTTPS publik tanpa kredensial.');
        }$scheme = strtolower($p['scheme']);
        $port = $scheme === 'https' ? 443 : 80;
        if (isset($p['port']) && $p['port'] !== $port) {
            throw new UnsafeTarget('Hanya port HTTP/HTTPS standar yang diizinkan.');
        }$host = strtolower(rtrim(trim($p['host'], '[]'), '.'));
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            if (! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $host)) {
                throw new UnsafeTarget('Nama host tidak valid atau bukan domain publik.');
            }$ips = $this->resolver->lookup($host);
        }
        if (! $ips || count($ips) > 32) {
            throw new UnsafeTarget('Domain tidak memiliki alamat publik yang dapat digunakan.');
        }foreach ($ips as $ip) {
            if (! self::publicIp($ip)) {
                throw new UnsafeTarget('Tujuan lokal, privat, reserved, atau campuran DNS tidak diizinkan.');
            }
        }$ip = $ips[0];
        $display = str_contains($host, ':') ? '['.$host.']' : $host;
        $path = $p['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }$normalized = $scheme.'://'.$display.$path.(isset($p['query']) ? '?'.$p['query'] : '');

        return new Target($normalized, $host, $ip, $port);
    }

    public static function publicIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        $blocked = ['0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4'];
        if (str_contains($ip, ':')) {
            if (! self::cidr($ip, '2000::/3')) {
                return false;
            }$blocked = ['2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20'];
        }foreach ($blocked as $range) {
            if (self::cidr($ip, $range)) {
                return false;
            }
        }

        return true;
    }

    private static function cidr(string $ip, string $range): bool
    {
        [$base,$bits] = explode('/', $range);
        $a = @inet_pton($ip);
        $b = @inet_pton($base);
        if (! $a || ! $b || strlen($a) !== strlen($b)) {
            return false;
        }$bytes = intdiv((int) $bits, 8);
        $rest = (int) $bits % 8;
        if (substr($a, 0, $bytes) !== substr($b, 0, $bytes)) {
            return false;
        }

        return ! $rest || ((ord($a[$bytes]) & (255 << (8 - $rest))) === (ord($b[$bytes]) & (255 << (8 - $rest))));
    }
}
