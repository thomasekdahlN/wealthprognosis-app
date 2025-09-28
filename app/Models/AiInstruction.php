<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInstruction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'system_prompt',
        'user_prompt_template',
        'model',
        'max_tokens',
        'temperature',
        'is_active',
        'sort_order',
        'team_id',
        'user_id',
        'created_by',
        'updated_by',
        'created_checksum',
        'updated_checksum',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the user that owns the AI instruction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that owns the AI instruction
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created this instruction
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this instruction
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active instructions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Replace placeholders in the user prompt template
     */
    public function buildUserPrompt(array $variables = []): string
    {
        $prompt = $this->user_prompt_template;

        foreach ($variables as $key => $value) {
            $prompt = str_replace("{{$key}}", $value, $prompt);
        }

        return $prompt;
    }

    /**
     * Get available OpenAI models
     */
    public static function getAvailableModels(): array
    {
        return [
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        ];
    }
}
