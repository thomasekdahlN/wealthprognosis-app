<div class="space-y-6">
    {{-- Owner Information Section --}}
    <x-filament::section>
        <x-slot name="heading">
            Configuration information
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-filament::fieldset>
                    <x-slot name="label">
                        Name
                    </x-slot>

                    <div class="text-lg font-bold text-gray-900">
                        {{ $record->name }}
                    </div>
                </x-filament::fieldset>
            </div>

            @if($record->description)
                <div class="md:col-span-2">
                    <x-filament::fieldset>
                        <x-slot name="label">
                            Description
                        </x-slot>

                        <div class="text-gray-700">
                            {{ $record->description }}
                        </div>
                    </x-filament::fieldset>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- AI Evaluation Results Section --}}
    @if($aiEvaluationResults)
        <x-filament::section>
            <x-slot name="heading">
                AI Evaluation Results
            </x-slot>

            <div class="space-y-4">
                @foreach($aiEvaluationResults as $result)
                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <div class="flex items-center mb-3">
                            <span class="text-lg mr-2">
                                @if($result['success'] ?? false)
                                    ✅
                                @else
                                    ❌
                                @endif
                            </span>
                            <h4 class="text-lg font-semibold {{ ($result['success'] ?? false) ? 'text-green-600' : 'text-red-600' }}">
                                {{ is_string($result['instruction_name'] ?? null) ? $result['instruction_name'] : 'Unknown Instruction' }}
                            </h4>
                            @php
                                $tokensUsed = $result['tokens_used'] ?? 0;
                                $tokensUsed = is_numeric($tokensUsed) ? (int)$tokensUsed : 0;
                            @endphp
                            @if($tokensUsed > 0)
                                <span class="ml-auto text-sm text-gray-500">({{ $tokensUsed }} tokens)</span>
                            @endif
                        </div>

                        @if(($result['success'] ?? false) && ($result['evaluation'] ?? null))
                            <div class="prose prose-sm max-w-none prose-headings:text-gray-900 prose-p:text-gray-700 prose-strong:text-gray-900 prose-ul:text-gray-700 prose-ol:text-gray-700">
                                {!! \App\Helpers\MarkdownHelper::aiContentToHtml($result['evaluation']) !!}
                            </div>
                        @elseif($result['error'] ?? null)
                            <div class="text-red-600 bg-red-50 p-3 rounded border border-red-200">
                                <strong>Error:</strong>
                                <div class="mt-1">
                                    {!! \App\Helpers\MarkdownHelper::aiContentToHtml($result['error']) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</div>
