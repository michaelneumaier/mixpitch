# Client Feedback Coach Implementation Plan

## Feature Overview

The Client Feedback Coach helps musicians and clients provide clear, actionable feedback to audio professionals. By analyzing feedback patterns and offering smart suggestions, this feature improves communication quality and reduces revision cycles, leading to better project outcomes and enhanced collaboration.

### Core Functionality
- **Smart Feedback Analysis**: Real-time analysis of feedback quality and clarity
- **Suggestion Engine**: Contextual tips for improving feedback specificity
- **Template Library**: Pre-built feedback templates for common scenarios
- **Communication Coaching**: Interactive guidance for effective audio feedback
- **Learning System**: Adaptive suggestions based on user feedback patterns
- **Integration with Pitch Workflow**: Seamless integration with existing comment systems

## Technical Architecture

### Database Schema

```sql
-- Feedback coaching templates and suggestions
CREATE TABLE feedback_coach_templates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    category ENUM('mix', 'master', 'composition', 'performance', 'technical') NOT NULL,
    subcategory VARCHAR(100) NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    example_feedback TEXT NOT NULL,
    improved_feedback TEXT NOT NULL,
    tags JSON DEFAULT '[]',
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_category (category, is_active),
    INDEX idx_difficulty (difficulty_level),
    INDEX idx_tags (tags),
    FULLTEXT KEY ft_content (title, description, example_feedback)
);

-- User feedback patterns and learning data
CREATE TABLE feedback_coach_user_profiles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    experience_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    preferred_feedback_style ENUM('brief', 'detailed', 'technical') DEFAULT 'detailed',
    feedback_quality_score DECIMAL(3,2) DEFAULT 0.50,
    total_feedback_given INT UNSIGNED DEFAULT 0,
    coaching_enabled BOOLEAN DEFAULT TRUE,
    show_suggestions BOOLEAN DEFAULT TRUE,
    preferred_categories JSON DEFAULT '[]',
    learning_preferences JSON DEFAULT '{}',
    last_coaching_interaction TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_profile (user_id),
    INDEX idx_quality_score (feedback_quality_score),
    INDEX idx_coaching_enabled (coaching_enabled)
);

-- Feedback analysis and coaching sessions
CREATE TABLE feedback_coach_sessions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    pitch_id BIGINT UNSIGNED NULL,
    original_feedback TEXT NOT NULL,
    analyzed_feedback JSON NOT NULL,
    suggestions JSON DEFAULT '[]',
    quality_score DECIMAL(3,2) NOT NULL,
    coaching_shown BOOLEAN DEFAULT FALSE,
    feedback_accepted BOOLEAN NULL,
    improvement_applied BOOLEAN NULL,
    final_feedback TEXT NULL,
    session_duration_seconds INT UNSIGNED NULL,
    template_used_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE SET NULL,
    FOREIGN KEY (template_used_id) REFERENCES feedback_coach_templates(id) ON DELETE SET NULL,
    INDEX idx_user_project (user_id, project_id),
    INDEX idx_quality_score (quality_score),
    INDEX idx_coaching_shown (coaching_shown),
    FULLTEXT KEY ft_feedback (original_feedback, final_feedback)
);

-- Feedback improvement suggestions and tips
CREATE TABLE feedback_coach_suggestions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    type ENUM('specificity', 'timing', 'technical', 'tone', 'actionable') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    example_before TEXT NOT NULL,
    example_after TEXT NOT NULL,
    keywords JSON DEFAULT '[]',
    trigger_patterns JSON DEFAULT '[]',
    improvement_impact ENUM('low', 'medium', 'high') DEFAULT 'medium',
    learning_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT UNSIGNED DEFAULT 0,
    success_rate DECIMAL(3,2) DEFAULT 0.50,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_type_active (type, is_active),
    INDEX idx_learning_level (learning_level),
    INDEX idx_success_rate (success_rate),
    FULLTEXT KEY ft_content (title, description, example_before, example_after)
);

-- User interactions with coaching system
CREATE TABLE feedback_coach_interactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    interaction_type ENUM('suggestion_shown', 'suggestion_accepted', 'suggestion_dismissed', 'template_used', 'feedback_improved') NOT NULL,
    suggestion_id BIGINT UNSIGNED NULL,
    template_id BIGINT UNSIGNED NULL,
    interaction_data JSON DEFAULT '{}',
    user_rating TINYINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (session_id) REFERENCES feedback_coach_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (suggestion_id) REFERENCES feedback_coach_suggestions(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES feedback_coach_templates(id) ON DELETE SET NULL,
    INDEX idx_session_type (session_id, interaction_type),
    INDEX idx_user_interactions (user_id, created_at)
);
```

### Service Architecture

