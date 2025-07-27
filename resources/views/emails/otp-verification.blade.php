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
            @lang('Verification Code')
        </div>
        <div style="font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #495057; padding: 10px 0; border-top: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
            {{ $otpCode }}
        </div>
        <div style="font-size: 11px; color: #6c757d; margin-top: 8px;">
            @lang('This code will expire in 10 minutes.')
        </div>
    </div>
</div>

{{-- 結び文 --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- 強化されたセキュリティ通知 --}}
<x-mail::panel>
**@lang('If you did not request this verification code, please ignore this email.')**

@lang('This is an automated message. Please do not reply to this email.')

---

**@lang('Security Tips'):**
- @lang('For your security, never share this code with anyone.')
- @lang('This code can only be used once.')
- @lang('Never share this code via phone, email, or text.')
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
@lang('This email was sent by :app for account verification purposes.', ['app' => config('app.name')])
</x-slot:subcopy>
</x-mail::message>
