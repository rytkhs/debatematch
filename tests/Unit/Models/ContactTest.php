<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesUsers;

class ContactTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
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

    #[Test]
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

    #[Test]
    public function test_contact_type_constants()
    {
        $this->assertEquals('bug_report', Contact::TYPE_BUG_REPORT);
        $this->assertEquals('feature_request', Contact::TYPE_FEATURE_REQUEST);
        $this->assertEquals('general_question', Contact::TYPE_GENERAL_QUESTION);
        $this->assertEquals('account_issues', Contact::TYPE_ACCOUNT_ISSUES);
        $this->assertEquals('other', Contact::TYPE_OTHER);
    }

    #[Test]
    public function test_factory_creation()
    {
        $contact = Contact::factory()->create();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function test_user_relationship()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $contact->user);
        $this->assertEquals($user->id, $contact->user->id);
    }

    #[Test]
    public function test_anonymous_contact()
    {
        $contact = Contact::factory()->create(['user_id' => null]);

        $this->assertNull($contact->user_id);
        $this->assertNull($contact->user);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function test_get_statuses()
    {
        $statuses = Contact::getStatuses();

        $this->assertIsArray($statuses);
        $this->assertNotEmpty($statuses);

        // Should contain expected statuses
        $expectedStatuses = ['new', 'in_progress', 'replied', 'closed'];
        foreach ($expectedStatuses as $status) {
            $this->assertArrayHasKey($status, $statuses);
        }
    }

    /**
     * Accessor tests
     */

    #[Test]
    public function test_type_name_attribute()
    {
        $contact = Contact::factory()->create(['type' => Contact::TYPE_BUG_REPORT]);

        $this->assertIsString($contact->type_name);
        $this->assertNotEmpty($contact->type_name);
    }

    #[Test]
    public function test_status_name_attribute()
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $this->assertIsString($contact->status_name);
        $this->assertNotEmpty($contact->status_name);
    }

    #[Test]
    public function test_type_emoji_attribute()
    {
        $contact = Contact::factory()->create(['type' => Contact::TYPE_BUG_REPORT]);

        $this->assertIsString($contact->type_emoji);
        $this->assertNotEmpty($contact->type_emoji);
    }

    #[Test]
    public function test_status_color_attribute()
    {
        $contact = Contact::factory()->create(['status' => 'new']);

        $statusColor = $contact->status_color;
        $this->assertIsString($statusColor);
        $this->assertNotEmpty($statusColor);
    }

    #[Test]
    public function test_status_css_class_attribute()
    {
        $statuses = ['new', 'in_progress', 'replied', 'closed'];

        foreach ($statuses as $status) {
            $contact = Contact::factory()->create(['status' => $status]);
            $cssClass = $contact->status_css_class;

            $this->assertIsString($cssClass);
            $this->assertNotEmpty($cssClass);
        }
    }

    /**
     * Scope tests
     */

    #[Test]
    public function test_by_status_scope()
    {
        Contact::factory()->create(['status' => 'new']);
        Contact::factory()->create(['status' => 'in_progress']);
        Contact::factory()->create(['status' => 'replied']);

        $newContacts = Contact::byStatus('new')->get();
        $inProgressContacts = Contact::byStatus('in_progress')->get();

        $this->assertCount(1, $newContacts);
        $this->assertCount(1, $inProgressContacts);
        $this->assertEquals('new', $newContacts->first()->status);
        $this->assertEquals('in_progress', $inProgressContacts->first()->status);
    }

    #[Test]
    public function test_by_type_scope()
    {
        Contact::factory()->create(['type' => Contact::TYPE_BUG_REPORT]);
        Contact::factory()->create(['type' => Contact::TYPE_FEATURE_REQUEST]);
        Contact::factory()->create(['type' => Contact::TYPE_GENERAL_QUESTION]);

        $bugReports = Contact::byType(Contact::TYPE_BUG_REPORT)->get();
        $featureRequests = Contact::byType(Contact::TYPE_FEATURE_REQUEST)->get();

        $this->assertCount(1, $bugReports);
        $this->assertCount(1, $featureRequests);
        $this->assertEquals(Contact::TYPE_BUG_REPORT, $bugReports->first()->type);
        $this->assertEquals(Contact::TYPE_FEATURE_REQUEST, $featureRequests->first()->type);
    }

    #[Test]
    public function test_latest_scope()
    {
        $older = Contact::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = Contact::factory()->create(['created_at' => now()->subDay()]);
        $newest = Contact::factory()->create(['created_at' => now()]);

        $latestContacts = Contact::latest()->get();

        $this->assertEquals($newest->id, $latestContacts->first()->id);
        $this->assertEquals($older->id, $latestContacts->last()->id);
    }

    /**
     * Factory state tests
     */

    #[Test]
    public function test_factory_from_user()
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->fromUser($user)->create();

        $this->assertEquals($user->id, $contact->user_id);
        $this->assertEquals($user->name, $contact->name);
        $this->assertEquals($user->email, $contact->email);
    }

    #[Test]
    public function test_factory_anonymous()
    {
        $contact = Contact::factory()->anonymous()->create();

        $this->assertNull($contact->user_id);
        $this->assertNotNull($contact->name);
        $this->assertNotNull($contact->email);
    }

    #[Test]
    public function test_factory_bug_report()
    {
        $contact = Contact::factory()->bugReport()->create(['language' => 'ja']);

        $this->assertEquals(Contact::TYPE_BUG_REPORT, $contact->type);
        $this->assertStringContainsString('バグ', $contact->subject);
    }

    #[Test]
    public function test_factory_feature_request()
    {
        $contact = Contact::factory()->featureRequest()->create(['language' => 'ja']);

        $this->assertEquals(Contact::TYPE_FEATURE_REQUEST, $contact->type);
        $this->assertStringContainsString('機能', $contact->subject);
    }

    #[Test]
    public function test_factory_status_states()
    {
        $newContact = Contact::factory()->newStatus()->create();
        $inProgressContact = Contact::factory()->inProgress()->create();
        $repliedContact = Contact::factory()->replied()->create();
        $closedContact = Contact::factory()->closed()->create();

        $this->assertEquals('new', $newContact->status);
        $this->assertEquals('in_progress', $inProgressContact->status);
        $this->assertEquals('replied', $repliedContact->status);
        $this->assertEquals('closed', $closedContact->status);
        $this->assertNotNull($repliedContact->replied_at);
    }

    #[Test]
    public function test_factory_language_states()
    {
        $jaContact = Contact::factory()->japanese()->create();
        $enContact = Contact::factory()->english()->create();

        $this->assertEquals('ja', $jaContact->language);
        $this->assertEquals('en', $enContact->language);
    }

    /**
     * Integration tests
     */

    #[Test]
    public function test_contact_lifecycle()
    {
        // Create new contact
        $contact = Contact::factory()->newStatus()->create();
        $this->assertEquals('new', $contact->status);

        // Update to in progress
        $contact->update(['status' => 'in_progress']);
        $this->assertEquals('in_progress', $contact->status);

        // Reply to contact
        $contact->update([
            'status' => 'replied',
            'replied_at' => now(),
            'admin_notes' => 'Issue resolved',
        ]);
        $this->assertEquals('replied', $contact->status);
        $this->assertNotNull($contact->replied_at);
        $this->assertEquals('Issue resolved', $contact->admin_notes);

        // Close contact
        $contact->update(['status' => 'closed']);
        $this->assertEquals('closed', $contact->status);
    }

    #[Test]
    public function test_contact_with_long_content()
    {
        $longMessage = str_repeat('Long message content. ', 100);
        $contact = Contact::factory()->create(['message' => $longMessage]);

        $this->assertEquals($longMessage, $contact->message);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'message' => $longMessage]);
    }

    #[Test]
    public function test_contact_with_special_characters()
    {
        $specialChars = '特殊文字テスト！@#$%^&*()_+-=[]{}|;:\'",.<>?/~`';
        $contact = Contact::factory()->create([
            'subject' => $specialChars,
            'message' => $specialChars,
        ]);

        $this->assertEquals($specialChars, $contact->subject);
        $this->assertEquals($specialChars, $contact->message);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'subject' => $specialChars,
            'message' => $specialChars,
        ]);
    }
}