#### FeedbackCoachService
```php
<?php

namespace App\Services;

use App\Models\FeedbackCoachSession;
use App\Models\FeedbackCoachUserProfile;
use App\Models\FeedbackCoachTemplate;
use App\Models\FeedbackCoachSuggestion;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Support\Facades\Cache;

class FeedbackCoachService
{
    public function analyzeFeedback(
        User $user,
        Project $project,
        string $feedback,
        ?Pitch $pitch = null
    ): array {
        $startTime = microtime(true);
        
        // Get or create user profile for personalized coaching
        $userProfile = $this->getUserProfile($user);
        
        // Analyze feedback quality and extract insights
        $analysis = $this->performFeedbackAnalysis($feedback, $userProfile);
        
        // Generate personalized suggestions
        $suggestions = $this->generateSuggestions($feedback, $analysis, $userProfile);
        
        // Create coaching session record
        $session = FeedbackCoachSession::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'pitch_id' => $pitch?->id,
            'original_feedback' => $feedback,
            'analyzed_feedback' => $analysis,
            'suggestions' => $suggestions,
            'quality_score' => $analysis['overall_score'],
            'session_duration_seconds' => round((microtime(true) - $startTime) * 1000)
        ]);

        return [
            'session_id' => $session->id,
            'analysis' => $analysis,
            'suggestions' => $suggestions,
            'show_coaching' => $this->shouldShowCoaching($userProfile, $analysis),
            'user_level' => $userProfile->experience_level
        ];
    }

    public function getRecommendedTemplates(
        User $user,
        string $category = null,
        string $feedback = null
    ): array {
        $userProfile = $this->getUserProfile($user);
        
        $query = FeedbackCoachTemplate::where('is_active', true)
            ->where('difficulty_level', '<=', $userProfile->experience_level);

        if ($category) {
            $query->where('category', $category);
        }

        // If feedback provided, find relevant templates using full-text search
        if ($feedback) {
            $keywords = $this->extractKeywords($feedback);
            if (!empty($keywords)) {
                $query->whereRaw(
                    'MATCH(title, description, example_feedback) AGAINST(? IN NATURAL LANGUAGE MODE)',
                    [implode(' ', $keywords)]
                );
            }
        }

        $templates = $query->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get();

        return $templates->map(function ($template) {
            return [
                'id' => $template->id,
                'title' => $template->title,
                'description' => $template->description,
                'category' => $template->category,
                'example' => $template->example_feedback,
                'improved_version' => $template->improved_feedback,
                'tags' => $template->tags
            ];
        })->toArray();
    }

    public function improveFeedback(
        int $sessionId,
        array $appliedSuggestions,
        string $finalFeedback
    ): array {
        $session = FeedbackCoachSession::findOrFail($sessionId);
        
        // Update session with improvements
        $session->update([
            'improvement_applied' => true,
            'final_feedback' => $finalFeedback,
            'feedback_accepted' => true
        ]);

        // Track improvement interactions
        foreach ($appliedSuggestions as $suggestionId) {
            $this->recordInteraction($session, 'suggestion_accepted', [
                'suggestion_id' => $suggestionId
            ]);
        }

        // Update user profile quality score
        $this->updateUserQualityScore($session->user, $session);

        // Re-analyze improved feedback to measure improvement
        $improvedAnalysis = $this->performFeedbackAnalysis($finalFeedback, $session->user->feedbackCoachProfile);
        
        return [
            'improvement_score' => $improvedAnalysis['overall_score'] - $session->quality_score,
            'improved_analysis' => $improvedAnalysis,
            'coaching_effectiveness' => $this->calculateCoachingEffectiveness($session, $improvedAnalysis)
        ];
    }

    public function dismissCoaching(int $sessionId, string $reason = null): void
    {
        $session = FeedbackCoachSession::findOrFail($sessionId);
        
        $session->update([
            'coaching_shown' => true,
            'feedback_accepted' => false
        ]);

        $this->recordInteraction($session, 'suggestion_dismissed', [
            'reason' => $reason
        ]);

        // Update user preferences if consistently dismissing
        $this->updateDismissalPattern($session->user);
    }

    private function performFeedbackAnalysis(string $feedback, FeedbackCoachUserProfile $userProfile): array
    {
        $analysis = [
            'word_count' => str_word_count($feedback),
            'character_count' => strlen($feedback),
            'sentences' => substr_count($feedback, '.') + substr_count($feedback, '!') + substr_count($feedback, '?'),
            'questions' => substr_count($feedback, '?'),
            'exclamations' => substr_count($feedback, '!'),
            'technical_terms' => $this->countTechnicalTerms($feedback),
            'time_references' => $this->extractTimeReferences($feedback),
            'specific_elements' => $this->extractSpecificElements($feedback),
            'tone_analysis' => $this->analyzeTone($feedback),
            'actionability_score' => $this->calculateActionabilityScore($feedback),
            'specificity_score' => $this->calculateSpecificityScore($feedback),
            'clarity_score' => $this->calculateClarityScore($feedback),
            'overall_score' => 0
        ];

        // Calculate overall quality score
        $analysis['overall_score'] = $this->calculateOverallScore($analysis, $userProfile);

        return $analysis;
    }

    private function generateSuggestions(string $feedback, array $analysis, FeedbackCoachUserProfile $userProfile): array
    {
        $suggestions = [];

        // Low specificity suggestions
        if ($analysis['specificity_score'] < 0.6) {
            $suggestions[] = $this->getSuggestion('specificity', $userProfile->experience_level);
        }

        // Lack of time references
        if (empty($analysis['time_references']) && $analysis['word_count'] > 10) {
            $suggestions[] = $this->getSuggestion('timing', $userProfile->experience_level);
        }

        // Low actionability
        if ($analysis['actionability_score'] < 0.5) {
            $suggestions[] = $this->getSuggestion('actionable', $userProfile->experience_level);
        }

        // Tone improvement
        if ($analysis['tone_analysis']['negativity_score'] > 0.7) {
            $suggestions[] = $this->getSuggestion('tone', $userProfile->experience_level);
        }

        // Technical detail suggestions for advanced users
        if ($userProfile->experience_level === 'advanced' && $analysis['technical_terms'] < 2) {
            $suggestions[] = $this->getSuggestion('technical', 'advanced');
        }

        return array_filter($suggestions);
    }

    private function getSuggestion(string $type, string $level): ?array
    {
        $suggestion = FeedbackCoachSuggestion::where('type', $type)
            ->where('learning_level', '<=', $level)
            ->where('is_active', true)
            ->orderBy('success_rate', 'desc')
            ->first();

        if (!$suggestion) {
            return null;
        }

        $suggestion->increment('usage_count');

        return [
            'id' => $suggestion->id,
            'type' => $suggestion->type,
            'title' => $suggestion->title,
            'description' => $suggestion->description,
            'example_before' => $suggestion->example_before,
            'example_after' => $suggestion->example_after,
            'impact' => $suggestion->improvement_impact
        ];
    }

    private function extractTimeReferences(string $feedback): array
    {
        $timePatterns = [
            '/\b(\d{1,2}):(\d{2})\b/',           // MM:SS format
            '/\b(\d{1,2}):(\d{2}):(\d{2})\b/',   // HH:MM:SS format
            '/\b(\d+)\s*(second|minute|hour)s?\b/i',
            '/\bat\s+(\d{1,2}:\d{2})\b/i',
            '/\b(beginning|start|end|outro|intro|bridge|chorus|verse)\b/i'
        ];

        $timeReferences = [];
        foreach ($timePatterns as $pattern) {
            if (preg_match_all($pattern, $feedback, $matches)) {
                $timeReferences = array_merge($timeReferences, $matches[0]);
            }
        }

        return array_unique($timeReferences);
    }

    private function extractSpecificElements(string $feedback): array
    {
        $elementPatterns = [
            '/\b(bass|kick|snare|hi-hat|cymbal|guitar|piano|vocal|drums|lead|rhythm)\b/i',
            '/\b(reverb|delay|compression|EQ|equalizer|filter|distortion|chorus|flanger)\b/i',
            '/\b(mix|master|volume|level|pan|stereo|mono|frequency|Hz|kHz|dB)\b/i',
            '/\b(verse|chorus|bridge|intro|outro|breakdown|drop|build)\b/i'
        ];

        $elements = [];
        foreach ($elementPatterns as $pattern) {
            if (preg_match_all($pattern, $feedback, $matches)) {
                $elements = array_merge($elements, $matches[0]);
            }
        }

        return array_unique($elements);
    }

    private function countTechnicalTerms(string $feedback): int
    {
        $technicalTerms = [
            'compression', 'compressor', 'limiter', 'eq', 'equalizer', 'frequency',
            'reverb', 'delay', 'chorus', 'flanger', 'distortion', 'saturation',
            'db', 'hz', 'khz', 'stereo', 'mono', 'pan', 'automation',
            'sidechain', 'gate', 'threshold', 'ratio', 'attack', 'release'
        ];

        $count = 0;
        foreach ($technicalTerms as $term) {
            $count += substr_count(strtolower($feedback), $term);
        }

        return $count;
    }

    private function analyzeTone(string $feedback): array
    {
        $positiveWords = ['good', 'great', 'love', 'perfect', 'amazing', 'excellent', 'nice', 'smooth'];
        $negativeWords = ['bad', 'terrible', 'hate', 'awful', 'horrible', 'wrong', 'poor', 'harsh'];
        $constructiveWords = ['improve', 'adjust', 'modify', 'enhance', 'refine', 'polish'];

        $lowerFeedback = strtolower($feedback);
        
        $positiveCount = 0;
        $negativeCount = 0;
        $constructiveCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($lowerFeedback, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($lowerFeedback, $word);
        }

        foreach ($constructiveWords as $word) {
            $constructiveCount += substr_count($lowerFeedback, $word);
        }

        $totalEmotionalWords = $positiveCount + $negativeCount + $constructiveCount;

        return [
            'positive_score' => $totalEmotionalWords > 0 ? $positiveCount / $totalEmotionalWords : 0,
            'negativity_score' => $totalEmotionalWords > 0 ? $negativeCount / $totalEmotionalWords : 0,
            'constructive_score' => $totalEmotionalWords > 0 ? $constructiveCount / $totalEmotionalWords : 0,
            'overall_tone' => $this->determineTone($positiveCount, $negativeCount, $constructiveCount)
        ];
    }

    private function calculateActionabilityScore(string $feedback): float
    {
        $actionWords = ['increase', 'decrease', 'add', 'remove', 'adjust', 'change', 'fix', 'boost', 'cut', 'try'];
        $questionWords = ['what', 'how', 'why', 'when', 'where', 'could', 'would', 'should'];
        
        $actionCount = 0;
        $questionCount = 0;
        $wordCount = str_word_count($feedback);

        foreach ($actionWords as $word) {
            $actionCount += substr_count(strtolower($feedback), $word);
        }

        foreach ($questionWords as $word) {
            $questionCount += substr_count(strtolower($feedback), $word);
        }

        // Questions reduce actionability unless they're specific
        $adjustedActionCount = $actionCount - ($questionCount * 0.5);
        
        return $wordCount > 0 ? min(1.0, max(0.0, $adjustedActionCount / ($wordCount * 0.1))) : 0;
    }

    private function calculateSpecificityScore(string $feedback): float
    {
        $specificElements = $this->extractSpecificElements($feedback);
        $timeReferences = $this->extractTimeReferences($feedback);
        $wordCount = str_word_count($feedback);

        $specificityFactors = [
            count($specificElements) * 0.1,
            count($timeReferences) * 0.15,
            $this->countTechnicalTerms($feedback) * 0.05
        ];

        $baseScore = array_sum($specificityFactors);
        
        // Penalize vague language
        $vagueWords = ['thing', 'stuff', 'something', 'somehow', 'maybe', 'kind of', 'sort of'];
        $vagueCount = 0;
        foreach ($vagueWords as $word) {
            $vagueCount += substr_count(strtolower($feedback), $word);
        }

        $penalty = $wordCount > 0 ? ($vagueCount / $wordCount) * 0.3 : 0;
        
        return min(1.0, max(0.0, $baseScore - $penalty));
    }

    private function calculateClarityScore(string $feedback): float
    {
        $wordCount = str_word_count($feedback);
        $sentenceCount = max(1, substr_count($feedback, '.') + substr_count($feedback, '!') + substr_count($feedback, '?'));
        
        // Average words per sentence (ideal range: 15-20)
        $averageWordsPerSentence = $wordCount / $sentenceCount;
        
        $clarityFactors = [
            // Sentence length factor (penalize very long or very short sentences)
            $this->getClarityFactor($averageWordsPerSentence, 15, 20, 0.3),
            // Overall length factor (too short or too long reduces clarity)
            $this->getClarityFactor($wordCount, 10, 100, 0.2),
            // Technical term balance
            $this->getTechnicalBalance($feedback, $wordCount) * 0.2,
            // Structure clarity (questions, statements balance)
            $this->getStructureClarity($feedback) * 0.3
        ];

        return min(1.0, max(0.0, array_sum($clarityFactors)));
    }

    private function calculateOverallScore(array $analysis, FeedbackCoachUserProfile $userProfile): float
    {
        $weights = [
            'specificity' => 0.3,
            'actionability' => 0.25,
            'clarity' => 0.25,
            'tone' => 0.2
        ];

        $toneScore = 1 - $analysis['tone_analysis']['negativity_score'] + 
                     $analysis['tone_analysis']['constructive_score'];

        $weightedScore = (
            $analysis['specificity_score'] * $weights['specificity'] +
            $analysis['actionability_score'] * $weights['actionability'] +
            $analysis['clarity_score'] * $weights['clarity'] +
            min(1.0, $toneScore) * $weights['tone']
        );

        // Adjust based on user experience level
        if ($userProfile->experience_level === 'beginner' && $weightedScore > 0.5) {
            $weightedScore *= 1.1; // Encourage beginners
        }

        return min(1.0, max(0.0, $weightedScore));
    }

    private function shouldShowCoaching(FeedbackCoachUserProfile $userProfile, array $analysis): bool
    {
        if (!$userProfile->coaching_enabled || !$userProfile->show_suggestions) {
            return false;
        }

        // Show coaching if quality score is below threshold
        $qualityThreshold = match ($userProfile->experience_level) {
            'beginner' => 0.4,
            'intermediate' => 0.6,
            'advanced' => 0.8
        };

        return $analysis['overall_score'] < $qualityThreshold;
    }

    private function getUserProfile(User $user): FeedbackCoachUserProfile
    {
        return FeedbackCoachUserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'experience_level' => 'beginner',
                'preferred_feedback_style' => 'detailed',
                'feedback_quality_score' => 0.5,
                'coaching_enabled' => true,
                'show_suggestions' => true
            ]
        );
    }

    private function recordInteraction(FeedbackCoachSession $session, string $type, array $data = []): void
    {
        $session->interactions()->create([
            'user_id' => $session->user_id,
            'interaction_type' => $type,
            'suggestion_id' => $data['suggestion_id'] ?? null,
            'template_id' => $data['template_id'] ?? null,
            'interaction_data' => $data
        ]);
    }

    private function updateUserQualityScore(User $user, FeedbackCoachSession $session): void
    {
        $profile = $user->feedbackCoachProfile;
        
        // Calculate running average of quality scores
        $totalFeedback = $profile->total_feedback_given + 1;
        $newAverageScore = (
            ($profile->feedback_quality_score * $profile->total_feedback_given) + 
            $session->quality_score
        ) / $totalFeedback;

        $profile->update([
            'feedback_quality_score' => $newAverageScore,
            'total_feedback_given' => $totalFeedback,
            'last_coaching_interaction' => now()
        ]);
    }

    private function extractKeywords(string $text): array
    {
        // Simple keyword extraction - could be enhanced with NLP libraries
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = str_word_count(strtolower($text), 1);
        
        return array_diff($words, $stopWords);
    }

    // Helper methods for clarity calculation
    private function getClarityFactor(float $value, float $ideal_min, float $ideal_max, float $weight): float
    {
        if ($value >= $ideal_min && $value <= $ideal_max) {
            return $weight;
        }
        
        $distance = min(abs($value - $ideal_min), abs($value - $ideal_max));
        $penalty = min($weight, $distance / $ideal_max * $weight);
        
        return max(0, $weight - $penalty);
    }

    private function getTechnicalBalance(string $feedback, int $wordCount): float
    {
        $technicalTerms = $this->countTechnicalTerms($feedback);
        $ratio = $wordCount > 0 ? $technicalTerms / $wordCount : 0;
        
        // Ideal technical term ratio: 5-15%
        if ($ratio >= 0.05 && $ratio <= 0.15) {
            return 1.0;
        }
        
        return max(0, 1 - abs($ratio - 0.1) * 5);
    }

    private function getStructureClarity(string $feedback): float
    {
        $sentences = max(1, substr_count($feedback, '.') + substr_count($feedback, '!') + substr_count($feedback, '?'));
        $questions = substr_count($feedback, '?');
        $statements = $sentences - $questions;
        
        // Good balance: mostly statements with some questions
        $questionRatio = $questions / $sentences;
        
        if ($questionRatio <= 0.3) {
            return 1.0; // Good balance
        }
        
        return max(0.2, 1 - ($questionRatio - 0.3) * 2);
    }

    private function determineTone(int $positive, int $negative, int $constructive): string
    {
        if ($constructive > 0 && $negative <= $positive) {
            return 'constructive';
        } elseif ($positive > $negative) {
            return 'positive';
        } elseif ($negative > $positive) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }

    private function calculateCoachingEffectiveness(FeedbackCoachSession $session, array $improvedAnalysis): float
    {
        $originalScore = $session->quality_score;
        $improvedScore = $improvedAnalysis['overall_score'];
        
        return max(0, min(1, ($improvedScore - $originalScore) / (1 - $originalScore)));
    }

    private function updateDismissalPattern(User $user): void
    {
        $profile = $user->feedbackCoachProfile;
        $recentDismissals = $user->feedbackCoachSessions()
            ->where('coaching_shown', true)
            ->where('feedback_accepted', false)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // If user dismisses coaching frequently, reduce frequency
        if ($recentDismissals >= 3) {
            $preferences = $profile->learning_preferences;
            $preferences['coaching_frequency'] = 'reduced';
            $profile->update(['learning_preferences' => $preferences]);
        }
    }
}
```

