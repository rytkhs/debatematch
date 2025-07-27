<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'otp' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp.required' => __('validation.required', ['attribute' => __('auth.otp_code_label')]),
            'otp.string' => __('validation.string', ['attribute' => __('auth.otp_code_label')]),
            'otp.size' => __('validation.size.string', ['attribute' => __('auth.otp_code_label'), 'size' => 6]),
            'otp.regex' => __('auth.otp_invalid_format'),
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'otp' => __('auth.otp_code_label'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove any whitespace or non-numeric characters from OTP input
        if ($this->has('otp')) {
            $this->merge([
                'otp' => preg_replace('/[^0-9]/', '', $this->input('otp')),
            ]);
        }
    }
}
