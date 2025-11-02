<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 12px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 28px; height: 28px; color: #667eea;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                </svg>
                <span>AI Comparison Analysis</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Get AI-powered insights comparing these two simulation scenarios
        </x-slot>

        <div>
            @if(!$aiAnalysis && !$isLoading && !$errorMessage)
                <!-- Initial state - show button to trigger analysis -->
                <div style="text-align: center; padding: 40px 20px;">
                    <div style="margin-bottom: 20px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 64px; height: 64px; margin: 0 auto; color: #667eea;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                        </svg>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: rgb(var(--gray-900));">
                        Ready to Analyze
                    </h3>
                    <p style="color: rgb(var(--gray-600)); margin-bottom: 24px; max-width: 600px; margin-left: auto; margin-right: auto;">
                        Click the button below to get AI-powered insights comparing <strong>{{ $simulationA->name }}</strong> and <strong>{{ $simulationB->name }}</strong>. 
                        The AI will analyze financial outcomes, risk factors, and provide actionable recommendations.
                    </p>
                    <button 
                        wire:click="loadAiAnalysis" 
                        type="button"
                        style="
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                            padding: 12px 32px;
                            border-radius: 8px;
                            font-weight: 600;
                            border: none;
                            cursor: pointer;
                            font-size: 16px;
                            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
                            transition: all 0.2s;
                        "
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(102, 126, 234, 0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(102, 126, 234, 0.3)';"
                    >
                        ✨ Generate AI Analysis
                    </button>
                </div>
            @endif

            @if($isLoading)
                <!-- Loading state -->
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="margin-bottom: 24px;">
                        <div style="display: inline-block; position: relative;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" 
                                 style="width: 64px; height: 64px; color: #667eea; animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                            </svg>
                        </div>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: rgb(var(--gray-900));">
                        Analyzing Scenarios...
                    </h3>
                    <p style="color: rgb(var(--gray-600)); margin-bottom: 16px;">
                        Our AI is comparing the two simulations and generating insights.
                    </p>
                    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
                        <div style="width: 12px; height: 12px; background: #667eea; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; animation-delay: -0.32s;"></div>
                        <div style="width: 12px; height: 12px; background: #667eea; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; animation-delay: -0.16s;"></div>
                        <div style="width: 12px; height: 12px; background: #667eea; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both;"></div>
                    </div>
                </div>

                <style>
                    @keyframes pulse {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.5; }
                    }
                    @keyframes bounce {
                        0%, 80%, 100% { 
                            transform: scale(0);
                            opacity: 0.5;
                        }
                        40% { 
                            transform: scale(1);
                            opacity: 1;
                        }
                    }
                </style>
            @endif

            @if($errorMessage)
                <!-- Error state -->
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: start; gap: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 24px; height: 24px; color: #dc2626; flex-shrink: 0;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <div>
                            <h4 style="font-weight: 600; color: #991b1b; margin-bottom: 4px;">Error</h4>
                            <p style="color: #7f1d1d; margin: 0;">{{ $errorMessage }}</p>
                        </div>
                    </div>
                </div>
                <button 
                    wire:click="loadAiAnalysis" 
                    type="button"
                    style="
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 10px 24px;
                        border-radius: 8px;
                        font-weight: 600;
                        border: none;
                        cursor: pointer;
                        font-size: 14px;
                    "
                >
                    🔄 Try Again
                </button>
            @endif

            @if($aiAnalysis && !$isLoading)
                <!-- AI Analysis Result -->
                <div style="background: linear-gradient(135deg, #f8f9ff 0%, #f3f4ff 100%); border: 1px solid #e0e7ff; border-radius: 12px; padding: 24px; margin-bottom: 16px;">
                    <div class="ai-analysis-content prose prose-sm max-w-none dark:prose-invert" style="color: rgb(var(--gray-900));">
                        {!! \Illuminate\Support\Str::markdown($aiAnalysis) !!}
                    </div>
                </div>
                <button 
                    wire:click="loadAiAnalysis" 
                    type="button"
                    style="
                        background: white;
                        color: #667eea;
                        padding: 10px 24px;
                        border-radius: 8px;
                        font-weight: 600;
                        border: 2px solid #667eea;
                        cursor: pointer;
                        font-size: 14px;
                    "
                >
                    🔄 Regenerate Analysis
                </button>
            @endif
        </div>
    </x-filament::section>

    <style>
        .ai-analysis-content h1,
        .ai-analysis-content h2,
        .ai-analysis-content h3,
        .ai-analysis-content h4,
        .ai-analysis-content h5,
        .ai-analysis-content h6 {
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 12px;
            color: rgb(var(--gray-900));
        }

        .ai-analysis-content h1 { font-size: 24px; }
        .ai-analysis-content h2 { font-size: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; }
        .ai-analysis-content h3 { font-size: 18px; }
        .ai-analysis-content h4 { font-size: 16px; }

        .ai-analysis-content p {
            margin: 12px 0;
            line-height: 1.7;
        }

        .ai-analysis-content strong,
        .ai-analysis-content b {
            font-weight: 700;
            color: rgb(var(--gray-900));
        }

        .ai-analysis-content ul,
        .ai-analysis-content ol {
            margin: 12px 0;
            padding-left: 24px;
        }

        .ai-analysis-content li {
            margin: 6px 0;
            line-height: 1.6;
        }

        .ai-analysis-content code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #1f2937;
        }

        .ai-analysis-content pre {
            background: #1f2937;
            color: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 16px 0;
        }

        .ai-analysis-content blockquote {
            border-left: 4px solid #667eea;
            padding-left: 16px;
            margin: 16px 0;
            color: rgb(var(--gray-700));
            font-style: italic;
        }

        .ai-analysis-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }

        .ai-analysis-content th,
        .ai-analysis-content td {
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            text-align: left;
        }

        .ai-analysis-content th {
            background: #f9fafb;
            font-weight: 600;
        }
    </style>
</x-filament-widgets::widget>