## UI Implementation

### Feedback Coach Component
```php
<?php

namespace App\Livewire\FeedbackCoach;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\FeedbackCoachTemplate;
use App\Services\FeedbackCoachService;
use Livewire\Component;

class FeedbackCoach extends Component
{
    public Project $project;
    public ?Pitch $pitch = null;
    public string $feedback = '';
    public array $analysis = [];
    public array $suggestions = [];
    public bool $showCoaching = false;
    public bool $coachingEnabled = true;
    public int $sessionId = 0;
    public array $appliedSuggestions = [];
    public bool $showTemplates = false;
    public array $templates = [];
    public string $selectedTemplate = '';

    protected $rules = [
        'feedback' => 'required|string|min:10|max:2000'
    ];

    public function mount(Project $project, ?Pitch $pitch = null)
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->coachingEnabled = auth()->user()->feedbackCoachProfile?->coaching_enabled ?? true;
    }

    public function analyzeFeedback(FeedbackCoachService $coachService)
    {
        if (strlen($this->feedback) < 10) {
            return; // Don't analyze very short feedback
        }

        $result = $coachService->analyzeFeedback(
            auth()->user(),
            $this->project,
            $this->feedback,
            $this->pitch
        );

        $this->sessionId = $result['session_id'];
        $this->analysis = $result['analysis'];
        $this->suggestions = $result['suggestions'];
        $this->showCoaching = $result['show_coaching'] && $this->coachingEnabled;

        $this->dispatch('feedback-analyzed', [
            'score' => $this->analysis['overall_score'],
            'showCoaching' => $this->showCoaching
        ]);
    }

    public function applySuggestion(int $suggestionId, FeedbackCoachService $coachService)
    {
        $suggestion = collect($this->suggestions)->firstWhere('id', $suggestionId);
        
        if ($suggestion) {
            // Add suggestion to applied list
            $this->appliedSuggestions[] = $suggestionId;
            
            // Apply the suggestion to the feedback text
            $this->feedback = $this->applyImprovementToText($this->feedback, $suggestion);
            
            // Re-analyze with improvements
            $this->analyzeFeedback($coachService);
            
            $this->dispatch('suggestion-applied', [
                'type' => $suggestion['type'],
                'improvement' => $suggestion['title']
            ]);
        }
    }

    public function loadTemplates(FeedbackCoachService $coachService)
    {
        $this->templates = $coachService->getRecommendedTemplates(
            auth()->user(),
            $this->project->workflow_type,
            $this->feedback
        );
        
        $this->showTemplates = true;
    }

    public function useTemplate(int $templateId)
    {
        $template = collect($this->templates)->firstWhere('id', $templateId);
        
        if ($template) {
            $this->feedback = $template['improved_version'];
            $this->selectedTemplate = $template['title'];
            $this->showTemplates = false;
            
            // Re-analyze with template
            $this->analyzeFeedback(app(FeedbackCoachService::class));
            
            $this->dispatch('template-applied', [
                'template' => $template['title']
            ]);
        }
    }

    public function dismissCoaching(FeedbackCoachService $coachService)
    {
        if ($this->sessionId) {
            $coachService->dismissCoaching($this->sessionId, 'user_dismissed');
        }
        
        $this->showCoaching = false;
        
        $this->dispatch('coaching-dismissed');
    }

    public function submitFeedback()
    {
        $this->validate();

        // If improvements were applied, record them
        if (!empty($this->appliedSuggestions) && $this->sessionId) {
            $coachService = app(FeedbackCoachService::class);
            $coachService->improveFeedback(
                $this->sessionId,
                $this->appliedSuggestions,
                $this->feedback
            );
        }

        // Emit event for parent component to handle actual feedback submission
        $this->dispatch('feedback-ready', [
            'feedback' => $this->feedback,
            'analysis' => $this->analysis,
            'improvements_applied' => !empty($this->appliedSuggestions)
        ]);
    }

    public function toggleCoaching()
    {
        $this->coachingEnabled = !$this->coachingEnabled;
        
        // Update user preference
        auth()->user()->feedbackCoachProfile()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['coaching_enabled' => $this->coachingEnabled]
        );

        if (!$this->coachingEnabled) {
            $this->showCoaching = false;
        }
    }

    private function applyImprovementToText(string $text, array $suggestion): string
    {
        // Simple text improvement based on suggestion type
        // In a real implementation, this would be more sophisticated
        
        switch ($suggestion['type']) {
            case 'specificity':
                if (!str_contains(strtolower($text), 'at ') && !preg_match('/\d+:\d+/', $text)) {
                    $text .= " (Please specify the exact time or element you're referring to)";
                }
                break;
                
            case 'timing':
                if (!preg_match('/\d+:\d+/', $text)) {
                    $text = "At [TIMESTAMP]: " . $text;
                }
                break;
                
            case 'actionable':
                if (!str_contains(strtolower($text), 'please') && !str_contains(strtolower($text), 'try')) {
                    $text = "Please try: " . $text;
                }
                break;
                
            case 'tone':
                // Replace harsh words with constructive alternatives
                $improvements = [
                    'bad' => 'could be improved',
                    'terrible' => 'needs adjustment',
                    'wrong' => 'different from what I expected',
                    'hate' => 'would prefer'
                ];
                
                foreach ($improvements as $harsh => $gentle) {
                    $text = str_ireplace($harsh, $gentle, $text);
                }
                break;
        }
        
        return $text;
    }

    public function render()
    {
        return view('livewire.feedback-coach.coach');
    }
}
```

