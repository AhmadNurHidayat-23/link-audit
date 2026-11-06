@extends('layout')@section('content')<section class="workspace">
<a class="back" href="/audits">← Semua audit</a>
<div class="section-heading">
<div>
<span class="eyebrow">LAPORAN / #{{ str_pad($audit->id,4,'0',STR_PAD_LEFT) }}</span>
<h1>Setiap tujuan,<br>tercatat.</h1>
<p class="audit-url">{{ $audit->url }}</p>
</div>
<a class="button secondary" href="/audits/{{ $audit->id }}/export">Unduh CSV ↓</a>
</div>
<div class="summary">
<div>
<strong>{{ $checked }}</strong>
<span>Tujuan diperiksa</span>
</div>
<div>
<strong>{{ $counts['ok']??0 }}</strong>
<span>HTTP berhasil</span>
</div>
<div>
<strong>{{ $checked-($counts['ok']??0) }}</strong>
<span>Perlu ditinjau</span>
</div>
<div>
<strong>{{ $audit->requests }}</strong>
<span>Permintaan HTTP</span>
</div>
</div>
<p class="notice">{{ $audit->note }}<br>{{ $audit->discovered }} tujuan ditemukan; {{ max(0,$audit->discovered-$checked) }} belum diperiksa. Batas sumber daya atau penolakan koneksi tidak otomatis berarti tautan rusak untuk semua pengunjung.</p>
<form method="get" class="filter-bar">
<label>Kategori hasil<select name="kind" aria-label="Kategori hasil">
<option value="">Semua hasil</option>
@foreach(\App\Models\AuditResult::LABELS as $value=>$label)<option value="{{ $value }}" @selected(($f['kind']??'')===$value)>{{ $label }}</option>
@endforeach
</select>
</label>
<label>Status HTTP<input type="number" name="status_code" min="100" max="599" value="{{ $f['status_code']??'' }}" placeholder="Contoh: 404">
</label>
<button type="submit" class="button">Terapkan filter</button>
<a href="/audits/{{ $audit->id }}" class="text-button">Reset</a>
</form>
<div class="results">
@forelse($results as $result)<article class="result-row">
<div class="result-status">
<strong>{{ $result->status_code??'—' }}</strong>
<span class="badge {{ $result->kind }}">{{ $result->label }}</span>
<small>{{ $result->duration_ms?$result->duration_ms.' ms':'Durasi tidak tersedia' }}</small>
</div>
<div class="result-body">
<h2>{{ $result->url }}</h2>
<p>Tujuan akhir <span>→</span> {{ $result->final_url }}</p>
@if($result->message)<p class="result-message">{{ $result->message }}</p>
@endif
<details>
<summary>Jejak HTTP · {{ count($result->chain) }} respons</summary>
<ol>
@forelse($result->chain as $hop)<li>
<b>{{ $hop['status'] }}</b>
<span>{{ $hop['url'] }}</span>
</li>
@empty
<li>Tidak ada respons HTTP; tujuan diblokir atau koneksi belum berhasil.</li>
@endforelse
</ol>
</details>
</div>
</article>
@empty
<div class="empty">
<h2>Tidak ada hasil yang sesuai.</h2>
<p>Coba kategori atau status HTTP lain.</p>
</div>
@endforelse
</div>
</section>@endsection
