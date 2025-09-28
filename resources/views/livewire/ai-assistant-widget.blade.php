<div class="fixed bottom-6 right-6 z-[9999]" x-data="{ isTyping: @entangle('isLoading') }" style="position: fixed !important; bottom: 24px !important; right: 24px !important; z-index: 9999 !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Embedded Styles -->
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom scrollbar for messages container */
        #messages-container::-webkit-scrollbar {
            width: 6px;
        }

        #messages-container::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
            border-radius: 3px;
        }

        #messages-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 3px;
        }

        #messages-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }

        /* Markdown content styling for AI messages */
        .ai-message-content h1, .ai-message-content h2, .ai-message-content h3,
        .ai-message-content h4, .ai-message-content h5, .ai-message-content h6 {
            font-weight: bold !important;
            margin: 12px 0 8px 0 !important;
            color: #1f2937 !important;
        }

        .ai-message-content h1 { font-size: 18px !important; }
        .ai-message-content h2 { font-size: 16px !important; }
        .ai-message-content h3 { font-size: 15px !important; }
        .ai-message-content h4, .ai-message-content h5, .ai-message-content h6 { font-size: 14px !important; }

        .ai-message-content p {
            margin: 8px 0 !important;
            line-height: 1.5 !important;
        }

        .ai-message-content strong, .ai-message-content b {
            font-weight: bold !important;
            color: #1f2937 !important;
        }

        .ai-message-content em, .ai-message-content i {
            font-style: italic !important;
        }

        .ai-message-content ul, .ai-message-content ol {
            margin: 8px 0 !important;
            padding-left: 20px !important;
        }

        .ai-message-content li {
            margin: 4px 0 !important;
            line-height: 1.4 !important;
        }

        .ai-message-content code {
            background: rgba(102, 126, 234, 0.1) !important;
            color: #667eea !important;
            padding: 2px 4px !important;
            border-radius: 3px !important;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace !important;
            font-size: 13px !important;
        }

        .ai-message-content pre {
            background: #f8fafc !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 6px !important;
            padding: 12px !important;
            margin: 8px 0 !important;
            overflow-x: auto !important;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace !important;
            font-size: 13px !important;
            line-height: 1.4 !important;
        }

        .ai-message-content pre code {
            background: none !important;
            padding: 0 !important;
            color: #374151 !important;
        }

        .ai-message-content blockquote {
            border-left: 4px solid #667eea !important;
            margin: 8px 0 !important;
            padding: 8px 0 8px 16px !important;
            background: rgba(102, 126, 234, 0.05) !important;
            font-style: italic !important;
            color: #6b7280 !important;
        }

        .ai-message-content table {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 8px 0 !important;
            font-size: 13px !important;
        }

        .ai-message-content th, .ai-message-content td {
            border: 1px solid #e5e7eb !important;
            padding: 6px 8px !important;
            text-align: left !important;
        }

        .ai-message-content th {
            background: #f8fafc !important;
            font-weight: bold !important;
        }

        .ai-message-content a {
            color: #667eea !important;
            text-decoration: underline !important;
        }

        .ai-message-content a:hover {
            color: #5a67d8 !important;
        }
    </style>

    <!-- Toggle Button -->
    <div class="relative">
        @if(!$isOpen)
            <button
                wire:click="toggleWidget"
                class="group relative text-white rounded-full p-4 transition-all duration-300 hover:scale-110"
                style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
                    border: none !important;
                    cursor: pointer !important;
                "
                title="Open AI Financial Assistant"
            >
                <!-- Sparkle Icon -->
                <svg class="w-24 h-24 group-hover:rotate-12 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
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

                <!-- Animated notification dot -->
                <span class="absolute -top-1 -right-1 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%) !important; box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4) !important;">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background: #ff6b6b !important;"></span>
                    <span class="relative text-xs font-bold">AI</span>
                </span>

                <!-- Subtle glow effect -->
                <div class="absolute inset-0 rounded-full opacity-0 group-hover:opacity-30 transition-opacity duration-300 blur-xl" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;"></div>
            </button>
        @endif
    </div>

    <!-- Chat Widget -->
    @if($isOpen)
        <div style="
                position: absolute !important;
                bottom: 80px !important;
                right: 0 !important;
                background: #ffffff !important;
                border-radius: 16px !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                width: 800px !important;
                height: 1200px !important;
                display: flex !important;
                flex-direction: column !important;
                border: 1px solid rgba(0, 0, 0, 0.1) !important;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
             ">
            <!-- Header -->
            <div style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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

                <!-- Loading indicator -->
                @if($isLoading)
                    <div style="display: flex !important; justify-content: flex-start !important;">
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
                                animation: pulse 2s infinite !important;
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

                                <div style="display: flex !important; align-items: center !important; gap: 12px !important;">
                                    <div id="status-text" style="font-size: 14px !important; color: #6b7280 !important;">
                                        @if($currentStatus)
                                            {{ $currentStatus }}
                                        @else
                                            AI is thinking
                                        @endif
                                    </div>
                                    <div style="display: flex !important; gap: 4px !important;">
                                        <div style="width: 8px !important; height: 8px !important; border-radius: 50% !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; animation: bounce 1.4s infinite ease-in-out both !important;"></div>
                                        <div style="width: 8px !important; height: 8px !important; border-radius: 50% !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; animation: bounce 1.4s infinite ease-in-out both !important; animation-delay: -0.32s !important;"></div>
                                        <div style="width: 8px !important; height: 8px !important; border-radius: 50% !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; animation: bounce 1.4s infinite ease-in-out both !important; animation-delay: -0.16s !important;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
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
                            wire:keydown.enter="sendMessage"
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
                            @disabled($isLoading)
                        >
                        <button
                            type="submit"
                            wire:click="sendMessage"
                            @disabled($isLoading || empty(trim($message)))
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
                            onmouseover="this.style.transform='translateY(-50%) scale(1.05)';"
                            onmouseout="this.style.transform='translateY(-50%) scale(1)';"
                        >
                            @if($isLoading)
                                <svg style="width: 16px !important; height: 16px !important; animation: spin 1s linear infinite !important;" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                            @else
                                <svg style="width: 16px !important; height: 16px !important;" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            @endif
                        </button>
                    </div>
                </form>

                <!-- Quick Actions -->
                <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px !important; margin-bottom: 16px !important;">
                    <button
                        wire:click="$set('message', 'Create a new financial configuration')"
                        wire:click.prevent=""
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
                        onmouseover="this.style.transform='scale(1.05)'; this.style.background='rgba(102, 126, 234, 0.2)';"
                        onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(102, 126, 234, 0.1)';"
                        @disabled($isLoading)
                    >
                        <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New Config
                    </button>
                    <button
                        wire:click="$set('message', 'Help me plan for retirement')"
                        wire:click.prevent=""
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
                        onmouseover="this.style.transform='scale(1.05)'; this.style.background='rgba(16, 185, 129, 0.2)';"
                        onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(16, 185, 129, 0.1)';"
                        @disabled($isLoading)
                    >
                        <svg style="width: 12px !important; height: 12px !important;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5"/>
                        </svg>
                        Retirement
                    </button>
                    <button
                        wire:click="$set('message', 'Show me help')"
                        wire:click.prevent=""
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
                        onmouseover="this.style.transform='scale(1.05)'; this.style.background='rgba(147, 51, 234, 0.2)';"
                        onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(147, 51, 234, 0.1)';"
                        @disabled($isLoading)
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

    <!-- Embedded JavaScript -->
    <script>
        // Enhanced auto-scroll with smooth animation
        document.addEventListener('livewire:updated', function () {
            console.log('Livewire updated');
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
            console.log('AI Assistant Widget loaded');

            // Auto-scroll to bottom on initial load
            setTimeout(() => {
                const container = document.getElementById('messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        });

        // Debug form submissions
        document.addEventListener('livewire:init', function () {
            console.log('Livewire initialized');
        });

        // Listen for Livewire events
        document.addEventListener('livewire:request', function (event) {
            console.log('Livewire request:', event.detail);
        });

        document.addEventListener('livewire:response', function (event) {
            console.log('Livewire response:', event.detail);
        });

        // Listen for status updates
        document.addEventListener('livewire:dispatch', function (event) {
            if (event.detail.name === 'status-updated') {
                const statusElement = document.getElementById('status-text');
                if (statusElement && event.detail.status) {
                    statusElement.textContent = event.detail.status;
                    // Add a subtle animation to show the status changed
                    statusElement.style.opacity = '0.5';
                    setTimeout(() => {
                        statusElement.style.opacity = '1';
                    }, 100);
                }
            }
        });


    </script>
</div>


