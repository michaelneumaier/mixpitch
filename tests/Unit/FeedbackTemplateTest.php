<?php

namespace Tests\Unit;

use App\Models\FeedbackTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_template_can_be_created()
    {
        $user = User::factory()->create();
        
        $template = FeedbackTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'description' => 'Test Description',
            'category' => FeedbackTemplate::CATEGORY_GENERAL,
            'questions' => [
                [
                    'id' => 'test_question',
                    'type' => FeedbackTemplate::TYPE_TEXT,
                    'label' => 'Test Question',
                    'required' => true,
                ]
            ],
        ]);

        $this->assertDatabaseHas('feedback_templates', [
            'name' => 'Test Template',
            'user_id' => $user->id,
        ]);
        
        $this->assertEquals('Test Template', $template->name);
        $this->assertEquals($user->id, $template->user_id);
        $this->assertEquals(FeedbackTemplate::CATEGORY_GENERAL, $template->category);
        $this->assertIsArray($template->questions);
    }

    public function test_feedback_template_belongs_to_user()
    {
        $user = User::factory()->create();
        $template = FeedbackTemplate::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $template->creator);
        $this->assertEquals($user->id, $template->creator->id);
    }

    public function test_feedback_template_can_be_default()
    {
        $template = FeedbackTemplate::factory()->default()->create();

        $this->assertTrue($template->is_default);
        $this->assertNull($template->user_id);
        $this->assertTrue($template->isSystemTemplate());
    }

    public function test_feedback_template_scopes()
    {
        $user = User::factory()->create();
        
        // Create various templates
        $activeTemplate = FeedbackTemplate::factory()->create(['is_active' => true, 'category' => FeedbackTemplate::CATEGORY_GENERAL]);
        $inactiveTemplate = FeedbackTemplate::factory()->create(['is_active' => false, 'category' => FeedbackTemplate::CATEGORY_GENERAL]);
        $defaultTemplate = FeedbackTemplate::factory()->default()->create(['category' => FeedbackTemplate::CATEGORY_GENERAL]);
        $customTemplate = FeedbackTemplate::factory()->create(['user_id' => $user->id, 'category' => FeedbackTemplate::CATEGORY_GENERAL]);
        $categoryTemplate = FeedbackTemplate::factory()->category(FeedbackTemplate::CATEGORY_MIXING)->create();

        // Debug the actual counts
        $activeCount = FeedbackTemplate::active()->count();
        $defaultCount = FeedbackTemplate::default()->count();
        $customCount = FeedbackTemplate::custom()->count();
        $mixingCount = FeedbackTemplate::byCategory(FeedbackTemplate::CATEGORY_MIXING)->count();
        $availableCount = FeedbackTemplate::availableToUser($user->id)->count();
        
        // Test scopes
        $this->assertEquals(4, $activeCount); // All except inactive
        $this->assertEquals(1, $defaultCount);
        $this->assertEquals(4, $customCount); // activeTemplate, customTemplate, categoryTemplate, and inactiveTemplate all have user_id and is_default=false
        $this->assertEquals(1, $mixingCount); // Only categoryTemplate
        $this->assertEquals(2, $availableCount); // Default + user's custom
    }

    public function test_feedback_template_category_label()
    {
        $template = FeedbackTemplate::factory()->create([
            'category' => FeedbackTemplate::CATEGORY_MIXING
        ]);

        $this->assertEquals('Mixing', $template->category_label);
    }

    public function test_feedback_template_increment_usage()
    {
        $template = FeedbackTemplate::factory()->create(['usage_count' => 5]);

        $template->incrementUsage();

        $this->assertEquals(6, $template->fresh()->usage_count);
    }

    public function test_feedback_template_validates_questions()
    {
        $template = new FeedbackTemplate([
            'questions' => [
                [
                    'id' => 'valid_question',
                    'type' => FeedbackTemplate::TYPE_TEXT,
                    'label' => 'Valid Question',
                ],
                [
                    // Missing required fields
                    'type' => FeedbackTemplate::TYPE_RATING,
                ],
                [
                    'id' => 'rating_question',
                    'type' => FeedbackTemplate::TYPE_RATING,
                    'label' => 'Rating Question',
                    // Missing max_rating
                ]
            ]
        ]);

        $errors = $template->validateQuestions();

        $this->assertNotEmpty($errors);
        $this->assertContains('Question 2: ID is required', $errors);
        $this->assertContains('Question 2: Label is required', $errors);
        $this->assertContains('Question 3: Max rating is required for rating questions', $errors);
    }

    public function test_feedback_template_validates_question_with_options()
    {
        $template = new FeedbackTemplate([
            'questions' => [
                [
                    'id' => 'select_question',
                    'type' => FeedbackTemplate::TYPE_SELECT,
                    'label' => 'Select Question',
                    // Missing options
                ]
            ]
        ]);

        $errors = $template->validateQuestions();

        $this->assertContains('Question 1: Options are required for this question type', $errors);
    }

    public function test_feedback_template_validates_range_question()
    {
        $template = new FeedbackTemplate([
            'questions' => [
                [
                    'id' => 'range_question',
                    'type' => FeedbackTemplate::TYPE_RANGE,
                    'label' => 'Range Question',
                    // Missing min/max
                ]
            ]
        ]);

        $errors = $template->validateQuestions();

        $this->assertContains('Question 1: Min and max values are required for range questions', $errors);
    }

    public function test_feedback_template_creates_default_question()
    {
        $textQuestion = FeedbackTemplate::createDefaultQuestion(FeedbackTemplate::TYPE_TEXT, 'Test Text');
        $ratingQuestion = FeedbackTemplate::createDefaultQuestion(FeedbackTemplate::TYPE_RATING, 'Test Rating');
        $selectQuestion = FeedbackTemplate::createDefaultQuestion(FeedbackTemplate::TYPE_SELECT, 'Test Select');
        $rangeQuestion = FeedbackTemplate::createDefaultQuestion(FeedbackTemplate::TYPE_RANGE, 'Test Range');

        $this->assertEquals(FeedbackTemplate::TYPE_TEXT, $textQuestion['type']);
        $this->assertEquals('Test Text', $textQuestion['label']);
        $this->assertFalse($textQuestion['required']);

        $this->assertEquals(5, $ratingQuestion['max_rating']);
        $this->assertIsArray($selectQuestion['options']);
        $this->assertEquals(0, $rangeQuestion['min']);
        $this->assertEquals(100, $rangeQuestion['max']);
    }

    public function test_feedback_template_get_categories()
    {
        $categories = FeedbackTemplate::getCategories();

        $this->assertIsArray($categories);
        $this->assertArrayHasKey(FeedbackTemplate::CATEGORY_GENERAL, $categories);
        $this->assertArrayHasKey(FeedbackTemplate::CATEGORY_MIXING, $categories);
        $this->assertEquals('General Feedback', $categories[FeedbackTemplate::CATEGORY_GENERAL]);
    }

    public function test_feedback_template_get_question_types()
    {
        $questionTypes = FeedbackTemplate::getQuestionTypes();

        $this->assertIsArray($questionTypes);
        $this->assertArrayHasKey(FeedbackTemplate::TYPE_TEXT, $questionTypes);
        $this->assertArrayHasKey(FeedbackTemplate::TYPE_RATING, $questionTypes);
        $this->assertEquals('Short Text', $questionTypes[FeedbackTemplate::TYPE_TEXT]);
    }

    public function test_feedback_template_get_default_templates()
    {
        $defaultTemplates = FeedbackTemplate::getDefaultTemplates();

        $this->assertIsArray($defaultTemplates);
        $this->assertNotEmpty($defaultTemplates);
        
        $generalTemplate = collect($defaultTemplates)->firstWhere('name', 'General Audio Feedback');
        $this->assertNotNull($generalTemplate);
        $this->assertEquals(FeedbackTemplate::CATEGORY_GENERAL, $generalTemplate['category']);
        $this->assertIsArray($generalTemplate['questions']);
        $this->assertNotEmpty($generalTemplate['questions']);
    }

    public function test_feedback_template_belongs_to_user_check()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $template = FeedbackTemplate::factory()->create(['user_id' => $user1->id]);

        $this->assertTrue($template->belongsToUser($user1->id));
        $this->assertFalse($template->belongsToUser($user2->id));
    }

    public function test_feedback_template_validates_invalid_question_type()
    {
        $template = new FeedbackTemplate([
            'questions' => [
                [
                    'id' => 'invalid_question',
                    'type' => 'invalid_type',
                    'label' => 'Invalid Question',
                ]
            ]
        ]);

        $errors = $template->validateQuestions();

        $this->assertContains('Question 1: Invalid question type', $errors);
    }

    public function test_feedback_template_casts_questions_to_array()
    {
        $questionsArray = [['id' => 'test', 'type' => 'text', 'label' => 'Test']];
        
        $template = FeedbackTemplate::factory()->create([
            'questions' => $questionsArray
        ]);

        $this->assertIsArray($template->questions);
        $this->assertEquals('test', $template->questions[0]['id']);
        
        // Test that it's properly cast when retrieved fresh from database
        $template = $template->fresh();
        $this->assertIsArray($template->questions);
        $this->assertEquals('test', $template->questions[0]['id']);
    }
}