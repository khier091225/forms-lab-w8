<x-layout title="Courses">
    <h1>Courses</h1>
    <ul>
        @foreach ($courses as $c)
            <li>
                <a href="{{ route('courses.show', $c) }}">
                    {{ $c->code }} — {{ $c->title }}
                </a>
            </li>
        @endforeach
    </ul>
</x-layout>
