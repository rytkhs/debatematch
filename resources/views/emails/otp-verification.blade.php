<x-mail::message>
{{-- 挨拶 --}}
# {{ $greeting }}

{{-- 導入文 --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- 強化されたスタイリングとセキュリティ考慮事項を含むOTPコード表示 --}}
<div style="text-align: center; margin: 30px 0;">
    <div style="background-color: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; display: inline-block; font-family: 'Courier New', monospace; max-width: 300px;">
        <div style="font-size: 11px; color: #6c757d; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">
            {{ __('auth.otp_code_display', ['code' => '']) }}
        </div>
        <div style="font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #495057; padding: 10px 0; border-top: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
            {{ $otpCode }}
        </div>
        <div style="font-size: 11px; color: #6c757d; margin-top: 8px;">
            {{ __('auth.otp_expiry_message') }}
        </div>
    </div>
</div>

{{-- 結び文 --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- 強化されたセキュリティ通知 --}}
<x-mail::panel>
**{{ __('auth.otp_security_notice') }}**

{{ __('auth.otp_no_reply') }}

---

**{{ __('common.security_tips') ?? 'Security Tips' }}:**
- {{ __('auth.otp_security_reminder') }}
- {{ __('auth.otp_single_use') ?? 'This code can only be used once' }}
- {{ __('auth.otp_no_sharing') ?? 'Never share this code via phone, email, or text' }}
</x-mail::panel>

{{-- 署名 --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards,')<br>
{{ config('app.name') }}
@endif

{{-- 追加のセキュリティ情報を含むフッター --}}
<x-slot:subcopy>
{{ __('auth.otp_email_footer', ['app' => config('app.name')]) ?? 'This email was sent by ' . config('app.name') . ' for account verification purposes.' }}
</x-slot:subcopy>
</x-mail::message>