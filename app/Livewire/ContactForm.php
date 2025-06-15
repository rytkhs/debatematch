<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Services\ContactSlackNotifier;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContactForm extends Component
{
    public $type = '';
    public $name = '';
    public $email = '';
    public $subject = '';
    public $message = '';
    public $language;

    public $submitted = false;
    public $contactId = null;

    protected function rules()
    {
        $validTypes = implode(',', Contact::getValidTypes());

        // テスト環境ではDNSチェックを無効にする
        $emailRule = app()->environment('testing')
            ? 'required|email:rfc|max:255'
            : 'required|email:rfc,dns|max:255';

        return [
            'type' => "required|in:{$validTypes}",
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'subject' => 'required|string|max:500',
            'message' => 'required|string|min:10|max:5000',
        ];
    }

    protected function messages()
    {
        return [
            'type.required' => __('Please select a contact type'),
            'type.in' => __('Please select a valid contact type'),
            'name.required' => __('Name is required'),
            'name.max' => __('Name must not exceed 255 characters'),
            'email.required' => __('Email address is required'),
            'email.email' => __('Please enter a valid email address'),
            'email.max' => __('Email must not exceed 255 characters'),
            'subject.required' => __('Subject is required'),
            'subject.max' => __('Subject must not exceed 500 characters'),
            'message.required' => __('Message is required'),
            'message.min' => __('Message must be at least 10 characters'),
            'message.max' => __('Message must not exceed 5000 characters'),
        ];
    }

    public function mount()
    {
        $this->language = app()->getLocale();

        // ログインユーザーの情報を自動入力
        if (Auth::check()) {
            $this->name = Auth::user()->name ?? '';
            $this->email = Auth::user()->email ?? '';
        }
    }

    public function updatedType()
    {
        $this->validateOnly('type');
    }

    public function updatedName()
    {
        $this->validateOnly('name');
    }

    public function updatedEmail()
    {
        $this->validateOnly('email');
    }

    public function updatedSubject()
    {
        $this->validateOnly('subject');
    }

    public function updatedMessage()
    {
        $this->validateOnly('message');
    }

    public function submit()
    {
        $this->validate();

        try {
            // お問い合わせを保存
            $contact = Contact::create([
                'type' => $this->type,
                'name' => $this->name,
                'email' => $this->email,
                'subject' => $this->subject,
                'message' => $this->message,
                'language' => $this->language,
                'user_id' => Auth::id(),
            ]);

            $this->contactId = $contact->id;
            $this->submitted = true;

            // フォームをリセット
            $this->reset(['type', 'name', 'email', 'subject', 'message']);

            // ログインユーザーの情報は再設定
            if (Auth::check()) {
                $this->name = Auth::user()->name ?? '';
                $this->email = Auth::user()->email ?? '';
            }

            // Slack通知は別途実行（失敗してもフォーム送信は成功として扱う）
            try {
                $slackService = new ContactSlackNotifier();
                $slackService->notifyNewContact($contact);
            } catch (\Exception $slackError) {
                Log::warning('Slack notification failed for contact #' . $contact->id . ': ' . $slackError->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', __('An error occurred while submitting your contact. Please try again.'));
            Log::error('Contact form submission error: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->submitted = false;
        $this->contactId = null;
    }

    public function render()
    {
        return view('livewire.contact-form', [
            'contactTypes' => Contact::getTypes()
        ]);
    }
}
