@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'required' => false,
    'placeholder' => null,
    'value' => null,
    'error' => null
])

<div class="space-y-2">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)
                <span class="text-warm-red">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->merge([
            'class' => 'block w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-lake-blue-500 focus:border-lake-blue-500 transition-colors duration-200 ' .
            ($error ? 'border-warm-red focus:ring-warm-red focus:border-warm-red' : '')
        ]) }}
    />

    @if($error)
        <p class="text-sm text-warm-red">{{ $error }}</p>
    @endif
</div>