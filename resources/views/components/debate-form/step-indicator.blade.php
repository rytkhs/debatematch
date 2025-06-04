@props(['currentStep' => 1, 'totalSteps' => 2, 'steps' => []])

<!-- ステップインジケーター -->
<div class="flex items-center justify-center mb-8 sm:mb-12">
    <div class="flex items-center space-x-4">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="flex items-center">
                <div id="step{{ $i }}-indicator"
                     class="step-indicator w-8 h-8 {{ $i === $currentStep ? 'bg-indigo-600 text-white' : ($i < $currentStep ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600') }} rounded-full flex items-center justify-center text-sm font-medium transition-colors duration-300">
                    @if ($i < $currentStep)
                        <span class="material-icons-outlined text-sm">check</span>
                    @else
                        {{ $i }}
                    @endif
                </div>
                <span id="step{{ $i }}-text"
                      class="ml-2 text-sm font-medium {{ $i === $currentStep ? 'text-indigo-600' : ($i < $currentStep ? 'text-green-600' : 'text-gray-500') }} transition-colors duration-300">
                    {{ $steps[$i - 1] ?? '' }}
                </span>
            </div>

            @if ($i < $totalSteps)
                <div class="w-8 h-0.5 {{ $currentStep > $i ? 'bg-green-300' : 'bg-gray-300' }} transition-colors duration-300"
                     id="{{ $i === 1 ? 'step-connector' : '' }}"></div>
            @endif
        @endfor
    </div>
</div>
