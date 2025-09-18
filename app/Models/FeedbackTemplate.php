<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackTemplate extends Model
{
    use HasFactory;

    // Category Constants
    const CATEGORY_GENERAL = 'general';

    const CATEGORY_MIXING = 'mixing';

    const CATEGORY_MASTERING = 'mastering';

    const CATEGORY_COMPOSITION = 'composition';

    const CATEGORY_ARRANGEMENT = 'arrangement';

    const CATEGORY_VOCAL = 'vocal';

    const CATEGORY_PRODUCTION = 'production';

    // Question Types
    const TYPE_TEXT = 'text';

    const TYPE_TEXTAREA = 'textarea';

    const TYPE_SELECT = 'select';

    const TYPE_RADIO = 'radio';

    const TYPE_CHECKBOX = 'checkbox';

    const TYPE_RATING = 'rating';

    const TYPE_RANGE = 'range';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'questions',
        'category',
        'is_default',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'questions' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Get the user who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Increment the usage count for this template.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default/system templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for custom templates created by users.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_default', false)->whereNotNull('user_id');
    }

    /**
     * Scope for templates by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for templates available to a specific user.
     */
    public function scopeAvailableToUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_default', true)
                ->orWhere('user_id', $userId);
        })->active();
    }

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'General Feedback',
            self::CATEGORY_MIXING => 'Mixing',
            self::CATEGORY_MASTERING => 'Mastering',
            self::CATEGORY_COMPOSITION => 'Composition',
            self::CATEGORY_ARRANGEMENT => 'Arrangement',
            self::CATEGORY_VOCAL => 'Vocal Performance',
            self::CATEGORY_PRODUCTION => 'Production',
        ];
    }

    /**
     * Get all available question types.
     */
    public static function getQuestionTypes(): array
    {
        return [
            self::TYPE_TEXT => 'Short Text',
            self::TYPE_TEXTAREA => 'Long Text',
            self::TYPE_SELECT => 'Dropdown',
            self::TYPE_RADIO => 'Radio Buttons',
            self::TYPE_CHECKBOX => 'Checkboxes',
            self::TYPE_RATING => 'Star Rating',
            self::TYPE_RANGE => 'Range Slider',
        ];
    }

    /**
     * Get the category label for display.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Validate the questions structure.
     */
    public function validateQuestions(): array
    {
        $errors = [];

        if (! is_array($this->questions)) {
            $errors[] = 'Questions must be an array';

            return $errors;
        }

        foreach ($this->questions as $index => $question) {
            $questionErrors = $this->validateQuestion($question, $index);
            $errors = array_merge($errors, $questionErrors);
        }

        return $errors;
    }

    /**
     * Validate a single question structure.
     */
    protected function validateQuestion(array $question, int $index): array
    {
        $errors = [];
        $prefix = 'Question '.($index + 1);

        // Required fields
        if (empty($question['id'])) {
            $errors[] = "{$prefix}: ID is required";
        }

        if (empty($question['type'])) {
            $errors[] = "{$prefix}: Type is required";
        } elseif (! in_array($question['type'], array_keys(self::getQuestionTypes()))) {
            $errors[] = "{$prefix}: Invalid question type";
        }

        if (empty($question['label'])) {
            $errors[] = "{$prefix}: Label is required";
        }

        // Type-specific validation
        if (in_array($question['type'], [self::TYPE_SELECT, self::TYPE_RADIO, self::TYPE_CHECKBOX])) {
            if (empty($question['options']) || ! is_array($question['options'])) {
                $errors[] = "{$prefix}: Options are required for this question type";
            }
        }

        if ($question['type'] === self::TYPE_RATING) {
            if (! isset($question['max_rating']) || ! is_numeric($question['max_rating'])) {
                $errors[] = "{$prefix}: Max rating is required for rating questions";
            }
        }

        if ($question['type'] === self::TYPE_RANGE) {
            if (! isset($question['min']) || ! isset($question['max']) ||
                ! is_numeric($question['min']) || ! is_numeric($question['max'])) {
                $errors[] = "{$prefix}: Min and max values are required for range questions";
            }
        }

        return $errors;
    }

    /**
     * Create a default question structure.
     */
    public static function createDefaultQuestion(string $type, string $label): array
    {
        $question = [
            'id' => uniqid(),
            'type' => $type,
            'label' => $label,
            'required' => false,
            'help_text' => '',
        ];

        // Add type-specific fields
        switch ($type) {
            case self::TYPE_SELECT:
            case self::TYPE_RADIO:
            case self::TYPE_CHECKBOX:
                $question['options'] = [];
                break;

            case self::TYPE_RATING:
                $question['max_rating'] = 5;
                break;

            case self::TYPE_RANGE:
                $question['min'] = 0;
                $question['max'] = 100;
                $question['step'] = 1;
                break;

            case self::TYPE_TEXTAREA:
                $question['rows'] = 4;
                break;
        }

        return $question;
    }

    /**
     * Get default system templates.
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'General Audio Feedback',
                'description' => 'Comprehensive feedback template for any audio content',
                'category' => self::CATEGORY_GENERAL,
                'questions' => [
                    [
                        'id' => 'overall_rating',
                        'type' => self::TYPE_RATING,
                        'label' => 'Overall Rating',
                        'required' => true,
                        'max_rating' => 5,
                        'help_text' => 'Rate the overall quality of this audio',
                    ],
                    [
                        'id' => 'what_works',
                        'type' => self::TYPE_TEXTAREA,
                        'label' => 'What works well?',
                        'required' => false,
                        'rows' => 3,
                        'help_text' => 'Describe the positive aspects of this audio',
                    ],
                    [
                        'id' => 'improvements',
                        'type' => self::TYPE_TEXTAREA,
                        'label' => 'What could be improved?',
                        'required' => false,
                        'rows' => 3,
                        'help_text' => 'Suggest areas for improvement',
                    ],
                    [
                        'id' => 'urgency',
                        'type' => self::TYPE_SELECT,
                        'label' => 'Revision Priority',
                        'required' => true,
                        'options' => ['Low', 'Medium', 'High', 'Critical'],
                        'help_text' => 'How urgent are these revisions?',
                    ],
                ],
            ],
            [
                'name' => 'Mixing Feedback',
                'description' => 'Detailed feedback template for mixing reviews',
                'category' => self::CATEGORY_MIXING,
                'questions' => [
                    [
                        'id' => 'mix_balance',
                        'type' => self::TYPE_RATING,
                        'label' => 'Overall Mix Balance',
                        'required' => true,
                        'max_rating' => 5,
                        'help_text' => 'How well balanced are all the elements?',
                    ],
                    [
                        'id' => 'frequency_balance',
                        'type' => self::TYPE_RATING,
                        'label' => 'Frequency Balance',
                        'required' => true,
                        'max_rating' => 5,
                        'help_text' => 'How well balanced are the lows, mids, and highs?',
                    ],
                    [
                        'id' => 'vocal_level',
                        'type' => self::TYPE_SELECT,
                        'label' => 'Vocal Level',
                        'required' => true,
                        'options' => ['Too quiet', 'Perfect', 'Too loud'],
                        'help_text' => 'How is the vocal level in relation to the mix?',
                    ],
                    [
                        'id' => 'specific_feedback',
                        'type' => self::TYPE_TEXTAREA,
                        'label' => 'Specific Mix Notes',
                        'required' => false,
                        'rows' => 4,
                        'help_text' => 'Any specific feedback about instruments, effects, or mix elements',
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if this is a system template.
     */
    public function isSystemTemplate(): bool
    {
        return $this->is_default && $this->user_id === null;
    }

    /**
     * Check if this template belongs to a specific user.
     */
    public function belongsToUser(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
