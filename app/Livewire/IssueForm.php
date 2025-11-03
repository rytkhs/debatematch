<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Services\ContactSlackNotifier;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class IssueForm extends Component
{
    public $type = '';
    public $subject = '';
    public $message = '';
    public $language;

    public $submitted = false;
    public $contactId = null;

    protected function rules()
    {
        return [
            'type' => 'required|in:bug_report,feature_request',
            'subject' => 'required|string|max:500',
            'message' => 'required|string|min:10|max:5000',
        ];
    }

    protected function messages()
    {
        return [
            'type.required' => __('Please select an issue type'),
            'type.in' => __('Please select a valid issue type'),
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
    }

    public function updatedType()
    {
        $this->validateOnly('type');
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
            // お問い合わせを保存（nameとemailはnull）
            $contact = Contact::create([
                'type' => $this->type,
                'name' => null,
                'email' => null,
                'subject' => $this->subject,
                'message' => $this->message,
                'language' => $this->language,
                'user_id' => Auth::id(),
            ]);

            $this->contactId = $contact->id;
            $this->submitted = true;

            // フォームをリセット
            $this->reset(['type', 'subject', 'message']);

            // Slack通知は別途実行（失敗してもフォーム送信は成功として扱う）
            try {
                $slackService = new ContactSlackNotifier();
                $slackService->notifyNewContact($contact);
            } catch (\Exception $slackError) {
                Log::warning('Slack notification failed for contact #' . $contact->id . ': ' . $slackError->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', __('An error occurred while submitting your issue. Please try again.'));
            Log::error('Issue form submission error: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->submitted = false;
        $this->contactId = null;
    }

    public function getIssueTypesProperty(): array
    {
        $types = config('contact.types', []);
        $result = [];

        // バグ報告と機能リクエストのみを取得
        $allowedTypes = ['bug_report', 'feature_request'];
        foreach ($allowedTypes as $key) {
            if (isset($types[$key]) && ($types[$key]['enabled'] ?? true)) {
                $locale = app()->getLocale();
                $label = $types[$key]['label'][$locale] ?? $types[$key]['label']['en'] ?? $key;
                $emoji = $types[$key]['emoji'] ?? '';
                $result[$key] = $emoji ? "{$emoji} {$label}" : $label;
            }
        }

        return $result;
    }

    public function render()
    {
        return view('livewire.issue-form');
    }
}