### Blade Template
```blade
<div class="space-y-6">
    {{-- Feedback Textarea with Coach Integration --}}
    <div class="relative">
        <flux:field>
            <flux:label>Your Feedback</flux:label>
            <flux:textarea 
                wire:model.live.debounce.500ms="feedback"
                wire:key="feedback-textarea"
                placeholder="Share your thoughts on this pitch..."
                rows="6"
                class="pr-12"
            />
            <flux:error name="feedback" />
            
            {{-- Coaching Toggle --}}
            <div class="flex items-center justify-between mt-2">
                <flux:description>
                    {{-- Quality Score Indicator --}}
                    @if(!empty($analysis))
                        <div class="flex items-center space-x-2">
                            <span class="text-sm">Feedback Quality:</span>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <flux:icon 
                                        icon="star" 
                                        class="w-4 h-4 {{ $i <= ($analysis['overall_score'] * 5) ? 'text-yellow-400' : 'text-gray-300' }}"
                                    />
                                @endfor
                                <span class="ml-1 text-sm text-gray-600">
                                    {{ number_format($analysis['overall_score'] * 100) }}%
                                </span>
                            </div>
                        </div>
                    @endif
                </flux:description>
                
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model.live="coachingEnabled" 
                        wire:change="toggleCoaching"
                        class="mr-2"
                    >
                    <span class="text-sm text-gray-600">Enable Feedback Coach</span>
                </label>
            </div>
        </flux:field>

        {{-- AI Analysis Indicator --}}
        @if(strlen($feedback) > 10)
            <div 
                class="absolute top-2 right-2"
                wire:loading.remove
                wire:target="analyzeFeedback"
            >
                <flux:icon 
                    icon="sparkles" 
                    class="w-5 h-5 {{ !empty($analysis) ? 'text-indigo-500' : 'text-gray-400' }}"
                />
            </div>
            
            <div 
                class="absolute top-2 right-2"
                wire:loading
                wire:target="analyzeFeedback"
            >
                <flux:icon 
                    icon="arrow-path" 
                    class="w-5 h-5 text-indigo-500 animate-spin"
                />
            </div>
        @endif
    </div>

    {{-- Coaching Panel --}}
    @if($showCoaching && !empty($suggestions))
        <flux:card class="border-l-4 border-l-indigo-500 bg-indigo-50 dark:bg-indigo-900/20">
            <flux:card.header class="pb-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <flux:icon icon="academic-cap" class="w-5 h-5 text-indigo-600" />
                        <flux:heading size="sm">Feedback Coach</flux:heading>
                    </div>
                    
                    <flux:button 
                        wire:click="dismissCoaching"
                        variant="ghost" 
                        size="xs"
                    >
                        <flux:icon icon="x-mark" class="w-4 h-4" />
                    </flux:button>
                </div>
            </flux:card.header>
            
            <flux:card.body>
                <div class="space-y-4">
                    <flux:text variant="muted">
                        Here are some suggestions to make your feedback more helpful:
                    </flux:text>

                    {{-- Suggestions --}}
                    <div class="space-y-3">
                        @foreach($suggestions as $suggestion)
                            <div class="border rounded-lg p-3 bg-white dark:bg-slate-800">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="font-medium text-sm">{{ $suggestion['title'] }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $suggestion['description'] }}
                                        </p>
                                    </div>
                                    
                                    <flux:badge 
                                        variant="{{ $suggestion['impact'] === 'high' ? 'danger' : ($suggestion['impact'] === 'medium' ? 'warning' : 'outline') }}"
                                        size="sm"
                                    >
                                        {{ ucfirst($suggestion['impact']) }} Impact
                                    </flux:badge>
                                </div>

                                {{-- Before/After Example --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <flux:text size="xs" class="font-medium text-red-600 mb-1">Before:</flux:text>
                                        <div class="text-sm p-2 bg-red-50 dark:bg-red-900/20 rounded border">
                                            {{ $suggestion['example_before'] }}
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <flux:text size="xs" class="font-medium text-green-600 mb-1">After:</flux:text>
                                        <div class="text-sm p-2 bg-green-50 dark:bg-green-900/20 rounded border">
                                            {{ $suggestion['example_after'] }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Apply Suggestion Button --}}
                                <div class="mt-3 flex justify-end">
                                    <flux:button 
                                        wire:click="applySuggestion({{ $suggestion['id'] }})"
                                        variant="primary" 
                                        size="sm"
                                        :disabled="in_array($suggestion['id'], $appliedSuggestions)"
                                    >
                                        @if(in_array($suggestion['id'], $appliedSuggestions))
                                            <flux:icon icon="check" class="w-4 h-4" />
                                            Applied
                                        @else
                                            <flux:icon icon="sparkles" class="w-4 h-4" />
                                            Apply Suggestion
                                        @endif
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Template Suggestions --}}
                    <div class="pt-3 border-t">
                        <flux:button 
                            wire:click="loadTemplates"
                            variant="outline" 
                            size="sm"
                            class="w-full"
                        >
                            <flux:icon icon="document-text" class="w-4 h-4" />
                            Browse Feedback Templates
                        </flux:button>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>
    @endif

    {{-- Templates Modal --}}
    @if($showTemplates)
        <flux:modal wire:model="showTemplates" size="xl">
            <flux:modal.header>
                <flux:heading>Feedback Templates</flux:heading>
            </flux:modal.header>
            
            <flux:modal.body>
                @if(!empty($templates))
                    <div class="space-y-4">
                        @foreach($templates as $template)
                            <div class="border rounded-lg p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-medium">{{ $template['title'] }}</h3>
                                        <p class="text-sm text-gray-600 mt-1">{{ $template['description'] }}</p>
                                    </div>
                                    
                                    <flux:badge variant="outline" size="sm">
                                        {{ ucfirst($template['category']) }}
                                    </flux:badge>
                                </div>

                                <div class="mt-3">
                                    <flux:text size="xs" class="font-medium mb-1">Template Example:</flux:text>
                                    <div class="text-sm p-3 bg-gray-50 dark:bg-gray-800 rounded border">
                                        {{ $template['improved_version'] }}
                                    </div>
                                </div>

                                @if(!empty($template['tags']))
                                    <div class="flex flex-wrap gap-1 mt-3">
                                        @foreach($template['tags'] as $tag)
                                            <flux:badge variant="outline" size="xs">
                                                {{ $tag }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-3 flex justify-end">
                                    <flux:button 
                                        wire:click="useTemplate({{ $template['id'] }})"
                                        variant="primary" 
                                        size="sm"
                                    >
                                        Use This Template
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <flux:icon icon="document-text" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <flux:text>No templates found for your current feedback.</flux:text>
                    </div>
                @endif
            </flux:modal.body>
            
            <flux:modal.footer>
                <flux:button 
                    wire:click="$set('showTemplates', false)" 
                    variant="outline"
                >
                    Close
                </flux:button>
            </flux:modal.footer>
        </flux:modal>
    @endif

    {{-- Submit Button --}}
    <div class="flex items-center justify-between">
        <div>
            @if(!empty($appliedSuggestions))
                <flux:text size="sm" class="text-green-600">
                    <flux:icon icon="check-circle" class="w-4 h-4 inline" />
                    {{ count($appliedSuggestions) }} improvement(s) applied
                </flux:text>
            @endif
            
            @if($selectedTemplate)
                <flux:text size="sm" class="text-blue-600">
                    <flux:icon icon="document-text" class="w-4 h-4 inline" />
                    Using template: {{ $selectedTemplate }}
                </flux:text>
            @endif
        </div>
        
        <flux:button 
            wire:click="submitFeedback"
            variant="primary"
            :disabled="empty(trim($feedback))"
        >
            Submit Feedback
        </flux:button>
    </div>
</div>

@script
<script>
    $wire.on('feedback-analyzed', (data) => {
        if (data.showCoaching) {
            // Subtle highlight to draw attention to coaching panel
            setTimeout(() => {
                const coachingPanel = document.querySelector('[class*="border-l-indigo-500"]');
                if (coachingPanel) {
                    coachingPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }, 300);
        }
    });

    $wire.on('suggestion-applied', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                type: 'success',
                message: `Applied ${data.improvement} suggestion`
            }
        }));
    });

    $wire.on('template-applied', (data) => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                type: 'success',
                message: `Applied template: ${data.template}`
            }
        }));
    });

    $wire.on('coaching-dismissed', () => {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                type: 'info',
                message: 'Feedback coaching hidden. You can re-enable it in settings.'
            }
        }));
    });

    $wire.on('feedback-ready', (data) => {
        // This event can be listened to by parent components
        window.dispatchEvent(new CustomEvent('feedbackSubmitted', {
            detail: data
        }));
    });
</script>
@endscript
```

