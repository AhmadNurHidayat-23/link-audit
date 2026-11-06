<?php

namespace Tests\Unit;

use App\Services\Network\PublicTarget;
use App\Services\Network\Resolver;
use App\Services\Network\UnsafeTarget;
use PHPUnit\Framework\TestCase;

class PublicTargetTest extends TestCase
{
    private function target(array $ips = ['93.184.216.34']): PublicTarget
    {
        return new PublicTarget(new class($ips)extends Resolver
        {
            public function __construct(private array $ips) {}

            public function lookup(string $host): array
            {
                return $this->ips;
            }
        });
    }

    public function test_private_loopback_metadata_documentation_and_tunnels_are_blocked(): void
    {
        foreach (['127.0.0.1', '10.10.1.1', '172.31.0.1', '192.168.4.1', '169.254.169.254', '100.64.0.1', '0.0.0.0', '198.19.0.1', '192.0.2.2', '203.0.113.1', '224.0.0.1', '::1', '::ffff:127.0.0.1', 'fc00::1', 'fe80::1', '2001:db8::1', '2002:7f00:1::1', '64:ff9b::7f00:1'] as $ip) {
            $this->assertFalse(PublicTarget::publicIp($ip), $ip);
        }foreach (['1.1.1.1', '8.8.8.8', '93.184.216.34', '2606:4700:4700::1111', '2001:4860:4860::8888'] as $ip) {
            $this->assertTrue(PublicTarget::publicIp($ip), $ip);
        }
    }

    public function test_malformed_hosts_protocols_credentials_and_ports_are_rejected(): void
    {
        foreach (['http://localhost/', 'http://2130706433/', 'http://127.1/', 'http://0x7f000001/', 'file:///etc/passwd', 'ftp://example.com/file', 'https://user:pass@example.com', 'https://example.com:8443/', 'http://example.com:443/', 'http://example.com\\@127.0.0.1/', 'http://example.com/with space'] as $url) {
            try {
                $this->target()->resolve($url);
                $this->fail('Harus ditolak: '.$url);
            } catch (UnsafeTarget) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_every_dns_answer_must_be_public(): void
    {
        $this->expectException(UnsafeTarget::class);
        $this->target(['93.184.216.34', '127.0.0.1'])->resolve('https://example.test');
    }

    public function test_normalization_and_pinned_address_preserve_host(): void
    {
        $target = $this->target()->resolve('HTTPS://EXAMPLE.TEST:443/path?a=1#section');
        $this->assertSame('https://example.test/path?a=1', $target->url);
        $this->assertSame('example.test', $target->host);
        $this->assertSame('93.184.216.34', $target->ip);
        $this->assertSame(443, $target->port);
    }
}
