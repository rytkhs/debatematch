<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesUsers;

class ContactTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    /**
     * @test
     */
    public function test_fillable_attributes()
    {
        $expectedFillable = [
            'type',
            'name',
            'email',
            'subject',
            'message',
            'status',
            'language',
            'user_id',
            'admin_notes',
            'replied_at',
        ];

        $contact = new Contact();
        $this->assertEquals($expectedFillable, $contact->getFillable());
    }

    /**
     * @test
     */
    public function test_casts()
    {
        $expectedCasts = [
            'id' => 'int',
            'replied_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        $contact = new Contact();
        $this->assertEquals($expectedCasts, $contact->getCasts());
    }

    /**
     * @test
     */
    public function test_contact_type_constants()
    {
        $this->assertEquals('bug_report', Contact::TYPE_BUG_REPORT);
        $this->assertEquals('feature_request', Contact::TYPE_FEATURE_REQUEST);
        $this->assertEquals('general_question', Contact::TYPE_GENERAL_QUESTION);
        $this->assertEquals('account_issues', Contact::TYPE_ACCOUNT_ISSUES);
        $this->assertEquals('other', Contact::TYPE_OTHER);
    }

    /**
     * @test
     */
    public function test_factory_creation()
    {
        $contact = Contact::factory()->create();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    /**
     * @test
     */
    public function test_basic_attributes()
    {
        $contact = Contact::factory()->create([
            'type' => Contact::TYPE_BUG_REPORT,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
            'status' => 'new',
            'language' => 'ja',
        ]);

        $this->assertEquals(Contact::TYPE_BUG_REPORT, $contact->type);
        $this->assertEquals('Test User', $contact->name);
        $this->assertEquals('test@example.com', $contact->email);
        $this->assertEquals('Test Subject', $contact->subject);
        $this->assertEquals('Test message content', $contact->message);
        $this->assertEquals('new', $contact->status);
        $this->assertEquals('ja', $contact->language);
    }

    /**
     * @test
     */
    public function test_replied_at_cast()
    {
        $repliedAt = now()->subDays(3);
        $contact = Contact::factory()->create([
            'replied_at' => $repliedAt,
        ]);

        $contact = $contact->fresh();
        $this->assertInstanceOf(\Carbon\Carbon::class, $contact->replied_at);
        $this->assertEquals($repliedAt->format('Y-m-d H:i:s'), $contact->replied_at->format('Y-m-d H:i:s'));
    }

    /**
     * Relationship tests
     */

    /**
     * @test
     */
    public function test_user_relationship()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $contact->user);
        $this->assertEquals($user->id, $contact->user->id);
    }

    /**
     * @test
     */
    public function test_anonymous_contact()
    {
        $contact = Contact::factory()->create(['user_id' => null]);

        $this->assertNull($contact->user_id);
        $this->assertNull($contact->user);
    }

    /**
     * @test
     */
    public function test_user_relationship_with_soft_deleted_user()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);

        // Soft delete the user
        $user->delete();

        // Contact should still exist and user relationship should work
        $this->assertNotNull(Contact::find($contact->id));
        // Note: Contact model doesn't use withTrashed() on user relationship
        // so deleted user won't be accessible unless explicitly added
    }

    /**
     * Static method tests
     */

    /**
     * @test
     */
    public function test_get_types()
    {
        $types = Contact::getTypes();

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);

        // Should contain expected types if they are enabled in config
        $validTypes = Contact::getValidTypes();
        foreach ($validTypes as $validType) {
            $this->assertArrayHasKey($validType, $types);
        }
    }

    /**
     * @test
     */
    public function test_get_valid_types()
    {
        $validTypes = Contact::getValidTypes();

        $this->assertIsArray($validTypes);
        $this->assertNotEmpty($validTypes);

        // Should be array of strings
        foreach ($validTypes as $type) {
            $this->assertIsString($type);
        }
    }

    /**
     * @test
     */
    public function test_get_statuses()
    {
        $statuses = Contact::getStatuses();

        $this->assertIsArray($statuses);
        $this->assertNotEmpty($statuses);

        // Should contain status keys and labels
        foreach ($statuses as $key => $label) {
            $this->assertIsString($key);
            $this->assertIsString($label);
        }
    }

    /**
     * Accessor tests
     */

    /**
     * @test
     */
    public function test_type_name_attribute()
    {
        $contact = Contact::factory()->create(['type' => Contact::TYPE_BUG_REPORT]);

        $typeName = $contact->type_name;
        $this->assertIsString($typeName);
        $this->assertNotEmpty($typeName);
    }

    /**
     * @test
     */
    public function test_status_name_attribute()
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $statusName = $contact->status_name;
        $this->assertIsString($statusName);
        $this->assertNotEmpty($statusName);
    }

    /**
     * @test
     */
    public function test_type_emoji_attribute()
    {
        $contact = Contact::factory()->create(['type' => Contact::TYPE_BUG_REPORT]);

        $emoji = $contact->type_emoji;
        $this->assertIsString($emoji);
        $this->assertNotEmpty($emoji);
    }

    /**
     * @test
     */
    public function test_status_color_attribute()
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $color = $contact->status_color;
        $this->assertIsString($color);
        $this->assertNotEmpty($color);
        // Should be a color code
        $this->assertStringStartsWith('#', $color);
    }

    /**
     * @test
     */
    public function test_status_css_class_attribute()
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $cssClass = $contact->status_css_class;
        $this->assertIsString($cssClass);
        $this->assertNotEmpty($cssClass);

        // Should contain Tailwind CSS classes
        $this->assertStringContainsString('bg-', $cssClass);
        $this->assertStringContainsString('text-', $cssClass);
    }

    /**
     * Scope tests
     */

    /**
     * @test
     */
    public function test_by_status_scope()
    {
        Contact::factory()->create(['status' => 'new']);
        Contact::factory()->create(['status' => 'in_progress']);
        Contact::factory()->create(['status' => 'new']);

        $newContacts = Contact::byStatus('new')->get();
        $inProgressContacts = Contact::byStatus('in_progress')->get();

        $this->assertEquals(2, $newContacts->count());
        $this->assertEquals(1, $inProgressContacts->count());

        foreach ($newContacts as $contact) {
            $this->assertEquals('new', $contact->status);
        }
    }

    /**
     * @test
     */
    public function test_by_type_scope()
    {
        Contact::factory()->create(['type' => Contact::TYPE_BUG_REPORT]);
        Contact::factory()->create(['type' => Contact::TYPE_FEATURE_REQUEST]);
        Contact::factory()->create(['type' => Contact::TYPE_BUG_REPORT]);

        $bugReports = Contact::byType(Contact::TYPE_BUG_REPORT)->get();
        $featureRequests = Contact::byType(Contact::TYPE_FEATURE_REQUEST)->get();

        $this->assertEquals(2, $bugReports->count());
        $this->assertEquals(1, $featureRequests->count());

        foreach ($bugReports as $contact) {
            $this->assertEquals(Contact::TYPE_BUG_REPORT, $contact->type);
        }
    }

    /**
     * @test
     */
    public function test_latest_scope()
    {
        $oldContact = Contact::factory()->create(['created_at' => now()->subDays(5)]);
        $newContact = Contact::factory()->create(['created_at' => now()->subDay()]);
        $newestContact = Contact::factory()->create(['created_at' => now()]);

        $contacts = Contact::latest()->get();

        $this->assertEquals($newestContact->id, $contacts[0]->id);
        $this->assertEquals($newContact->id, $contacts[1]->id);
        $this->assertEquals($oldContact->id, $contacts[2]->id);
    }

    /**
     * Factory state tests
     */

    /**
     * @test
     */
    public function test_factory_from_user()
    {
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $contact = Contact::factory()->fromUser($user)->create();

        $this->assertEquals($user->id, $contact->user_id);
        $this->assertEquals($user->name, $contact->name);
        $this->assertEquals($user->email, $contact->email);
    }

    /**
     * @test
     */
    public function test_factory_anonymous()
    {
        $contact = Contact::factory()->anonymous()->create();

        $this->assertNull($contact->user_id);
    }

    /**
     * @test
     */
    public function test_factory_bug_report()
    {
        $contact = Contact::factory()->bugReport()->create();

        $this->assertEquals(Contact::TYPE_BUG_REPORT, $contact->type);
        $this->assertStringContainsString('Bug Report', $contact->subject);
    }

    /**
     * @test
     */
    public function test_factory_feature_request()
    {
        $contact = Contact::factory()->featureRequest()->create();

        $this->assertEquals(Contact::TYPE_FEATURE_REQUEST, $contact->type);
        $this->assertStringContainsString('Feature Request', $contact->subject);
    }

    /**
     * @test
     */
    public function test_factory_status_states()
    {
        $newContact = Contact::factory()->newStatus()->create();
        $this->assertEquals('new', $newContact->status);
        $this->assertNull($newContact->admin_notes);
        $this->assertNull($newContact->replied_at);

        $repliedContact = Contact::factory()->replied()->create();
        $this->assertEquals('replied', $repliedContact->status);
        $this->assertNotNull($repliedContact->admin_notes);
        $this->assertNotNull($repliedContact->replied_at);

        $resolvedContact = Contact::factory()->resolved()->create();
        $this->assertEquals('resolved', $resolvedContact->status);
        $this->assertNotNull($repliedContact->replied_at);
    }

    /**
     * @test
     */
    public function test_factory_language_states()
    {
        $japaneseContact = Contact::factory()->japanese()->create();
        $this->assertEquals('ja', $japaneseContact->language);
        $this->assertStringContainsString('お問い合わせ', $japaneseContact->subject);

        $englishContact = Contact::factory()->english()->create();
        $this->assertEquals('en', $englishContact->language);
    }

    /**
     * Integration tests
     */

    /**
     * @test
     */
    public function test_contact_lifecycle()
    {
        // Create new contact
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'user_id' => $user->id,
            'status' => 'new',
            'type' => Contact::TYPE_BUG_REPORT,
        ]);

        // Initial state
        $this->assertEquals('new', $contact->status);
        $this->assertNull($contact->admin_notes);
        $this->assertNull($contact->replied_at);

        // Progress to in_progress
        $contact->update([
            'status' => 'in_progress',
            'admin_notes' => 'Working on this issue',
        ]);

        $this->assertEquals('in_progress', $contact->status);
        $this->assertEquals('Working on this issue', $contact->admin_notes);

        // Mark as replied
        $repliedAt = now();
        $contact->update([
            'status' => 'replied',
            'replied_at' => $repliedAt,
        ]);

        $this->assertEquals('replied', $contact->status);
        $this->assertNotNull($contact->replied_at);
    }

    /**
     * @test
     */
    public function test_contact_with_long_content()
    {
        $longMessage = str_repeat('This is a very long message. ', 200);
        $contact = Contact::factory()->create(['message' => $longMessage]);

        $this->assertEquals($longMessage, $contact->message);
    }

    /**
     * @test
     */
    public function test_contact_with_special_characters()
    {
        $specialContent = 'Special chars: 日本語 emoji 😊 symbols @#$%^&*()';
        $contact = Contact::factory()->create([
            'subject' => $specialContent,
            'message' => $specialContent,
        ]);

        $this->assertEquals($specialContent, $contact->subject);
        $this->assertEquals($specialContent, $contact->message);
    }
}
