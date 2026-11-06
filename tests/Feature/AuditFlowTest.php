<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Network\NetworkTransport;
use App\Services\Network\Resolver;
use App\Services\Network\Target;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFlowTest extends TestCase
{
    use RefreshDatabase;

    private function fakeNetwork(): NetworkTransport
    {
        $this->app->instance(Resolver::class, new class extends Resolver
        {
            public function lookup(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
        $transport = new class extends NetworkTransport
        {
            public array $calls = [];

            public function get(Target $target, int $timeout): array
            {
                $this->calls[] = $target;

                return ['status' => str_contains($target->url, 'missing') ? 404 : 200, 'headers' => ['content-type' => 'text/html'], 'body' => str_ends_with($target->url, '/') ? '<a href="/missing">Missing</a><a href="http://127.0.0.1/private">Private</a><a href="/ok">OK</a>' : '', 'duration_ms' => 15];
            }
        };
        $this->app->instance(NetworkTransport::class, $transport);

        return $transport;
    }

    public function test_real_flow_persists_filtered_results_and_csv(): void
    {
        $network = $this->fakeNetwork();
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/audits', ['url' => 'https://example.test', 'maximum' => 5])->assertRedirect();
        $audit = Audit::first();
        $this->assertSame('completed', $audit->status);
        $this->assertSame(3, $audit->requests);
        $this->assertSame(4, $audit->results()->count());
        $this->assertCount(3, $network->calls);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('HTTP 4xx');
        $this->get('/audits/'.$audit->id.'?kind=broken')->assertOk()->assertSee('/missing')->assertDontSee('http://127.0.0.1/private');
        $csv = $this->get('/audits/'.$audit->id.'/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('404', $csv);
        $this->assertStringContainsString('http://127.0.0.1/private', $csv);
        $this->get('/audits')->assertOk()->assertSee('https://example.test/');
    }

    public function test_url_limit_is_reported_as_partial_and_skipped_count_is_real(): void
    {
        $this->fakeNetwork();
        $audit = Audit::create(['user_id' => User::factory()->create()->id, 'url' => 'https://example.test/', 'maximum' => 2]);
        app(AuditService::class)->run($audit);
        $audit->refresh();
        $this->assertSame('partial', $audit->status);
        $this->assertSame(4, $audit->discovered);
        $this->assertSame(2, $audit->results()->count());
    }

    public function test_unsafe_root_is_rejected_without_network_calls_or_audit(): void
    {
        $network = $this->fakeNetwork();
        $this->actingAs(User::factory()->create())->post('/audits', ['url' => 'http://127.0.0.1', 'maximum' => 5])->assertSessionHasErrors('url');
        $this->assertCount(0, $network->calls);
        $this->assertDatabaseCount('audits', 0);
    }

    public function test_audit_history_and_export_cannot_be_read_by_another_account(): void
    {
        $this->fakeNetwork();
        $owner = User::factory()->create();
        $audit = Audit::create(['user_id' => $owner->id, 'url' => 'https://example.test/', 'maximum' => 5]);
        $this->actingAs(User::factory()->create())->get('/audits/'.$audit->id)->assertForbidden();
        $this->get('/audits/'.$audit->id.'/export')->assertForbidden();
        $this->get('/audits')->assertDontSee('https://example.test/');
    }

    public function test_input_validation_and_authentication(): void
    {
        $this->post('/audits', ['url' => 'https://example.test', 'maximum' => 5])->assertRedirect('/masuk');
        $this->actingAs(User::factory()->create())->post('/audits', ['url' => 'https://example.test', 'maximum' => 1000])->assertSessionHasErrors('maximum');
    }
}
