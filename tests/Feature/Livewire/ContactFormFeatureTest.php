<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ContactForm;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFormFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function contact_form_renders_successfully()
    {
        $this->get('/contact')
            ->assertSeeLivewire(ContactForm::class);
    }

    #[Test]
    public function authenticated_user_can_submit_contact_form()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->actingAs($user);

        Livewire::test(ContactForm::class)
            ->set('type', 'bug_report')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('subject', 'Test Subject')
            ->set('message', 'This is a test message with more than 10 characters.')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contacts', [
            'type' => 'bug_report',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with more than 10 characters.',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function guest_user_can_submit_contact_form()
    {
        Livewire::test(ContactForm::class)
            ->set('type', 'bug_report')
            ->set('name', 'Guest User')
            ->set('email', 'guest@example.com')
            ->set('subject', 'Guest Inquiry')
            ->set('message', 'This is a guest inquiry with sufficient length.')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contacts', [
            'type' => 'bug_report',
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'subject' => 'Guest Inquiry',
            'message' => 'This is a guest inquiry with sufficient length.',
            'user_id' => null,
        ]);
    }

    #[Test]
    public function form_validates_required_fields()
    {
        Livewire::test(ContactForm::class)
            ->call('submit')
            ->assertHasErrors(['type', 'name', 'email', 'subject', 'message']);
    }

    #[Test]
    public function form_validates_email_format()
    {
        Livewire::test(ContactForm::class)
            ->set('email', 'invalid-email')
            ->assertHasErrors('email');
    }

    #[Test]
    public function form_validates_message_length()
    {
        Livewire::test(ContactForm::class)
            ->set('message', 'short')
            ->assertHasErrors('message');

        Livewire::test(ContactForm::class)
            ->set('message', str_repeat('a', 5001))
            ->assertHasErrors('message');
    }

    #[Test]
    public function form_can_be_reset()
    {
        $livewire = Livewire::test(ContactForm::class)
            ->set('type', 'bug_report')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('subject', 'Test Subject')
            ->set('message', 'Test message with sufficient length.')
            ->set('submitted', true)
            ->set('contactId', 123);

        $livewire->call('resetForm')
            ->assertSet('submitted', false)
            ->assertSet('contactId', null);
    }

    #[Test]
    public function authenticated_user_name_and_email_are_pre_filled()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->actingAs($user);

        Livewire::test(ContactForm::class)
            ->assertSet('name', 'John Doe')
            ->assertSet('email', 'john@example.com');
    }

    #[Test]
    public function real_time_validation_works_for_all_fields()
    {
        $livewire = Livewire::test(ContactForm::class);

        // Type validation
        $livewire->set('type', 'invalid_type')
            ->assertHasErrors('type');

        $livewire->set('type', 'bug_report')
            ->assertHasNoErrors('type');

        // Name validation
        $livewire->set('name', '')
            ->assertHasErrors('name');

        $livewire->set('name', 'Valid Name')
            ->assertHasNoErrors('name');

        // Subject validation
        $livewire->set('subject', '')
            ->assertHasErrors('subject');

        $livewire->set('subject', 'Valid Subject')
            ->assertHasNoErrors('subject');
    }
}
