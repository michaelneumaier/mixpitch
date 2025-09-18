<?php

namespace App\Livewire;

use App\Models\FeedbackTemplate;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\PitchFileComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StructuredFeedbackForm extends Component
{
    public ?Pitch $pitch = null;

    public ?PitchFile $pitchFile = null;

    public ?FeedbackTemplate $template = null;

    public string $clientEmail = '';

    public bool $isClientUser = false;

    // Form state
    public array $responses = [];

    public ?int $selectedTemplateId = null;

    public array $availableTemplates = [];

    public bool $showTemplateSelector = true;

    public bool $isSubmitting = false;

    // Validation messages
    public array $validationErrors = [];

    protected $rules = [
        'selectedTemplateId' => 'required|integer|exists:feedback_templates,id',
        'responses' => 'required|array',
        'responses.*' => 'nullable',
    ];

    protected $messages = [
        'selectedTemplateId.required' => 'Please select a feedback template.',
        'responses.required' => 'Please provide your feedback responses.',
    ];

    public function mount(?Pitch $pitch = null, ?PitchFile $pitchFile = null, string $clientEmail = '')
    {
        $this->pitch = $pitch;
        $this->pitchFile = $pitchFile;
        $this->clientEmail = $clientEmail;
        $this->isClientUser = ! Auth::check() && ! empty($clientEmail);

        $this->loadAvailableTemplates();
    }

    public function loadAvailableTemplates()
    {
        if ($this->isClientUser) {
            // For clients, show only default templates
            $this->availableTemplates = FeedbackTemplate::default()
                ->active()
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'description' => $template->description,
                        'category' => $template->category_label,
                        'question_count' => count($template->questions ?? []),
                    ];
                })
                ->toArray();
        } else {
            // For authenticated users, show all available templates
            $this->availableTemplates = FeedbackTemplate::availableToUser(Auth::id())
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'description' => $template->description,
                        'category' => $template->category_label,
                        'question_count' => count($template->questions ?? []),
                        'is_default' => $template->is_default,
                    ];
                })
                ->toArray();
        }
    }

    public function selectTemplate(int $templateId)
    {
        $this->selectedTemplateId = $templateId;
        $this->loadTemplate();
    }

    protected function loadTemplate()
    {
        if (! $this->selectedTemplateId) {
            return;
        }

        if ($this->isClientUser) {
            $this->template = FeedbackTemplate::default()
                ->active()
                ->findOrFail($this->selectedTemplateId);
        } else {
            $this->template = FeedbackTemplate::availableToUser(Auth::id())
                ->findOrFail($this->selectedTemplateId);
        }

        // Initialize responses array
        $this->responses = [];
        foreach ($this->template->questions ?? [] as $question) {
            $this->responses[$question['id']] = $this->getDefaultValueForQuestion($question);
        }

        $this->showTemplateSelector = false;
        $this->resetValidation();
    }

    protected function getDefaultValueForQuestion(array $question): mixed
    {
        switch ($question['type']) {
            case FeedbackTemplate::TYPE_CHECKBOX:
                return [];
            case FeedbackTemplate::TYPE_RATING:
                return null;
            case FeedbackTemplate::TYPE_RANGE:
                return $question['min'] ?? 0;
            default:
                return '';
        }
    }

    public function backToTemplateSelector()
    {
        $this->showTemplateSelector = true;
        $this->template = null;
        $this->responses = [];
        $this->selectedTemplateId = null;
        $this->resetValidation();
    }

    public function submitFeedback()
    {
        $this->isSubmitting = true;
        $this->validateFeedback();

        if (! empty($this->validationErrors)) {
            $this->isSubmitting = false;

            return;
        }

        try {
            // Create the feedback comment
            $commentData = [
                'user_id' => $this->isClientUser ? null : Auth::id(),
                'pitch_file_id' => $this->pitchFile?->id,
                'comment' => $this->formatFeedbackContent(),
                'timestamp' => 0, // No specific timestamp for structured feedback
                'is_client_comment' => $this->isClientUser,
                'client_email' => $this->isClientUser ? $this->clientEmail : null,
            ];

            // If no specific pitch file, associate with the pitch's latest file
            if (! $this->pitchFile && $this->pitch) {
                $latestFile = $this->pitch->files()->latest()->first();
                if ($latestFile) {
                    $commentData['pitch_file_id'] = $latestFile->id;
                } else {
                    // No files available for this pitch - skip creating comment
                    $this->validationErrors = ['general' => 'No pitch files available for feedback.'];
                    $this->isSubmitting = false;

                    return;
                }
            }

            PitchFileComment::create($commentData);

            // Increment template usage
            $this->template->incrementUsage();

            // Emit success event
            $this->dispatch('feedbackSubmitted', [
                'message' => 'Feedback submitted successfully!',
                'template' => $this->template->name,
            ]);

            $this->reset(['responses', 'selectedTemplateId', 'template', 'validationErrors']);
            $this->showTemplateSelector = true;

        } catch (\Exception $e) {
            $this->validationErrors = ['general' => 'An error occurred while submitting feedback. Please try again.'];
        }

        $this->isSubmitting = false;
    }

    protected function validateFeedback()
    {
        $this->validationErrors = [];

        if (! $this->template) {
            $this->validationErrors['template'] = 'No template selected.';

            return;
        }

        foreach ($this->template->questions ?? [] as $question) {
            $questionId = $question['id'];
            $response = $this->responses[$questionId] ?? null;

            if ($question['required'] ?? false) {
                if ($this->isEmptyResponse($response, $question['type'])) {
                    $this->validationErrors[$questionId] = 'This field is required.';

                    continue;
                }
            }

            // Type-specific validation
            $this->validateQuestionResponse($question, $response, $questionId);
        }
    }

    protected function isEmptyResponse($response, string $type): bool
    {
        switch ($type) {
            case FeedbackTemplate::TYPE_CHECKBOX:
                return empty($response) || ! is_array($response);
            case FeedbackTemplate::TYPE_RATING:
            case FeedbackTemplate::TYPE_RANGE:
                return $response === null || $response === '';
            default:
                return empty(trim($response ?? ''));
        }
    }

    protected function validateQuestionResponse(array $question, $response, string $questionId)
    {
        switch ($question['type']) {
            case FeedbackTemplate::TYPE_RATING:
                $maxRating = $question['max_rating'] ?? 5;
                if ($response !== null && ($response < 1 || $response > $maxRating)) {
                    $this->validationErrors[$questionId] = "Rating must be between 1 and {$maxRating}.";
                }
                break;

            case FeedbackTemplate::TYPE_RANGE:
                $min = $question['min'] ?? 0;
                $max = $question['max'] ?? 100;
                if ($response !== null && ($response < $min || $response > $max)) {
                    $this->validationErrors[$questionId] = "Value must be between {$min} and {$max}.";
                }
                break;

            case FeedbackTemplate::TYPE_SELECT:
            case FeedbackTemplate::TYPE_RADIO:
                $options = $question['options'] ?? [];
                if ($response && ! in_array($response, $options)) {
                    $this->validationErrors[$questionId] = 'Invalid option selected.';
                }
                break;

            case FeedbackTemplate::TYPE_CHECKBOX:
                $options = $question['options'] ?? [];
                if (is_array($response)) {
                    foreach ($response as $value) {
                        if (! in_array($value, $options)) {
                            $this->validationErrors[$questionId] = 'Invalid option selected.';
                            break;
                        }
                    }
                }
                break;
        }
    }

    protected function formatFeedbackContent(): string
    {
        $content = "**Structured Feedback - {$this->template->name}**\n\n";

        foreach ($this->template->questions ?? [] as $question) {
            $questionId = $question['id'];
            $response = $this->responses[$questionId] ?? null;

            $content .= "**{$question['label']}**\n";
            $content .= $this->formatResponseForDisplay($response, $question)."\n\n";
        }

        return trim($content);
    }

    protected function formatResponseForDisplay($response, array $question): string
    {
        if ($this->isEmptyResponse($response, $question['type'])) {
            return '*No response*';
        }

        switch ($question['type']) {
            case FeedbackTemplate::TYPE_RATING:
                $maxRating = $question['max_rating'] ?? 5;

                return str_repeat('★', (int) $response).str_repeat('☆', $maxRating - (int) $response)." ({$response}/{$maxRating})";

            case FeedbackTemplate::TYPE_RANGE:
                $min = $question['min'] ?? 0;
                $max = $question['max'] ?? 100;

                return "{$response} (Range: {$min}-{$max})";

            case FeedbackTemplate::TYPE_CHECKBOX:
                return is_array($response) ? implode(', ', $response) : $response;

            default:
                return (string) $response;
        }
    }

    public function getHasSelectedTemplateProperty(): bool
    {
        return ! $this->showTemplateSelector && $this->template !== null;
    }

    public function render()
    {
        return view('livewire.structured-feedback-form');
    }
}
