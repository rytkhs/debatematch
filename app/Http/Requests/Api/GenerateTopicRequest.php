<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keywords' => 'nullable|string|max:200',
            'category' => 'nullable|string|in:all,politics,business,technology,education,philosophy,entertainment,lifestyle,other',
            'difficulty' => 'nullable|string|in:all,easy,normal,hard',
            'language' => 'required|in:japanese,english',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('keywords')) {
            $keywords = $this->input('keywords');
            if (is_string($keywords)) {
                $this->merge([
                    'keywords' => $this->sanitizeInput($keywords),
                ]);
            }
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
        // Remove control characters
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        $sanitized = trim($sanitized);
        return $sanitized === '' ? null : $sanitized;
    }
}
