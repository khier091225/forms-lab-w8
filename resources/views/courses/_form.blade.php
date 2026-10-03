@if ($errors->any())
    <div class="error-summary">
        <strong>Please fix the {{ $errors->count() }} error(s) below.</strong>
    </div>
@endif

<x-forms.input name="code" label="Code" :value="$course->code" />
<x-forms.input name="title" label="Title" :value="$course->title" />

<x-forms.input name="description" label="Description" type="textarea"
    :value="$course->description" rows="3" />

<x-forms.input name="units" label="Units" type="number" :value="$course->units ?? 3" />

<x-forms.input name="instructor_id" label="Instructor" type="select"
    :options="$instructors->pluck('name', 'id')" :selected="$course->instructor_id" />

<x-forms.input name="is_active" label="Active" type="checkbox" :value="$course->is_active ?? true" />

@if ($course->image_path)
    <img src="{{ asset('storage/' . $course->image_path) }}"
         alt="Current image" width="120">
@endif
<x-forms.input name="image" label="Image" type="file" accept="image/*" />
