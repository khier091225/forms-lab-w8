@props(['name', 'label', 'options' => [], 'selected' => null, 'placeholder' => '— none —'])

<label for="{{ $name }}">{{ $label }}</label>
<select id="{{ $name }}" name="{{ $name }}" {{ $attributes }}>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $value => $text)
        <option value="{{ $value }}" @selected(old($name, $selected) == $value)>
            {{ $text }}
        </option>
    @endforeach
</select>
@error($name) <p class="error">{{ $message }}</p> @enderror
