<x-layout :title="$course->code">
    <h1>{{ $course->code }} — {{ $course->title }}</h1>

    @if ($course->image_path)
        <img src="{{ asset('storage/' . $course->image_path) }}"
             alt="{{ $course->title }}" width="200">
    @endif

    <p>{{ $course->description }}</p>
    <p>
        Instructor: {{ $course->instructor?->name ?? '—' }}
        · Units: {{ $course->units }}
        · {{ $course->is_active ? 'Active' : 'Inactive' }}
    </p>
    <a href="{{ route('courses.edit', $course) }}">Edit</a>

    <form method="POST" action="{{ route('courses.destroy', $course) }}"
          onsubmit="return confirm('Delete this course? This cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
</x-layout>
