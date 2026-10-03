<label for="code">Code</label>
<input id="code" name="code" value="{{ old('code', $course->code) }}">
@error('code') <p class="error">{{ $message }}</p> @enderror

<label for="title">Title</label>
<input id="title" name="title" value="{{ old('title', $course->title) }}">
@error('title') <p class="error">{{ $message }}</p> @enderror

<label for="description">Description</label>
<textarea id="description" name="description" rows="3"
    >{{ old('description', $course->description) }}</textarea>
@error('description') <p class="error">{{ $message }}</p> @enderror

<label for="units">Units</label>
<input id="units" name="units" type="number"
    value="{{ old('units', $course->units ?? 3) }}">
@error('units') <p class="error">{{ $message }}</p> @enderror

<label for="instructor_id">Instructor</label>
<select id="instructor_id" name="instructor_id">
    <option value="">— none —</option>
    @foreach ($instructors as $instructor)
        <option value="{{ $instructor->id }}"
            @selected(old('instructor_id', $course->instructor_id) == $instructor->id)>
            {{ $instructor->name }}
        </option>
    @endforeach
</select>
@error('instructor_id') <p class="error">{{ $message }}</p> @enderror

<input type="hidden" name="is_active" value="0">
<label>
    <input type="checkbox" name="is_active" value="1"
        @checked(old('is_active', $course->is_active ?? true))>
    Active
</label>

@if ($course->image_path)
    <img src="{{ asset('storage/' . $course->image_path) }}"
         alt="Current image" width="120">
@endif
<label for="image">Image</label>
<input id="image" name="image" type="file" accept="image/*">
@error('image') <p class="error">{{ $message }}</p> @enderror
