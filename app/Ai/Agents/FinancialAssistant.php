<?php

namespace App\Ai\Agents;

use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

class FinancialAssistant implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    /**
     * Create a new FinancialAssistant agent.
     */
    public function __construct(
        protected User $user,
        protected string $contextData
    ) {
        $this->conversationUser = $user;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return 'You are an expert financial advisor AI assistant for the Wealth Prognosis application. '.
               "You have access to the user's complete financial configuration data in JSON format. ".
               "Use this data to provide accurate, personalized financial advice and analysis.\n\n".
               "USER'S FINANCIAL DATA:\n{$this->contextData}\n\n".
               "IMPORTANT INSTRUCTIONS:\n".
               "- Always base your analysis on the actual numbers in the data provided\n".
               "- Be constructive, clear, and direct\n".
               "- Focus on practical, actionable financial advice\n".
               "- Format your response using markdown\n".
               "- If asked about assets, list them specifically with their current values\n".
               "- Calculate net worth, income, expenses based on the actual data provided\n\n".
               'Respond in a helpful, knowledgeable manner as a personal financial advisor would.';
    }
}