## Testing Strategy

### Feature Tests
```php
<?php

namespace Tests\Feature\FeedbackCoach;

use App\Models\User;
use App\Models\Project;
use App\Models\FeedbackCoachTemplate;
use App\Services\FeedbackCoachService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackCoachTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyzes_feedback_quality(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new FeedbackCoachService();

        $vagueFeedback = "It's okay but needs work.";
        $result = $service->analyzeFeedback($user, $project, $vagueFeedback);

        $this->assertIsArray($result['analysis']);
        $this->assertArrayHasKey('overall_score', $result['analysis']);
        $this->assertLessThan(0.5, $result['analysis']['overall_score']); // Vague feedback should score low
        $this->assertTrue($result['show_coaching']);
    }

    public function test_provides_suggestions_for_poor_feedback(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new FeedbackCoachService();

        // Create some test suggestions
        FeedbackCoachSuggestion::factory()->create(['type' => 'specificity']);
        FeedbackCoachSuggestion::factory()->create(['type' => 'timing']);

        $poorFeedback = "Bad mix, fix it.";
        $result = $service->analyzeFeedback($user, $project, $poorFeedback);

        $this->assertNotEmpty($result['suggestions']);
        $this->assertTrue($result['show_coaching']);
    }

    public function test_does_not_show_coaching_for_good_feedback(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new FeedbackCoachService();

        $goodFeedback = "At 2:15, please boost the vocal level by 2dB and add a subtle reverb to create more space in the mix. The kick drum sounds great but could use a slight EQ adjustment around 80Hz to tighten the low end.";
        $result = $service->analyzeFeedback($user, $project, $goodFeedback);

        $this->assertGreaterThan(0.7, $result['analysis']['overall_score']);
        $this->assertFalse($result['show_coaching']);
    }

    public function test_tracks_user_improvement_over_time(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new FeedbackCoachService();

        // First feedback - poor quality
        $poorResult = $service->analyzeFeedback($user, $project, "Bad mix.");
        
        // Apply improvements
        $service->improveFeedback(
            $poorResult['session_id'],
            [1], // applied suggestion IDs
            "At 1:30, the mix sounds muddy. Please reduce the low-mid frequencies around 200-400Hz on the guitar track."
        );

        $profile = $user->fresh()->feedbackCoachProfile;
        $this->assertEquals(1, $profile->total_feedback_given);
        $this->assertGreaterThan(0, $profile->feedback_quality_score);
    }

    public function test_recommends_relevant_templates(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new FeedbackCoachService();

        // Create templates
        FeedbackCoachTemplate::factory()->create([
            'category' => 'mix',
            'title' => 'Mixing Feedback Template',
            'tags' => ['mix', 'levels', 'eq']
        ]);

        $templates = $service->getRecommendedTemplates($user, 'mix', 'The mix needs work');

        $this->assertNotEmpty($templates);
        $this->assertEquals('mix', $templates[0]['category']);
    }

    public function test_respects_user_coaching_preferences(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new FeedbackCoachService();

        // Disable coaching for user
        $user->feedbackCoachProfile()->create([
            'user_id' => $user->id,
            'coaching_enabled' => false
        ]);

        $poorFeedback = "Bad mix, fix it.";
        $result = $service->analyzeFeedback($user, $project, $poorFeedback);

        $this->assertFalse($result['show_coaching']);
    }

    public function test_tracks_dismissal_patterns(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $service = new FeedbackCoachService();

        // Create and dismiss multiple sessions
        for ($i = 0; $i < 4; $i++) {
            $result = $service->analyzeFeedback($user, $project, "Bad mix {$i}.");
            $service->dismissCoaching($result['session_id'], 'not_helpful');
        }

        $profile = $user->fresh()->feedbackCoachProfile;
        $preferences = $profile->learning_preferences;
        
        $this->assertEquals('reduced', $preferences['coaching_frequency'] ?? null);
    }
}
```

