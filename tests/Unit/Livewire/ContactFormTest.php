<?php

namespace Tests\Unit\Livewire;

use PHPUnit\Framework\Attributes\Test;
use App\Livewire\ContactForm;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Unit\Livewire\BaseLivewireTest;

class ContactFormTest extends BaseLivewireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // 言語設定
        app()->setLocale('en');
    }

    #[Test]
    public function testMount()
    {
        $livewire = Livewire::test(ContactForm::class);

        $livewire
            ->assertSet('type', '')
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('subject', '')
            ->assertSet('message', '')
            ->assertSet('language', 'en')
            ->assertSet('submitted', false)
            ->assertSet('contactId', null);
    }

    #[Test]
    public function testMountWithAuthenticatedUser()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $livewire = $this->testAsUser(ContactForm::class, [], $user);

        $livewire
            ->assertSet('name', 'Test User')
            ->assertSet('email', 'test@example.com')
            ->assertSet('language', 'en');
    }

    #[Test]
    public function testMountWithGuestUser()
    {
        $livewire = Livewire::test(ContactForm::class);

        $livewire
            ->assertSet('name', '')
            ->assertSet('email', '');
    }

    #[Test]
    public function testRender()
    {
        $livewire = Livewire::test(ContactForm::class);

        $livewire
            ->assertStatus(200)
            ->assertViewIs('livewire.contact-form')
            ->assertViewHas('contactTypes');
    }

    #[Test]
    public function testContactTypesAreLoaded()
    {
        $livewire = Livewire::test(ContactForm::class);

        $contactTypes = Contact::getTypes();
        $livewire->assertViewHas('contactTypes', $contactTypes);
    }

    #[Test]
    public function testUpdatedTypeValidation()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 有効な種別
        $livewire
            ->set('type', 'bug_report')
            ->assertHasNoErrors('type');

        // 無効な種別
        $livewire
            ->set('type', 'invalid_type')
            ->assertHasErrors('type');

        // 空の種別
        $livewire
            ->set('type', '')
            ->assertHasErrors('type');
    }

    #[Test]
    public function testUpdatedNameValidation()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 有効な名前
        $livewire
            ->set('name', 'Test User')
            ->assertHasNoErrors('name');

        // 空の名前
        $livewire
            ->set('name', '')
            ->assertHasErrors('name');

        // 長すぎる名前
        $livewire
            ->set('name', str_repeat('a', 256))
            ->assertHasErrors('name');
    }

    #[Test]
    public function testUpdatedEmailValidation()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 有効なメール
        $livewire
            ->set('email', 'test@example.com')
            ->assertHasNoErrors('email');

        // 無効なメール
        $livewire
            ->set('email', 'invalid-email')
            ->assertHasErrors('email');

        // 空のメール
        $livewire
            ->set('email', '')
            ->assertHasErrors('email');
    }

    #[Test]
    public function testUpdatedSubjectValidation()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 有効な件名
        $livewire
            ->set('subject', 'Test Subject')
            ->assertHasNoErrors('subject');

        // 空の件名
        $livewire
            ->set('subject', '')
            ->assertHasErrors('subject');

        // 長すぎる件名
        $livewire
            ->set('subject', str_repeat('a', 501))
            ->assertHasErrors('subject');
    }

    #[Test]
    public function testUpdatedMessageValidation()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 有効なメッセージ
        $livewire
            ->set('message', 'This is a test message with enough characters.')
            ->assertHasNoErrors('message');

        // 空のメッセージ
        $livewire
            ->set('message', '')
            ->assertHasErrors('message');

        // 短すぎるメッセージ
        $livewire
            ->set('message', 'short')
            ->assertHasErrors('message');

        // 長すぎるメッセージ
        $livewire
            ->set('message', str_repeat('a', 5001))
            ->assertHasErrors('message');
    }

    #[Test]
    public function testFormValidationOnSubmit()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 空のフォームで送信
        $livewire
            ->call('submit')
            ->assertHasErrors(['type', 'name', 'email', 'subject', 'message']);
    }

    #[Test]
    public function testSuccessfulContactSubmission()
    {
        $livewire = Livewire::test(ContactForm::class);

        $livewire
            ->set('type', 'bug_report')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('subject', 'Test Subject')
            ->set('message', 'This is a test message with enough characters.')
            ->call('submit');

        // データベースに保存されているか確認
        $this->assertDatabaseHas('contacts', [
            'type' => 'bug_report',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with enough characters.',
            'language' => 'en',
            'user_id' => null,
        ]);

        // フォーム状態の確認
        $livewire
            ->assertSet('submitted', true)
            ->assertSet('type', '')
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('subject', '')
            ->assertSet('message', '');

        // contactId が設定されているか確認
        $contact = Contact::where('email', 'test@example.com')->first();
        $livewire->assertSet('contactId', $contact->id);
    }

    #[Test]
    public function testSuccessfulContactSubmissionWithAuthenticatedUser()
    {
        $user = User::factory()->create([
            'name' => 'Authenticated User',
            'email' => 'auth@example.com'
        ]);

        $livewire = $this->testAsUser(ContactForm::class, [], $user);

        $livewire
            ->set('type', 'feature_request')
            ->set('name', 'Authenticated User')
            ->set('email', 'auth@example.com')
            ->set('subject', 'Feature Request')
            ->set('message', 'This is a feature request message.')
            ->call('submit');

        // データベースに保存されているか確認
        $this->assertDatabaseHas('contacts', [
            'type' => 'feature_request',
            'name' => 'Authenticated User',
            'email' => 'auth@example.com',
            'subject' => 'Feature Request',
            'message' => 'This is a feature request message.',
            'language' => 'en',
            'user_id' => $user->id,
        ]);

        // フォーム送信後、ユーザー情報が再設定されているか確認
        $livewire
            ->assertSet('submitted', true)
            ->assertSet('name', 'Authenticated User')
            ->assertSet('email', 'auth@example.com');
    }

    #[Test]
    public function testContactSubmissionWithException()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 無効なタイプで強制的にバリデーションエラーを起こす
        $livewire
            ->set('type', 'invalid_type') // これによりバリデーションエラーが発生
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('subject', 'Test Subject')
            ->set('message', 'This is a test message with enough characters.')
            ->call('submit')
            ->assertHasErrors('type'); // バリデーションエラーがあることを確認
    }

    #[Test]
    public function testResetForm()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 送信完了状態にする
        $livewire
            ->set('submitted', true)
            ->set('contactId', 123);

        // リセット
        $livewire
            ->call('resetForm')
            ->assertSet('submitted', false)
            ->assertSet('contactId', null);
    }

    #[Test]
    public function testLanguageSettings()
    {
        // 日本語に設定
        app()->setLocale('ja');

        $livewire = Livewire::test(ContactForm::class);

        $livewire->assertSet('language', 'ja');
    }

    #[Test]
    public function testContactTypesInView()
    {
        $livewire = Livewire::test(ContactForm::class);

        $expectedTypes = Contact::getTypes();
        $livewire->assertViewHas('contactTypes', $expectedTypes);
    }

    // TODO-049: ContactForm 機能テスト (Slack関連はスキップ)

    #[Test]
    public function testMultiLanguageSupportJapanese()
    {
        app()->setLocale('ja');

        $livewire = Livewire::test(ContactForm::class);

        $livewire
            ->set('type', 'bug_report')
            ->set('name', 'テストユーザー')
            ->set('email', 'test@example.com')
            ->set('subject', 'テスト件名')
            ->set('message', 'これは日本語でのテストメッセージです。十分な文字数があります。')
            ->call('submit');

        $this->assertDatabaseHas('contacts', [
            'type' => 'bug_report',
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'subject' => 'テスト件名',
            'message' => 'これは日本語でのテストメッセージです。十分な文字数があります。',
            'language' => 'ja',
        ]);

        $livewire->assertSet('submitted', true);
    }

    #[Test]
    public function testMultiLanguageSupportEnglish()
    {
        app()->setLocale('en');

        $livewire = Livewire::test(ContactForm::class);

        $livewire
            ->set('type', 'general_question')
            ->set('name', 'English User')
            ->set('email', 'english@example.com')
            ->set('subject', 'English Subject')
            ->set('message', 'This is an English test message with sufficient length.')
            ->call('submit');

        $this->assertDatabaseHas('contacts', [
            'type' => 'general_question',
            'name' => 'English User',
            'email' => 'english@example.com',
            'subject' => 'English Subject',
            'message' => 'This is an English test message with sufficient length.',
            'language' => 'en',
        ]);

        $livewire->assertSet('submitted', true);
    }

    #[Test]
    public function testRealTimeValidationOnType()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 無効なタイプを設定
        $livewire
            ->set('type', 'invalid_type')
            ->assertHasErrors('type');

        // 有効なタイプを設定
        $livewire
            ->set('type', 'bug_report')
            ->assertHasNoErrors('type');
    }

    #[Test]
    public function testRealTimeValidationOnName()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 空の名前を設定
        $livewire
            ->set('name', '')
            ->assertHasErrors('name');

        // 長すぎる名前を設定
        $livewire
            ->set('name', str_repeat('a', 256))
            ->assertHasErrors('name');

        // 有効な名前を設定
        $livewire
            ->set('name', 'Valid Name')
            ->assertHasNoErrors('name');
    }

    #[Test]
    public function testRealTimeValidationOnEmail()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 無効なメールを設定
        $livewire
            ->set('email', 'invalid-email')
            ->assertHasErrors('email');

        // 有効なメールを設定
        $livewire
            ->set('email', 'valid@example.com')
            ->assertHasNoErrors('email');
    }

    #[Test]
    public function testRealTimeValidationOnSubject()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 空の件名を設定
        $livewire
            ->set('subject', '')
            ->assertHasErrors('subject');

        // 有効な件名を設定
        $livewire
            ->set('subject', 'Valid Subject')
            ->assertHasNoErrors('subject');
    }

    #[Test]
    public function testRealTimeValidationOnMessage()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 短すぎるメッセージを設定
        $livewire
            ->set('message', 'short')
            ->assertHasErrors('message');

        // 有効なメッセージを設定
        $livewire
            ->set('message', 'This is a valid message with enough characters.')
            ->assertHasNoErrors('message');
    }

    #[Test]
    public function testSpecialCharactersInInput()
    {
        $livewire = Livewire::test(ContactForm::class);

        $specialMessage = 'Special chars: <script>alert("test")</script> & symbols ñáéíóú';

        $livewire
            ->set('type', 'general_question')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('subject', 'Special Characters Test')
            ->set('message', $specialMessage)
            ->call('submit');

        $this->assertDatabaseHas('contacts', [
            'type' => 'general_question',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Special Characters Test',
            'message' => $specialMessage,
        ]);

        $livewire->assertSet('submitted', true);
    }

    #[Test]
    public function testLongTextInput()
    {
        $livewire = Livewire::test(ContactForm::class);

        $longMessage = str_repeat('This is a long message. ', 50);

        $livewire
            ->set('type', 'general_question')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('subject', 'Long message test')
            ->set('message', $longMessage)
            ->call('submit');

        $this->assertDatabaseHas('contacts', [
            'type' => 'general_question',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Long message test',
            'message' => $longMessage,
        ]);

        $livewire->assertSet('submitted', true);
    }

    #[Test]
    public function testContactSubmissionFlowComplete()
    {
        $livewire = Livewire::test(ContactForm::class);

        // 完全なフォーム送信フロー
        $livewire
            ->assertSet('submitted', false)
            ->set('type', 'feature_request')
            ->set('name', 'Complete Flow User')
            ->set('email', 'complete@example.com')
            ->set('subject', 'Complete Flow Test')
            ->set('message', 'This is a complete flow test message with enough characters.')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('type', '') // フォームがリセットされている
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('subject', '')
            ->assertSet('message', '');

        // データベースに保存されている
        $this->assertDatabaseHas('contacts', [
            'type' => 'feature_request',
            'name' => 'Complete Flow User',
            'email' => 'complete@example.com',
            'subject' => 'Complete Flow Test',
            'message' => 'This is a complete flow test message with enough characters.',
            'language' => 'en',
        ]);

        // contactIdが設定されている
        $contact = Contact::where('email', 'complete@example.com')->first();
        $livewire->assertSet('contactId', $contact->id);
    }

    #[Test]
    public function testAllContactTypesAreValid()
    {
        $livewire = Livewire::test(ContactForm::class);

        $validTypes = Contact::getValidTypes();

        foreach ($validTypes as $type) {
            $livewire
                ->set('type', $type)
                ->set('name', 'Test User')
                ->set('email', 'test' . $type . '@example.com')
                ->set('subject', 'Test Subject for ' . $type)
                ->set('message', 'This is a test message for ' . $type . ' type.')
                ->call('submit');

            $this->assertDatabaseHas('contacts', [
                'type' => $type,
                'email' => 'test' . $type . '@example.com',
            ]);

            $livewire->assertSet('submitted', true);

            // フォームをリセットして次のテストの準備
            $livewire->call('resetForm');
        }
    }
}
