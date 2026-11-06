<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\AuditService;
use App\Services\Network\PublicTarget;
use App\Services\Network\UnsafeTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuditController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function history(Request $r)
    {
        $audits = Audit::where('user_id', $r->user()->id)->withCount('results')->latest()->paginate(15);

        return view('history', compact('audits'));
    }

    public function store(Request $r, PublicTarget $targets, AuditService $service)
    {
        $d = $r->validate(['url' => 'required|string|max:1800', 'maximum' => 'required|integer|in:5,10,15']);
        try {
            $target = $targets->resolve(trim($d['url']));
        } catch (UnsafeTarget $e) {
            throw ValidationException::withMessages(['url' => $e->getMessage()]);
        }$audit = Audit::create(['user_id' => $r->user()->id, 'url' => $target->url, 'maximum' => $d['maximum']]);
        try {
            $service->run($audit);
        } catch (\Throwable $e) {
            $audit->update(['status' => 'failed', 'note' => 'Pemindaian terhenti. Hasil yang sudah tersimpan tetap tersedia.', 'finished_at' => now()]);
            report($e);
        }

        return redirect()->route('audit.show', $audit)->with($audit->fresh()->status === 'failed' ? 'error' : 'success', $audit->status === 'failed' ? 'Pemindaian terhenti. Periksa hasil yang tersedia.' : 'Pemindaian selesai. Lihat hasil dan batas pemeriksaan pada laporan.');
    }

    private function owner(Request $r, Audit $audit): void
    {
        abort_unless($audit->user_id === $r->user()->id, 403);
    }

    public function show(Request $r, Audit $audit)
    {
        $this->owner($r, $audit);
        $f = $r->validate(['kind' => 'nullable|in:ok,broken,server_error,redirect_error,connection_error,blocked,limited,other', 'status_code' => 'nullable|integer|min:100|max:599']);
        $query = $audit->results();
        if (! empty($f['kind'])) {
            $query->where('kind', $f['kind']);
        }if (! empty($f['status_code'])) {
            $query->where('status_code', $f['status_code']);
        }$results = $query->orderBy('id')->get();
        $counts = $audit->results()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind');
        $checked = $audit->results()->count();

        return view('show', compact('audit', 'results', 'counts', 'checked', 'f'));
    }

    public function export(Request $r, Audit $audit)
    {
        $this->owner($r, $audit);

        return response()->streamDownload(function () use ($audit) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['URL', 'URL akhir', 'HTTP', 'Kategori', 'Durasi ms', 'Catatan', 'Redirect'], ',', '"', '');
            foreach ($audit->results()->orderBy('id')->cursor() as $row) {
                $values = [$row->url, $row->final_url, $row->status_code, $row->label, $row->duration_ms, $row->message, json_encode($row->chain, JSON_UNESCAPED_SLASHES)];
                fputcsv($out, array_map(fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v, $values), ',', '"', '');
            }fclose($out);
        }, 'link-audit-'.$audit->id.'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
