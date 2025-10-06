@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'default',
    'href' => null
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2';

$variants = [
    'primary' => 'bg-lake-blue-600 hover:bg-lake-blue-700 text-white focus:ring-lake-blue-500',
    'secondary' => 'bg-sunset-orange-100 hover:bg-sunset-orange-200 text-sunset-orange-700 border border-sunset-orange-300 focus:ring-sunset-orange-500',
    'danger' => 'bg-warm-red hover:bg-red-700 text-white focus:ring-red-500'
];

$sizes = [
    'small' => 'px-4 py-2 text-sm',
    'default' => 'px-6 py-3 text-base',
    'large' => 'px-8 py-4 text-lg'
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $sizes[$size];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif