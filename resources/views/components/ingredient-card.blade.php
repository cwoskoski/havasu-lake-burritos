@props([
    'ingredient' => null,
    'selected' => false,
    'showPortion' => false,
    'selectionType' => 'multiple' // 'single' for radio, 'multiple' for checkbox
])

@php
$baseClasses = 'relative bg-white border rounded-xl p-3 sm:p-4 transition-all duration-200 ease-in-out cursor-pointer';
$baseClasses .= ' hover:shadow-lg hover:scale-105 active:scale-95'; // Mobile touch feedback
$baseClasses .= ' min-h-[80px] sm:min-h-[100px]'; // Adequate touch target size
$baseClasses .= ' focus:outline-none focus:ring-2 focus:ring-lake-blue-500 focus:ring-offset-2';

$selectedClasses = $selected
    ? 'border-lake-blue-500 ring-2 ring-lake-blue-200 bg-lake-blue-50'
    : 'border-gray-200 hover:border-gray-300';

$classes = $baseClasses . ' ' . $selectedClasses;
@endphp

<div
    class="{{ $classes }}"
    role="button"
    tabindex="0"
    aria-pressed="{{ $selected ? 'true' : 'false' }}"
    @if($ingredient)
        aria-describedby="ingredient-{{ $ingredient->id }}-description"
    @endif
>
    <!-- Selection Indicator -->
    <div class="absolute top-2 right-2 w-5 h-5 sm:w-6 sm:h-6">
        @if($selectionType === 'single')
            <!-- Radio button style -->
            <div class="w-full h-full rounded-full border-2 {{ $selected ? 'border-lake-blue-500 bg-lake-blue-500' : 'border-gray-300' }} flex items-center justify-center">
                @if($selected)
                    <div class="w-2 h-2 sm:w-2.5 sm:h-2.5 bg-white rounded-full"></div>
                @endif
            </div>
        @else
            <!-- Checkbox style -->
            <div class="w-full h-full rounded {{ $selected ? 'bg-lake-blue-500 border-lake-blue-500' : 'border-2 border-gray-300' }} flex items-center justify-center">
                @if($selected)
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                @endif
            </div>
        @endif
    </div>

    <!-- Ingredient Image (placeholder for now) -->
    <div class="w-full h-12 sm:h-16 bg-gray-100 rounded-lg mb-2 sm:mb-3 flex items-center justify-center">
        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
    </div>

    <!-- Ingredient Info -->
    <div class="text-center">
        <h3 class="font-medium text-gray-900 text-sm sm:text-base mb-1 leading-tight">
            @if($ingredient)
                {{ $ingredient->name ?? $ingredient }}
            @else
                Ingredient Name
            @endif
        </h3>

        @if($showPortion && $ingredient && $ingredient->portion_size)
            <p class="text-xs sm:text-sm text-gray-500">
                {{ $ingredient->portion_size }} {{ $ingredient->portion_size == 1 ? 'cup' : 'cups' }}
            </p>
        @endif
    </div>

    @if($ingredient)
        <!-- Screen reader description -->
        <div id="ingredient-{{ $ingredient->id }}-description" class="sr-only">
            {{ $ingredient->description ?? '' }}
            @if($showPortion && $ingredient->portion_size)
                Portion size: {{ $ingredient->portion_size }} {{ $ingredient->portion_size == 1 ? 'cup' : 'cups' }}
            @endif
        </div>
    @endif
</div>