### Unit Tests
```php
<?php

namespace Tests\Unit\Services;

use App\Models\FeedbackCoachUserProfile;
use App\Services\FeedbackCoachService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackCoachServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_extracts_time_references_correctly(): void
    {
        $service = new FeedbackCoachService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractTimeReferences');
        $method->setAccessible(true);

        $feedback = "At 2:30, the vocal is too quiet. The chorus at 1:15 needs more energy.";
        $timeRefs = $method->invoke($service, $feedback);

        $this->assertContains('2:30', $timeRefs);
        $this->assertContains('1:15', $timeRefs);
        $this->assertContains('chorus', $timeRefs);
    }

    public function test_counts_technical_terms_accurately(): void
    {
        $service = new FeedbackCoachService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('countTechnicalTerms');
        $method->setAccessible(true);

        $feedback = "Add compression and EQ to the vocal. The reverb needs adjustment.";
        $count = $method->invoke($service, $feedback);

        $this->assertGreaterThanOrEqual(3, $count); // compression, EQ, reverb
    }

    public function test_calculates_specificity_score(): void
    {
        $service = new FeedbackCoachService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateSpecificityScore');
        $method->setAccessible(true);

        $vagueFeedback = "It sounds kind of weird somehow.";
        $specificFeedback = "At 2:15, boost the vocal 3dB and add compression with 3:1 ratio.";

        $vagueScore = $method->invoke($service, $vagueFeedback);
        $specificScore = $method->invoke($service, $specificFeedback);

        $this->assertLessThan($specificScore, $vagueScore);
        $this->assertGreaterThan(0.7, $specificScore);
    }

    public function test_analyzes_tone_correctly(): void
    {
        $service = new FeedbackCoachService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('analyzeTone');
        $method->setAccessible(true);

        $negativeFeedback = "This is terrible and sounds awful.";
        $constructiveFeedback = "This could be improved by adjusting the EQ.";

        $negativeAnalysis = $method->invoke($service, $negativeFeedback);
        $constructiveAnalysis = $method->invoke($service, $constructiveFeedback);

        $this->assertGreaterThan(0.5, $negativeAnalysis['negativity_score']);
        $this->assertGreaterThan(0.5, $constructiveAnalysis['constructive_score']);
    }

    public function test_calculates_actionability_score(): void
    {
        $service = new FeedbackCoachService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateActionabilityScore');
        $method->setAccessible(true);

        $questionFeedback = "What do you think about this? How does it sound?";
        $actionableFeedback = "Please increase the bass and decrease the treble.";

        $questionScore = $method->invoke($service, $questionFeedback);
        $actionableScore = $method->invoke($service, $actionableFeedback);

        $this->assertLessThan($actionableScore, $questionScore);
        $this->assertGreaterThan(0.6, $actionableScore);
    }

    public function test_adapts_to_user_experience_level(): void
    {
        $service = new FeedbackCoachService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('shouldShowCoaching');
        $method->setAccessible(true);

        $beginnerProfile = FeedbackCoachUserProfile::factory()->make(['experience_level' => 'beginner']);
        $advancedProfile = FeedbackCoachUserProfile::factory()->make(['experience_level' => 'advanced']);

        $mediumQualityAnalysis = ['overall_score' => 0.5];

        $showForBeginner = $method->invoke($service, $beginnerProfile, $mediumQualityAnalysis);
        $showForAdvanced = $method->invoke($service, $advancedProfile, $mediumQualityAnalysis);

        $this->assertFalse($showForBeginner); // 0.5 > 0.4 threshold
        $this->assertTrue($showForAdvanced);  // 0.5 < 0.8 threshold
    }
}
```

