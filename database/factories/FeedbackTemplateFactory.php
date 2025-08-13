<?php

namespace Database\Factories;

use App\Models\FeedbackTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedbackTemplate>
 */
class FeedbackTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement([
                FeedbackTemplate::CATEGORY_GENERAL,
                FeedbackTemplate::CATEGORY_MIXING,
                FeedbackTemplate::CATEGORY_MASTERING,
                FeedbackTemplate::CATEGORY_COMPOSITION,
                FeedbackTemplate::CATEGORY_ARRANGEMENT,
                FeedbackTemplate::CATEGORY_VOCAL,
                FeedbackTemplate::CATEGORY_PRODUCTION,
            ]),
            'questions' => $this->generateSampleQuestions(),
            'is_default' => false,
            'is_active' => true,
            'usage_count' => $this->faker->numberBetween(0, 50),
        ];
    }

    /**
     * Indicate that this is a system default template.
     */
    public function default(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => null,
                'is_default' => true,
                'usage_count' => $this->faker->numberBetween(100, 1000),
            ];
        });
    }

    /**
     * Indicate that this template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Create a template for a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(function (array $attributes) use ($category) {
            return [
                'category' => $category,
                'questions' => $this->generateCategorySpecificQuestions($category),
            ];
        });
    }

    /**
     * Create a minimal template with just basic questions.
     */
    public function minimal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'questions' => [
                    [
                        'id' => 'overall_rating',
                        'type' => FeedbackTemplate::TYPE_RATING,
                        'label' => 'Overall Rating',
                        'required' => true,
                        'max_rating' => 5,
                        'help_text' => '',
                    ],
                    [
                        'id' => 'comments',
                        'type' => FeedbackTemplate::TYPE_TEXTAREA,
                        'label' => 'Comments',
                        'required' => false,
                        'rows' => 3,
                        'help_text' => '',
                    ],
                ],
            ];
        });
    }

    /**
     * Generate sample questions for the template.
     */
    protected function generateSampleQuestions(): array
    {
        $questionTypes = [
            FeedbackTemplate::TYPE_TEXT,
            FeedbackTemplate::TYPE_TEXTAREA,
            FeedbackTemplate::TYPE_RATING,
            FeedbackTemplate::TYPE_SELECT,
        ];

        $questions = [];
        $questionCount = $this->faker->numberBetween(2, 5);

        for ($i = 0; $i < $questionCount; $i++) {
            $type = $this->faker->randomElement($questionTypes);
            $questions[] = $this->generateQuestionByType($type, $i + 1);
        }

        return $questions;
    }

    /**
     * Generate category-specific questions.
     */
    protected function generateCategorySpecificQuestions(string $category): array
    {
        switch ($category) {
            case FeedbackTemplate::CATEGORY_MIXING:
                return [
                    [
                        'id' => 'mix_balance',
                        'type' => FeedbackTemplate::TYPE_RATING,
                        'label' => 'Mix Balance',
                        'required' => true,
                        'max_rating' => 5,
                        'help_text' => 'Rate the overall balance of the mix',
                    ],
                    [
                        'id' => 'vocal_level',
                        'type' => FeedbackTemplate::TYPE_SELECT,
                        'label' => 'Vocal Level',
                        'required' => true,
                        'options' => ['Too quiet', 'Perfect', 'Too loud'],
                        'help_text' => 'How is the vocal level?',
                    ],
                ];

            case FeedbackTemplate::CATEGORY_MASTERING:
                return [
                    [
                        'id' => 'loudness',
                        'type' => FeedbackTemplate::TYPE_RANGE,
                        'label' => 'Loudness Level',
                        'required' => true,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'help_text' => 'Rate the loudness level',
                    ],
                    [
                        'id' => 'clarity',
                        'type' => FeedbackTemplate::TYPE_RATING,
                        'label' => 'Clarity',
                        'required' => true,
                        'max_rating' => 5,
                        'help_text' => 'Rate the overall clarity',
                    ],
                ];

            default:
                return $this->generateSampleQuestions();
        }
    }

    /**
     * Generate a question of a specific type.
     */
    protected function generateQuestionByType(string $type, int $index): array
    {
        $baseQuestion = [
            'id' => 'question_'.$index,
            'type' => $type,
            'label' => $this->faker->words(2, true).'?',
            'required' => $this->faker->boolean(),
            'help_text' => $this->faker->sentence(),
        ];

        switch ($type) {
            case FeedbackTemplate::TYPE_SELECT:
            case FeedbackTemplate::TYPE_RADIO:
            case FeedbackTemplate::TYPE_CHECKBOX:
                $baseQuestion['options'] = $this->faker->words(3);
                break;

            case FeedbackTemplate::TYPE_RATING:
                $baseQuestion['max_rating'] = $this->faker->randomElement([3, 5, 10]);
                break;

            case FeedbackTemplate::TYPE_RANGE:
                $baseQuestion['min'] = 0;
                $baseQuestion['max'] = $this->faker->randomElement([10, 50, 100]);
                $baseQuestion['step'] = 1;
                break;

            case FeedbackTemplate::TYPE_TEXTAREA:
                $baseQuestion['rows'] = $this->faker->numberBetween(3, 6);
                break;
        }

        return $baseQuestion;
    }
}
