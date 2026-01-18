<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic' => 'required|string|max:500',
            'language' => 'required|in:japanese,english',
        ];
    }

    public function messages(): array
    {
        return [
            'topic.required' => __('topic_catalog.ai.base_topic_required'),
        ];
    }

    protected function prepareForValidation()
    {
        // Allow `base_topic` input effectively mapping to `topic` for backward compat if I wanted,
        // but since we are changing frontend, let's enforce `topic`.
        // However, let's also sanitize.
        if ($this->has('topic')) {
            $this->merge([
                'topic' => $this->sanitizeInput($this->input('topic')),
            ]);
        }
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'success' => false,
            'error' => $validator->errors()->first(),
        ], 422));
    }

    private function sanitizeInput(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        $sanitized = trim($sanitized);
        return $sanitized === '' ? null : $sanitized;
    }
}
