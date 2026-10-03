@props([
    'name', 'label', 'type' => 'text', 'value' => null,
    'options' => [], 'selected' => null, 'placeholder' => '— none —',
])

@if ($type === 'select')
    <x-forms.select :name="$name" :label="$label" :options="$options"
        :selected="$selected ?? $value" :placeholder="$placeholder" {{ $attributes }} />
@else
    @php($fieldValue = old($name, $value))

    @if ($type === 'checkbox')
        <input type="hidden" name="{{ $name }}" value="0">
        <label for="{{ $name }}">
            <input id="{{ $name }}" name="{{ $name }}" type="checkbox" value="1"
                @checked($fieldValue) {{ $attributes }}>
            {{ $label }}
        </label>
    @else
        <label for="{{ $name }}">{{ $label }}</label>
        @if ($type === 'textarea')
            <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes }}>{{ is_scalar($fieldValue) ? $fieldValue : '' }}</textarea>
        @else
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
                @if ($type !== 'file') value="{{ is_scalar($fieldValue) ? $fieldValue : '' }}" @endif
                {{ $attributes }}>
        @endif
    @endif

    @error($name) <p class="error">{{ $message }}</p> @enderror
@endif
