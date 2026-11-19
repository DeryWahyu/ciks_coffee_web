<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - Ciks Coffee</title>
    <x-brand-icons />
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#FAF7F2;color:#3E2723;font-family:Arial,sans-serif;padding:24px}main{max-width:520px;background:white;border-radius:20px;padding:32px}small{letter-spacing:.12em}h1{font-size:24px}p{line-height:1.7}a{display:inline-block;margin-top:12px;background:#3E2723;color:white;padding:12px 18px;border-radius:10px;text-decoration:none}
    </style>
</head>
<body>
    <main role="main">
        <x-brand-logo style="display:block;width:52px;height:52px;margin-bottom:16px;" />
        <small>CIKS COFFEE · {{ $status }}</small>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/login') }}">Kembali ke aplikasi</a>
    </main>
</body>
</html>
