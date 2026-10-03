@props(['title' => 'Student Portal'])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 2rem auto; }
        label { display: block; margin-top: .75rem; }
        .error { color: #c00; margin: .25rem 0; }
        .error-summary { background: #fdecea; border: 1px solid #c00; padding: .5rem 1rem; }
        .success { color: #080; }
    </style>
</head>
<body>
    <nav>
        <a href="{{ route('courses.index') }}">Courses</a> |
        <a href="{{ route('courses.create') }}">New</a>
    </nav>

    @if (session('status'))
        <p class="success">{{ session('status') }}</p>
    @endif

    {{ $slot }}
</body>
</html>
