@extends('layout')@section('content')@php($register=request('mode')==='daftar')<section class="auth-shell">
<div>
<span class="eyebrow">AKUN / RUANG KERJA PRIBADI</span>
<h1>{{ $register?'Mulai ruang kerja.':'Kembali memeriksa.' }}</h1>
<p>Riwayat URL dan hasil audit hanya tersedia di akun Anda.</p>
</div>
<form method="post" action="{{ $register?'/daftar':'/masuk' }}">
@csrf @if($register)<label>Nama lengkap<input name="name" required maxlength="80" value="{{ old('name') }}" autocomplete="name">
</label>
@endif
<label>Email<input name="email" type="email" required value="{{ old('email') }}" autocomplete="email">
</label>
<label>Kata sandi<input name="password" type="password" required minlength="{{ $register?12:1 }}" autocomplete="{{ $register?'new-password':'current-password' }}">
</label>
@if($register)<label>Ulangi kata sandi<input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password">
</label>
<small>Gunakan minimal 12 karakter.</small>
@endif
<button type="submit" class="button">{{ $register?'Daftar ruang kerja':'Masuk' }}</button>
<a class="text-button" href="{{ $register?'/masuk':'/masuk?mode=daftar' }}">{{ $register?'Sudah punya akun? Masuk':'Belum punya akun? Daftar' }}</a>
</form>
</section>@endsection
