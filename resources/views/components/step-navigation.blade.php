@props([
    'currentStep' => 1,
    'totalSteps' => 5,
    'canGoBack' => true,
    'canGoNext' => false,
    'isComplete' => false,
    'nextLabel' => 'Continue',
    'backLabel' => 'Back'
])

<div class="sticky bottom-0 bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
    <div class="max-w-md mx-auto">
        <!-- Progress indicator -->
        <div class="flex items-center justify-center mb-4">
            <div class="flex items-center space-x-2">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                            {{ $i < $currentStep ? 'bg-lake-blue-600 text-white' : ($i == $currentStep ? 'bg-lake-blue-100 text-lake-blue-600 ring-2 ring-lake-blue-600' : 'bg-gray-100 text-gray-400') }}">
                            @if($i < $currentStep)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        @if($i < $totalSteps)
                            <div class="w-6 h-0.5 {{ $i < $currentStep ? 'bg-lake-blue-600' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        <!-- Step label -->
        <p class="text-center text-sm text-gray-600 mb-4">
            Step {{ $currentStep }} of {{ $totalSteps }}
            @if($isComplete) • Complete @endif
        </p>

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between space-x-4">
            @if($canGoBack && $currentStep > 1)
                <button
                    type="button"
                    onclick="goToPreviousStep()"
                    class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-xl font-medium text-center
                           hover:bg-gray-200 active:bg-gray-300 transition-colors duration-150
                           focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2
                           min-h-[48px] text-base">
                    {{ $backLabel }}
                </button>
            @else
                <div class="flex-1"></div>
            @endif

            <button
                type="button"
                onclick="goToNextStep()"
                {{ $canGoNext ? '' : 'disabled' }}
                class="flex-1 py-3 px-4 rounded-xl font-medium text-center min-h-[48px] text-base
                       transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2
                       {{ $canGoNext
                          ? 'bg-lake-blue-600 text-white hover:bg-lake-blue-700 active:bg-lake-blue-800 focus:ring-lake-blue-500 shadow-lg hover:shadow-xl'
                          : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                @if($isComplete)
                    Review Order
                @elseif($currentStep == $totalSteps)
                    Complete
                @else
                    {{ $nextLabel }}
                @endif
            </button>
        </div>
    </div>
</div>

<script>
    function goToPreviousStep() {
        if (typeof window.previousStep === 'function') {
            window.previousStep();
        } else {
            // Fallback - emit Alpine.js event
            window.dispatchEvent(new CustomEvent('step-previous'));
        }
    }

    function goToNextStep() {
        if (typeof window.nextStep === 'function') {
            window.nextStep();
        } else {
            // Fallback - emit Alpine.js event
            window.dispatchEvent(new CustomEvent('step-next'));
        }
    }
</script>