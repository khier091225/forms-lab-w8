@props(['name', 'label', 'type' => 'text', 'value' => null])

<label for="{{ $name }}">{{ $label }}</label>
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
    @if ($type !== 'file') value="{{ old($name, $value) }}" @endif
    {{ $attributes }}>
@error($name) <p class="error">{{ $message }}</p> @enderror
