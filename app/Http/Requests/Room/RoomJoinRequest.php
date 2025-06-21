<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class RoomJoinRequest extends FormRequest
{
    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'side' => 'required|in:affirmative,negative',
        ];
    }

    /**
     * このリクエストを実行する権限があるかを判定
     */
    public function authorize(): bool
    {
        return true;
    }
}
