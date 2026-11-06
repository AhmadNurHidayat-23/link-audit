<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Link Audit')</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body>
<header class="site-header">
<a class="brand" href="/">link<span>/</span>audit<small>HTTP INSPECTION WORKSPACE</small>
</a>
<nav>
<a href="/">Pemindaian baru</a>
@auth<a href="/audits">Riwayat audit</a>
<form method="post" action="/keluar">
@csrf
<button class="text-button">Keluar ↗</button>
</form>
@else
<a href="/masuk">Masuk ↗</a>
@endauth
</nav>
</header>
@if($errors->any())<div role="alert" class="notice error">
<strong>Periksa input Anda.</strong>
<ul>
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif
@if(session('success'))<p role="status" class="notice success">{{ session('success') }}</p>
@endif
@if(session('error'))<p role="alert" class="notice error">{{ session('error') }}</p>
@endif
<main>@yield('content')</main>
<footer>
<a href="/" class="brand small">link/audit.</a>
<p>Data HTTP aktual. Riwayat milik Anda.</p>
<small>Batas terukur, hasil yang dapat ditelusuri.</small>
</footer>
</body>
</html>
