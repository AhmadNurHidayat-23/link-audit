@extends('layout')@section('content')<section class="workspace">
<div class="section-heading">
<div>
<span class="eyebrow">WORKSPACE / RIWAYAT</span>
<h1>Jejak pemeriksaan.</h1>
<p>Audit yang benar-benar Anda jalankan, tersimpan untuk ditinjau kembali.</p>
</div>
<a class="button" href="/">Audit baru ↗</a>
</div>
@if($audits->isEmpty())<div class="empty">
<span>↗</span>
<h2>Belum ada audit.</h2>
<p>Mulai dari URL publik. Hasil dan ringkasannya akan muncul di sini.</p>
<a href="/" class="text-button">Jalankan audit pertama →</a>
</div>
@else
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Audit / waktu</th>
<th>URL awal</th>
<th>Hasil</th>
<th>Status</th>
<th>
</th>
</tr>
</thead>
<tbody>
@foreach($audits as $a)<tr>
<td>#{{ str_pad($a->id,4,'0',STR_PAD_LEFT) }}<small>{{ $a->created_at->format('d M Y · H:i') }} WIB</small>
</td>
<td class="url-cell">{{ $a->url }}</td>
<td>{{ $a->results_count }} URL</td>
<td>
<span class="badge">{{ $a->status==='partial'?'Sebagian':($a->status==='completed'?'Selesai':($a->status==='failed'?'Terhenti':'Diproses')) }}</span>
</td>
<td>
<a class="text-button" href="/audits/{{ $a->id }}">Lihat laporan ↗</a>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>{{ $audits->links() }}@endif
</section>@endsection