## Implementation Steps

### Phase 1: Core Analysis Engine (Week 1)
1. **Database Setup**
   - Create feedback coach tables and relationships
   - Seed initial templates and suggestions
   - Set up user profiles and preferences

2. **Analysis Service**
   - Implement feedback analysis algorithms
   - Create suggestion generation system
   - Build scoring and quality assessment

3. **Basic Templates**
   - Create template library for common scenarios
   - Implement template matching system
   - Add usage tracking and optimization

### Phase 2: UI Integration (Week 2)
1. **Livewire Component**
   - Real-time feedback analysis
   - Suggestion application system
   - Template browsing and selection

2. **Coaching Interface**
   - Smart coaching panel with contextual tips
   - Progressive disclosure of advanced features
   - User preference management

3. **Integration Points**
   - Connect with existing comment systems
   - Integrate with pitch workflow
   - Add to project management interface

### Phase 3: Learning System (Week 3)
1. **Adaptive Intelligence**
   - User behavior tracking and analysis
   - Personalized suggestion prioritization
   - Dynamic coaching threshold adjustment

2. **Advanced Features**
   - Smart template recommendations
   - Feedback pattern recognition
   - Communication style analysis

3. **Reporting and Analytics**
   - Coaching effectiveness metrics
   - User improvement tracking
   - System optimization insights

### Phase 4: Polish and Optimization (Week 4)
1. **Performance Optimization**
   - Fast analysis algorithms
   - Efficient suggestion matching
   - Cached template recommendations

2. **Advanced Templates**
   - Industry-specific templates
   - Role-based suggestions
   - Context-aware recommendations

3. **User Experience Refinement**
   - A/B testing for coaching effectiveness
   - Mobile optimization
   - Accessibility improvements

## Security Considerations

### Data Privacy
- **Feedback Analysis**: All analysis performed locally, no external API calls
- **User Patterns**: Aggregated learning data only, no personal content stored
- **Template Sharing**: Optional community templates with privacy controls
- **Audit Trail**: Complete tracking of coaching interactions for improvement

### Content Safety
- **Input Validation**: Comprehensive validation of all feedback content
- **Content Filtering**: Detection and flagging of inappropriate content
- **Rate Limiting**: Prevent spam or abuse of coaching system
- **User Control**: Complete control over coaching preferences and data

This comprehensive implementation plan provides intelligent feedback coaching while maintaining MixPitch's focus on improving creative collaboration and communication quality.