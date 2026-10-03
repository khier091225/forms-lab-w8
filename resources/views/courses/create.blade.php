<x-layout title="New Course">
    <h1>New Course</h1>

    <form method="POST" action="{{ route('courses.store') }}"
          enctype="multipart/form-data">
        @csrf
        @include('courses._form')
        <button type="submit">Save</button>
    </form>
</x-layout>
