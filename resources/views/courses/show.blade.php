<x-layout :title="$course->code">
    <h1>{{ $course->code }} — {{ $course->title }}</h1>
    <p>{{ $course->description }}</p>
    <p>
        Instructor: {{ $course->instructor?->name ?? '—' }}
        · Units: {{ $course->units }}
    </p>
    <a href="{{ route('courses.edit', $course) }}">Edit</a>
</x-layout>
