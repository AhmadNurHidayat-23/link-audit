@extends('layout')@section('content')<section class="hero">
<div>
<span class="eyebrow">01 / TELUSURI KUALITAS TAUTAN</span>
<h1>Ke mana<br>tautan Anda<br>
<em>berakhir?</em>
</h1>
<p>Periksa status HTTP, ikuti jejak redirect, dan temukan tautan yang perlu diperbaiki. Semua dalam satu laporan yang tersimpan.</p>
<div class="hero-labels">
<span>STATUS HTTP</span>
<span>REDIRECT CHAIN</span>
<span>LAPORAN CSV</span>
</div>
</div>
<section class="scan-panel">
<div class="panel-top">
<span class="live-dot">
</span> PEMINDAIAN HALAMAN PUBLIK <span>GET ↗</span>
</div>
<h2>Mulai dari satu URL.</h2>
<p>Halaman awal dan tautannya akan diperiksa dengan batas yang Anda pilih.</p>
<form action="/audits" method="post">
@csrf
<label>URL website<input name="url" type="url" value="{{ old('url') }}" required maxlength="1800" placeholder="https://example.com" autocomplete="url">
</label>
<label>Batas URL yang diperiksa<select name="maximum" aria-label="Batas URL yang diperiksa">
<option value="5" @selected(old('maximum')==5)>5 URL — ringkas</option>
<option value="10" @selected(old('maximum',10)==10)>10 URL — standar</option>
<option value="15" @selected(old('maximum')==15)>15 URL — lebih luas</option>
</select>
</label>
<button type="submit" class="button">
@auth
Jalankan audit ↗
@else
Masuk untuk audit ↗
@endauth
</button>
<small>Pemindaian dapat berlangsung sekitar 20 detik. Hindari URL yang memuat token atau informasi pribadi.</small>
</form>
<div class="scan-limits">
<span>20 permintaan maks.</span>
<span>4 detik / permintaan</span>
<span>512 KB / respons</span>
<span>Kedalaman 1</span>
</div>
</section>
</section>
<section class="method">
<div>
<span class="eyebrow">BUKAN SEKADAR ANGKA STATUS</span>
<h2>Lihat jalurnya.<br>Pahami masalahnya.</h2>
</div>
<article>
<b>01</b>
<h3>Status yang nyata.</h3>
<p>HTTP 2xx, 4xx, 5xx, timeout, dan kegagalan koneksi dicatat terpisah.</p>
</article>
<article>
<b>02</b>
<h3>Redirect yang terlihat.</h3>
<p>Setiap perpindahan alamat ditelusuri dan divalidasi sebelum dihubungi.</p>
</article>
<article>
<b>03</b>
<h3>Hasil yang bisa dibawa.</h3>
<p>Filter hasil, buka riwayat, dan unduh rincian dalam format CSV.</p>
</article>
</section>@endsection
