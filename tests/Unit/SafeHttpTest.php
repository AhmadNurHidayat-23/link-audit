<?php

namespace Tests\Unit;

use App\Services\LinkExtractor;
use App\Services\Network\NetworkTransport;
use App\Services\Network\PublicTarget;
use App\Services\Network\Resolver;
use App\Services\Network\ResourceLimit;
use App\Services\Network\SafeHttpClient;
use App\Services\Network\ScanBudget;
use App\Services\Network\Target;
use PHPUnit\Framework\TestCase;

class SafeHttpTest extends TestCase
{
    private function client(array $responses): array
    {
        $resolver = new class extends Resolver
        {
            public function lookup(string $host): array
            {
                return ['93.184.216.34'];
            }
        };
        $transport = new class($responses)extends NetworkTransport
        {
            public array $calls = [];

            public function __construct(private array $responses) {}

            public function get(Target $target, int $timeout): array
            {
                $this->calls[] = $target;
                $r = array_shift($this->responses);
                if ($r instanceof \Throwable) {
                    throw $r;
                }

                return $r;
            }
        };

        return [new SafeHttpClient(new PublicTarget($resolver), $transport), $transport];
    }

    private function response(int $status = 200, array $headers = [], string $body = ''): array
    {
        return ['status' => $status, 'headers' => $headers, 'body' => $body, 'duration_ms' => 12];
    }

    public function test_redirect_to_private_ip_is_stopped_before_another_request(): void
    {
        [$c,$t] = $this->client([$this->response(302, ['location' => 'http://169.254.169.254/latest/meta-data/'])]);
        $r = $c->check('https://example.test', new ScanBudget);
        $this->assertSame('blocked', $r['kind']);
        $this->assertCount(1, $t->calls);
        $this->assertCount(1, $r['chain']);
    }

    public function test_relative_redirect_chain_is_followed_and_recorded(): void
    {
        [$c,$t] = $this->client([$this->response(301, ['location' => '/next']), $this->response(200, ['content-type' => 'text/html'], 'ok')]);
        $r = $c->check('https://example.test/start', new ScanBudget);
        $this->assertSame('ok', $r['kind']);
        $this->assertSame('https://example.test/next', $r['final_url']);
        $this->assertSame([301, 200], array_column($r['chain'], 'status'));
        $this->assertSame('93.184.216.34', $t->calls[1]->ip);
    }

    public function test_redirect_loops_and_missing_location_are_explained(): void
    {
        [$c,$t] = $this->client([$this->response(302, ['location' => '/'])]);
        $r = $c->check('https://example.test/', new ScanBudget);
        $this->assertSame('redirect_error', $r['kind']);
        $this->assertCount(1, $t->calls);
        [$c] = $this->client([$this->response(302)]);
        $this->assertSame('redirect_error', $c->check('https://example.test', new ScanBudget)['kind']);
    }

    public function test_redirect_limit_is_three_hops(): void
    {
        [$c,$t] = $this->client(array_map(fn ($n) => $this->response(302, ['location' => '/hop-'.$n]), range(1, 4)));
        $r = $c->check('https://example.test', new ScanBudget);
        $this->assertSame('redirect_error', $r['kind']);
        $this->assertCount(4, $t->calls);
    }

    public function test_status_classification_uses_received_http_codes(): void
    {
        foreach ([200 => 'ok', 204 => 'ok', 404 => 'broken', 410 => 'broken', 503 => 'server_error', 304 => 'other'] as $code => $kind) {
            [$c] = $this->client([$this->response($code)]);
            $r = $c->check('https://example.test', new ScanBudget);
            $this->assertSame($code, $r['status_code']);
            $this->assertSame($kind, $r['kind']);
        }
    }

    public function test_timeout_and_body_limit_have_no_fabricated_http_code(): void
    {
        [$c] = $this->client([new \RuntimeException('Koneksi melewati batas waktu.')]);
        $r = $c->check('https://example.test', new ScanBudget);
        $this->assertSame('connection_error', $r['kind']);
        $this->assertNull($r['status_code']);
        [$c] = $this->client([new ResourceLimit('Respons terlalu besar.')]);
        $this->assertSame('limited', $c->check('https://example.test', new ScanBudget)['kind']);
    }

    public function test_request_and_time_budgets_are_enforced(): void
    {
        [$c,$t] = $this->client([$this->response(302, ['location' => '/next'])]);
        $r = $c->check('https://example.test', new ScanBudget(1));
        $this->assertSame('limited', $r['kind']);
        $this->assertCount(1, $t->calls);
        $budget = new ScanBudget(10, 0);
        $this->assertTrue($budget->exhausted());
        $this->expectException(ResourceLimit::class);
        $budget->take();
    }

    public function test_extractor_handles_base_relative_links_fragments_duplicates_and_non_http(): void
    {
        [$c] = $this->client([]);
        $extractor = new LinkExtractor($c);
        $html = '<base href="https://example.test/folder/"><a href="next#one">A</a><a href="next#two">B</a><a href="/absolute">C</a><a href="mailto:a@example.test">D</a><a href="#section">E</a>';
        $this->assertSame(['https://example.test/folder/next', 'https://example.test/absolute'], $extractor->extract($html, 'https://example.test/'));
        $this->assertSame([], $extractor->extract('', 'https://example.test/'));
    }

    public function test_extraction_has_a_hard_cap(): void
    {
        [$c] = $this->client([]);
        $html = '';
        for ($i = 0; $i < 150; $i++) {
            $html .= '<a href="/page-'.$i.'">link</a>';
        }$this->assertCount(100, (new LinkExtractor($c))->extract($html, 'https://example.test'));
    }
}
