<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API reference · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/js/api-docs.js'])
    <style>body{margin:0;font-family:Inter,system-ui,sans-serif}.docs-bar{display:flex;align-items:center;gap:1rem;padding:.75rem 1.25rem;background:#2b211b;color:#f6efe6}.docs-bar a{color:#d9c7b0}</style>
</head>
<body>
    <div class="docs-bar"><strong>CafeFlow API v1</strong><a href="{{ route('home') }}">Back to site</a><a href="{{ route('docs.openapi') }}" download>Download openapi.yaml</a></div>
    <div id="swagger" data-spec="{{ route('docs.openapi') }}"></div>
</body>
</html>
