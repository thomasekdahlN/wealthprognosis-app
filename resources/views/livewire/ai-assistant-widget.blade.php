<div class="fixed bottom-6 right-6 z-[9999]" x-data="{ isTyping: @entangle('isLoading') }" style="position: fixed !important; bottom: 24px !important; right: 24px !important; z-index: 9999 !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Toggle Button - Beautiful Floating AI Assistant -->
    <div style="
            position: fixed !important;
            bottom: 30px !important;
            right: 30px !important;
            z-index: 9999 !important;
         ">
        @if(!$isOpen)
            <button
                wire:click="toggleWidget"
                style="
                    position: relative !important;
                    border: none !important;
                    cursor: pointer !important;
                    width: 70px !important;
                    height: 70px !important;
                    border-radius: 50% !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5), 0 0 0 0 rgba(102, 126, 234, 0.4) !important;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite !important;
                    color: white !important;
                "
                onmouseover="
                    this.style.transform='scale(1.1) rotate(5deg)';
                    this.style.boxShadow='0 15px 35px rgba(102, 126, 234, 0.6), 0 0 0 8px rgba(102, 126, 234, 0.2)';
                "
                onmouseout="
                    this.style.transform='scale(1) rotate(0deg)';
                    this.style.boxShadow='0 10px 25px rgba(102, 126, 234, 0.5), 0 0 0 0 rgba(102, 126, 234, 0.4)';
                "
                title="Open AI Financial Assistant"
            >
                <!-- Sparkle Icon -->
                <svg style="
                    width: 36px !important;
                    height: 36px !important;
                    transition: transform 0.3s ease !important;
                    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)) !important;
                " fill="currentColor" viewBox="0 0 24 24">
                    <!-- Main large sparkle -->
                    <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    <!-- Medium sparkle -->
                    <path d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                    <!-- Small sparkle -->
                    <path d="M16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                    <!-- Extra tiny sparkles for more magic -->
                    <circle cx="6" cy="6" r="0.8" opacity="0.8"/>
                    <circle cx="20" cy="20" r="0.6" opacity="0.6"/>
                    <circle cx="4" cy="18" r="0.5" opacity="0.7"/>
                    <circle cx="21" cy="4" r="0.4" opacity="0.5"/>
                </svg>

                <!-- Animated glow ring -->
                <div style="
                    position: absolute !important;
                    inset: -4px !important;
                    border-radius: 50% !important;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    opacity: 0.3 !important;
                    filter: blur(8px) !important;
                    animation: pulse-glow 2s ease-in-out infinite !important;
                    z-index: -1 !important;
                "></div>
            </button>
        @endif
    </div>

    <!-- Chat Widget -->
    @if($isOpen)
        <div style="
                position: fixed !important;
                bottom: 120px !important;
                right: 30px !important;
                background: #ffffff !important;
                border-radius: 16px !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                width: 800px !important;
                height: 90vh !important;
                max-height: 90vh !important;
                display: flex !important;
                flex-direction: column !important;
                border: 1px solid rgba(0, 0, 0, 0.1) !important;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
             ">
            <!-- Header -->
            <div class="ai-gradient-bg" style="
                    color: #ffffff !important;
                    padding: 16px !important;
                    border-radius: 16px 16px 0 0 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                 ">

                <div style="display: flex !important; align-items: center !important; gap: 12px !important;">
                    <div style="
                        background: rgba(255,255,255,0.2) !important;
                        padding: 10px !important;
                        border-radius: 12px !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                    ">
                        <!-- Sparkle Icon -->
                        <svg style="width: 36px !important; height: 36px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                            <path d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                            <path d="M16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                            <circle cx="6" cy="6" r="0.6" opacity="0.7"/>
                            <circle cx="20" cy="20" r="0.5" opacity="0.6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 style="font-weight: bold !important; font-size: 16px !important; margin: 0 !important; color: #ffffff !important;">AI Financial Assistant</h3>
                        <p style="font-size: 12px !important; margin: 0 !important; color: rgba(255,255,255,0.8) !important;">Your personal finance advisor</p>
                    </div>
                </div>
                <div style="display: flex !important; align-items: center !important; gap: 8px !important;">
                    @if($currentConfigurationId)
                        <span style="
                            font-size: 11px !important;
                            background: rgba(255,255,255,0.2) !important;
                            color: #ffffff !important;
                            padding: 4px 8px !important;
                            border-radius: 12px !important;
                            border: 1px solid rgba(255,255,255,0.2) !important;
                        ">
                            Config: {{ $currentConfigurationId }}
                        </span>
                    @endif
                    <button
                        wire:click="clearConversation"
                        style="
                            color: rgba(255,255,255,0.7) !important;
                            border: none !important;
                            background: transparent !important;
                            cursor: pointer !important;
                            padding: 8px !important;
                            border-radius: 8px !important;
                        "
                        onmouseover="this.style.color='#ffffff'; this.style.background='rgba(255,255,255,0.2)';"
                        onmouseout="this.style.color='rgba(255,255,255,0.7)'; this.style.background='transparent';"
                        title="Clear conversation"
                    >
                        <svg style="width: 16px !important; height: 16px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951z"/>
                        </svg>
                    </button>
                    <button
                        wire:click="toggleWidget"
                        style="
                            color: rgba(255,255,255,0.7) !important;
                            border: none !important;
                            background: transparent !important;
                            cursor: pointer !important;
                            padding: 8px !important;
                            border-radius: 8px !important;
                        "
                        onmouseover="this.style.color='#ffffff'; this.style.background='rgba(255,255,255,0.2)';"
                        onmouseout="this.style.color='rgba(255,255,255,0.7)'; this.style.background='transparent';"
                        title="Close assistant"
                    >
                        <svg style="width: 16px !important; height: 16px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6.225 4.811a1 1 0 00-1.414 1.414L10.586 12 4.81 17.775a1 1 0 101.414 1.414L12 13.414l5.775 5.775a1 1 0 001.414-1.414L13.414 12l5.775-5.775a1 1 0 00-1.414-1.414L12 10.586 6.225 4.81z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Loading Status Bar (Top of Chat) - Shows immediately with wire:loading -->
            <div
                wire:loading
                wire:target="sendMessage"
                id="ai-status-bar"
                style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    color: white !important;
                    padding: 12px 16px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    border-bottom: 1px solid rgba(255,255,255,0.2) !important;
                ">
                <div style="display: flex !important; align-items: center !important; gap: 12px !important;">
                    <svg style="width: 20px !important; height: 20px !important; animation: spin 2s linear infinite !important;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span style="font-weight: 600 !important; font-size: 14px !important;" data-ai-status>Processing your request...</span>
                </div>
                <div style="
                    background: rgba(255,255,255,0.3) !important;
                    padding: 4px 12px !important;
                    border-radius: 12px !important;
                    font-size: 13px !important;
                    font-weight: 600 !important;
                    display: flex !important;
                    align-items: center !important;
                    gap: 4px !important;
                ">
                    <svg style="width: 14px !important; height: 14px !important;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span data-ai-elapsed>0s</span>
                </div>
            </div>

            <!-- Messages Container -->
            <div id="messages-container" style="
                flex: 1 !important;
                overflow-y: auto !important;
                padding: 16px !important;
                background: #f8fafc !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 16px !important;
            ">
                @foreach($this->formattedConversation as $index => $msg)
                    <div style="display: flex !important; {{ $msg['type'] === 'user' ? 'justify-content: flex-end !important;' : 'justify-content: flex-start !important;' }}">
                        @if($msg['type'] === 'assistant')
                            <!-- AI Message -->
                            <div style="max-width: 85% !important; display: flex !important; align-items: flex-start !important; gap: 12px !important;">
                                <div style="
                                    width: 32px !important;
                                    height: 32px !important;
                                    border-radius: 50% !important;
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                    display: flex !important;
                                    align-items: center !important;
                                    justify-content: center !important;
                                    flex-shrink: 0 !important;
                                ">
                                    <svg style="width: 16px !important; height: 16px !important; color: #ffffff !important;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                                    </svg>
                                </div>
                                <div style="
                                    background: #ffffff !important;
                                    border-radius: 16px 16px 16px 4px !important;
                                    padding: 12px !important;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
                                    border: 1px solid rgba(0,0,0,0.05) !important;
                                    position: relative !important;
                                ">
                                    <div style="
                                        position: absolute !important;
                                        left: -6px !important;
                                        top: 12px !important;
                                        width: 12px !important;
                                        height: 12px !important;
                                        background: #ffffff !important;
                                        border-left: 1px solid rgba(0,0,0,0.05) !important;
                                        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
                                        transform: rotate(45deg) !important;
                                    "></div>

                                    <div style="font-size: 14px !important; line-height: 1.5 !important; color: #374151 !important;" class="ai-message-content">
                                        @if(isset($msg['formatted_message']))
                                            {!! $msg['formatted_message'] !!}
                                        @else
                                            <div style="white-space: pre-wrap !important;">{{ $msg['message'] }}</div>
                                        @endif
                                    </div>



                                    <div style="font-size: 11px !important; color: #9ca3af !important; margin-top: 8px !important; display: flex !important; align-items: center !important; gap: 4px !important;">
                                        <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>{{ $msg['timestamp'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- User Message -->
                            <div style="max-width: 85% !important; display: flex !important; align-items: flex-start !important; gap: 12px !important; flex-direction: row-reverse !important;">
                                <div style="
                                    width: 32px !important;
                                    height: 32px !important;
                                    border-radius: 50% !important;
                                    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                                    display: flex !important;
                                    align-items: center !important;
                                    justify-content: center !important;
                                    flex-shrink: 0 !important;
                                ">
                                    <svg style="width: 16px !important; height: 16px !important; color: #ffffff !important;" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z"/>
                                    </svg>
                                </div>
                                <div style="
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                    color: #ffffff !important;
                                    border-radius: 16px 16px 4px 16px !important;
                                    padding: 12px !important;
                                    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3) !important;
                                    position: relative !important;
                                ">
                                    <div style="
                                        position: absolute !important;
                                        right: -6px !important;
                                        top: 12px !important;
                                        width: 12px !important;
                                        height: 12px !important;
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                        transform: rotate(45deg) !important;
                                    "></div>

                                    <div style="font-size: 14px !important; line-height: 1.5 !important; white-space: pre-wrap !important;">{{ $msg['message'] }}</div>

                                    <div style="font-size: 11px !important; color: rgba(255,255,255,0.8) !important; margin-top: 8px !important; text-align: right !important; display: flex !important; align-items: center !important; justify-content: flex-end !important; gap: 4px !important;">
                                        <span>{{ $msg['timestamp'] }}</span>
                                        <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                <!-- Loading indicator with real-time status updates -->
                <div
                    wire:loading
                    wire:target="sendMessage"
                    style="display: flex !important; justify-content: flex-start !important;">
                        <div style="max-width: 85% !important; display: flex !important; align-items: flex-start !important; gap: 12px !important;">
                            <!-- Animated AI Avatar -->
                            <div style="
                                width: 40px !important;
                                height: 40px !important;
                                border-radius: 50% !important;
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                display: flex !important;
                                align-items: center !important;
                                justify-content: center !important;
                                flex-shrink: 0 !important;
                                animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite !important;
                                box-shadow: 0 0 20px rgba(102, 126, 234, 0.5) !important;
                            ">
                                <svg style="
                                    width: 20px !important;
                                    height: 20px !important;
                                    color: white !important;
                                    animation: spin 3s linear infinite !important;
                                " fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                                </svg>
                            </div>

                            <!-- Status Card with Progress Visualization -->
                            <div style="
                                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
                                border-radius: 16px 16px 16px 4px !important;
                                padding: 20px !important;
                                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2) !important;
                                border: 2px solid rgba(102, 126, 234, 0.2) !important;
                                position: relative !important;
                                min-width: 400px !important;
                            ">
                                <!-- Speech bubble tail -->
                                <div style="
                                    position: absolute !important;
                                    left: -8px !important;
                                    top: 16px !important;
                                    width: 16px !important;
                                    height: 16px !important;
                                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
                                    border-left: 2px solid rgba(102, 126, 234, 0.2) !important;
                                    border-bottom: 2px solid rgba(102, 126, 234, 0.2) !important;
                                    transform: rotate(45deg) !important;
                                "></div>

                                <!-- Header with Status and Timer -->
                                <div style="
                                    display: flex !important;
                                    justify-content: space-between !important;
                                    align-items: center !important;
                                    margin-bottom: 12px !important;
                                ">
                                    <!-- Status Text -->
                                    <div style="
                                        font-size: 16px !important;
                                        color: #667eea !important;
                                        font-weight: 600 !important;
                                        display: flex !important;
                                        align-items: center !important;
                                        gap: 8px !important;
                                    ">
                                        <span data-ai-status>Processing your request...</span>
                                    </div>

                                    <!-- Elapsed Time Badge -->
                                    <div style="
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                        color: white !important;
                                        padding: 4px 12px !important;
                                        border-radius: 12px !important;
                                        font-size: 13px !important;
                                        font-weight: 600 !important;
                                        display: flex !important;
                                        align-items: center !important;
                                        gap: 4px !important;
                                        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3) !important;
                                    ">
                                        <svg style="width: 14px !important; height: 14px !important;" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span data-ai-elapsed>0s</span>
                                    </div>
                                </div>

                                <!-- Progress Steps Visualization -->
                                <div style="
                                    display: flex !important;
                                    align-items: center !important;
                                    gap: 8px !important;
                                    margin-bottom: 16px !important;
                                ">
                                    @php
                                        $steps = [
                                            ['icon' => '🔍', 'label' => 'Analyzing', 'match' => 'Analyzing'],
                                            ['icon' => '🧠', 'label' => 'Thinking', 'match' => 'intent'],
                                            ['icon' => '📊', 'label' => 'Loading', 'match' => 'Loading'],
                                            ['icon' => '🤖', 'label' => 'AI Processing', 'match' => 'Sending'],
                                            ['icon' => '✨', 'label' => 'Formatting', 'match' => 'Formatting'],
                                        ];
                                        $currentStep = 0;
                                        foreach ($steps as $index => $step) {
                                            if (str_contains($currentStatus, $step['match'])) {
                                                $currentStep = $index;
                                                break;
                                            }
                                        }
                                    @endphp

                                    @foreach($steps as $index => $step)
                                        <!-- Step Circle -->
                                        <div style="
                                            width: {{ $index <= $currentStep ? '32px' : '24px' }} !important;
                                            height: {{ $index <= $currentStep ? '32px' : '24px' }} !important;
                                            border-radius: 50% !important;
                                            background: {{ $index <= $currentStep ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : '#e2e8f0' }} !important;
                                            display: flex !important;
                                            align-items: center !important;
                                            justify-content: center !important;
                                            font-size: {{ $index <= $currentStep ? '14px' : '12px' }} !important;
                                            transition: all 0.3s ease !important;
                                            box-shadow: {{ $index === $currentStep ? '0 0 15px rgba(102, 126, 234, 0.6)' : 'none' }} !important;
                                            animation: {{ $index === $currentStep ? 'pulse-step 1s ease-in-out infinite' : 'none' }} !important;
                                            position: relative !important;
                                        ">
                                            <span>{{ $step['icon'] }}</span>

                                            @if($index === $currentStep)
                                                <!-- Active indicator ring -->
                                                <div style="
                                                    position: absolute !important;
                                                    inset: -4px !important;
                                                    border-radius: 50% !important;
                                                    border: 2px solid #667eea !important;
                                                    animation: spin-ring 2s linear infinite !important;
                                                "></div>
                                            @endif
                                        </div>

                                        @if($index < count($steps) - 1)
                                            <!-- Connector Line -->
                                            <div style="
                                                flex: 1 !important;
                                                height: 3px !important;
                                                background: {{ $index < $currentStep ? 'linear-gradient(90deg, #667eea, #764ba2)' : '#e2e8f0' }} !important;
                                                border-radius: 2px !important;
                                                transition: all 0.3s ease !important;
                                                position: relative !important;
                                                overflow: hidden !important;
                                            ">
                                                @if($index === $currentStep)
                                                    <!-- Animated progress on current connector -->
                                                    <div style="
                                                        position: absolute !important;
                                                        width: 50% !important;
                                                        height: 100% !important;
                                                        background: linear-gradient(90deg, transparent, #667eea, transparent) !important;
                                                        animation: shimmer 1.5s ease-in-out infinite !important;
                                                    "></div>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- Current Step Label -->
                                <div style="
                                    font-size: 13px !important;
                                    color: #64748b !important;
                                    margin-bottom: 12px !important;
                                    text-align: center !important;
                                    font-weight: 500 !important;
                                ">
                                    Step {{ $currentStep + 1 }} of {{ count($steps) }}: {{ $steps[$currentStep]['label'] }}
                                </div>

                                <!-- Overall Progress Bar -->
                                <div style="
                                    width: 100% !important;
                                    height: 6px !important;
                                    background: rgba(102, 126, 234, 0.1) !important;
                                    border-radius: 3px !important;
                                    overflow: hidden !important;
                                    position: relative !important;
                                    margin-bottom: 12px !important;
                                ">
                                    <div style="
                                        position: absolute !important;
                                        width: {{ (($currentStep + 1) / count($steps)) * 100 }}% !important;
                                        height: 100% !important;
                                        background: linear-gradient(90deg, #667eea, #764ba2) !important;
                                        border-radius: 3px !important;
                                        transition: width 0.5s ease !important;
                                        box-shadow: 0 0 10px rgba(102, 126, 234, 0.5) !important;
                                    "></div>
                                </div>

                                <!-- Animated Dots -->
                                <div style="
                                    display: flex !important;
                                    gap: 6px !important;
                                    justify-content: center !important;
                                ">
                                    <div style="
                                        width: 8px !important;
                                        height: 8px !important;
                                        border-radius: 50% !important;
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                        animation: bounce 1.4s infinite ease-in-out both !important;
                                        box-shadow: 0 0 8px rgba(102, 126, 234, 0.5) !important;
                                    "></div>
                                    <div style="
                                        width: 8px !important;
                                        height: 8px !important;
                                        border-radius: 50% !important;
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                        animation: bounce 1.4s infinite ease-in-out both !important;
                                        animation-delay: -0.32s !important;
                                        box-shadow: 0 0 8px rgba(102, 126, 234, 0.5) !important;
                                    "></div>
                                    <div style="
                                        width: 8px !important;
                                        height: 8px !important;
                                        border-radius: 50% !important;
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                        animation: bounce 1.4s infinite ease-in-out both !important;
                                        animation-delay: -0.16s !important;
                                        box-shadow: 0 0 8px rgba(102, 126, 234, 0.5) !important;
                                    "></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <!-- Input Area -->
            <div style="
                background: #ffffff !important;
                border-top: 1px solid rgba(0,0,0,0.05) !important;
                border-radius: 0 0 16px 16px !important;
                padding: 16px !important;
            ">
                <form wire:submit.prevent="sendMessage" style="margin-bottom: 16px !important;">
                    <div style="position: relative !important;">
                        <input
                            type="text"
                            wire:model.live="message"
                            wire:keydown.enter.prevent="sendMessage"
                            placeholder="Ask me about your finances, assets, or retirement planning..."
                            style="
                                width: 100% !important;
                                padding: 12px 50px 12px 16px !important;
                                font-size: 14px !important;
                                border: 2px solid #e5e7eb !important;
                                border-radius: 12px !important;
                                background: #ffffff !important;
                                color: #374151 !important;
                                outline: none !important;
                                box-sizing: border-box !important;
                            "
                            onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)';"
                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                        >
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                            style="
                                position: absolute !important;
                                right: 8px !important;
                                top: 50% !important;
                                transform: translateY(-50%) !important;
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                                color: #ffffff !important;
                                border: none !important;
                                border-radius: 8px !important;
                                padding: 8px !important;
                                cursor: pointer !important;
                                display: flex !important;
                                align-items: center !important;
                                justify-content: center !important;
                            "
                            onmouseover="if(!this.disabled) this.style.transform='translateY(-50%) scale(1.05)';"
                            onmouseout="this.style.transform='translateY(-50%) scale(1)';"
                        >
                            <svg wire:loading.remove wire:target="sendMessage" style="width: 16px !important; height: 16px !important;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                            <svg wire:loading wire:target="sendMessage" style="width: 16px !important; height: 16px !important; animation: spin 1s linear infinite !important;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Quick Actions -->
                <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px !important; margin-bottom: 16px !important;">
                    <button
                        wire:click="$set('message', 'Create a new financial configuration')"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                        style="
                            font-size: 12px !important;
                            padding: 6px 12px !important;
                            border-radius: 16px !important;
                            border: 1px solid rgba(102, 126, 234, 0.3) !important;
                            background: rgba(102, 126, 234, 0.1) !important;
                            color: #667eea !important;
                            cursor: pointer !important;
                            display: flex !important;
                            align-items: center !important;
                            gap: 4px !important;
                        "
                        onmouseover="if(!this.disabled) { this.style.transform='scale(1.05)'; this.style.background='rgba(102, 126, 234, 0.2)'; }"
                        onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(102, 126, 234, 0.1)';"
                    >
                        <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New Config
                    </button>
                    <button
                        wire:click="$set('message', 'Help me plan for retirement')"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                        style="
                            font-size: 12px !important;
                            padding: 6px 12px !important;
                            border-radius: 16px !important;
                            border: 1px solid rgba(16, 185, 129, 0.3) !important;
                            background: rgba(16, 185, 129, 0.1) !important;
                            color: #10b981 !important;
                            cursor: pointer !important;
                            display: flex !important;
                            align-items: center !important;
                            gap: 4px !important;
                        "
                        onmouseover="if(!this.disabled) { this.style.transform='scale(1.05)'; this.style.background='rgba(16, 185, 129, 0.2)'; }"
                        onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(16, 185, 129, 0.1)';"
                    >
                        <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5"/>
                        </svg>
                        Retirement
                    </button>
                    <button
                        wire:click="$set('message', 'Show me help')"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                        style="
                            font-size: 12px !important;
                            padding: 6px 12px !important;
                            border-radius: 16px !important;
                            border: 1px solid rgba(147, 51, 234, 0.3) !important;
                            background: rgba(147, 51, 234, 0.1) !important;
                            color: #9333ea !important;
                            cursor: pointer !important;
                            display: flex !important;
                            align-items: center !important;
                            gap: 4px !important;
                        "
                        onmouseover="if(!this.disabled) { this.style.transform='scale(1.05)'; this.style.background='rgba(147, 51, 234, 0.2)'; }"
                        onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(147, 51, 234, 0.1)';"
                    >
                        <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                        </svg>
                        Help
                    </button>
                </div>

                <div style="font-size: 11px !important; color: #9ca3af !important; text-align: center !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 4px !important;">
                    <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                    Your financial data is secure and private
                </div>
            </div>
        </div>
    @endif

    <!-- Embedded Styles for Animations -->
    <style>
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        @keyframes pulse-ring {
            0% {
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5), 0 0 0 0 rgba(102, 126, 234, 0.4);
            }
            50% {
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5), 0 0 0 8px rgba(102, 126, 234, 0);
            }
            100% {
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5), 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }

        @keyframes pulse-glow {
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(1.1);
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
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

        @keyframes shimmer {
            0% {
                left: -40%;
            }
            100% {
                left: 100%;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse-step {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 15px rgba(102, 126, 234, 0.6);
            }
            50% {
                transform: scale(1.1);
                box-shadow: 0 0 25px rgba(102, 126, 234, 0.8);
            }
        }

        @keyframes spin-ring {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- JavaScript for auto-scroll and status updates -->
    <script>
        // Auto-scroll to bottom when messages update
        document.addEventListener('livewire:updated', function () {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const container = document.getElementById('messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        });

        // Real-time status updates during AI processing
        let statusTimer = null;
        let elapsedSeconds = 0;
        const statusSteps = [
            { time: 0, text: '🔍 Analyzing your question...' },
            { time: 2, text: '🧠 Analyzing intent and context...' },
            { time: 4, text: '📊 Loading your financial data...' },
            { time: 6, text: '🤖 Sending request to AI...' },
            { time: 10, text: '✨ Processing AI response...' },
            { time: 15, text: '📝 Formatting response...' },
            { time: 20, text: '⏳ Almost done...' }
        ];

        function updateStatus() {
            const statusElements = document.querySelectorAll('[data-ai-status]');
            const elapsedElements = document.querySelectorAll('[data-ai-elapsed]');

            console.log('Updating status:', elapsedSeconds, 'elements found:', statusElements.length);

            // Find the appropriate status text based on elapsed time
            let currentStatus = statusSteps[0].text;
            for (let i = statusSteps.length - 1; i >= 0; i--) {
                if (elapsedSeconds >= statusSteps[i].time) {
                    currentStatus = statusSteps[i].text;
                    break;
                }
            }

            console.log('Current status:', currentStatus);

            // Update all status elements
            statusElements.forEach(el => {
                console.log('Updating element:', el);
                el.textContent = currentStatus;
            });
            elapsedElements.forEach(el => {
                el.textContent = elapsedSeconds + 's';
            });
        }

        function startStatusTimer() {
            console.log('Starting status timer');
            elapsedSeconds = 0;
            updateStatus();

            if (statusTimer) {
                clearInterval(statusTimer);
            }

            statusTimer = setInterval(() => {
                elapsedSeconds++;
                updateStatus();
            }, 1000);
        }

        function stopStatusTimer() {
            console.log('Stopping status timer');
            if (statusTimer) {
                clearInterval(statusTimer);
                statusTimer = null;
                elapsedSeconds = 0;
            }
        }

        // Use MutationObserver to detect when wire:loading elements appear
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded, setting up observer');

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Check if this is the status bar appearing
                            if (node.id === 'ai-status-bar' || node.querySelector && node.querySelector('#ai-status-bar')) {
                                console.log('Status bar appeared, starting timer');
                                startStatusTimer();
                            }
                        }
                    });

                    mutation.removedNodes.forEach((node) => {
                        if (node.nodeType === 1) {
                            // Check if status bar is being removed
                            if (node.id === 'ai-status-bar' || node.querySelector && node.querySelector('#ai-status-bar')) {
                                console.log('Status bar removed, stopping timer');
                                stopStatusTimer();
                            }
                        }
                    });
                });
            });

            // Observe the entire widget for changes
            const widget = document.querySelector('[wire\\:id]');
            if (widget) {
                console.log('Observing widget:', widget);
                observer.observe(widget, {
                    childList: true,
                    subtree: true
                });
            } else {
                console.error('Widget not found');
            }
        });
    </script>
</div>


