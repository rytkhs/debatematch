<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class RoomCreationRequest extends FormRequest
{
    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'topic' => 'required|string|max:255',
            'side' => 'required|in:affirmative,negative',
            'remarks' => 'nullable|string|max:1000',
            'language' => 'required|in:japanese,english',
            'format_type' => 'required|string',
            'evidence_allowed' => 'required|boolean',
        ];

        // カスタムフォーマットの場合の追加ルール
        if ($this->input('format_type') === 'custom') {
            $rules = array_merge($rules, [
                'turns' => 'required|array|min:1',
                'turns.*.speaker' => 'required|in:affirmative,negative',
                'turns.*.name' => 'required|string|max:255',
                'turns.*.duration' => 'required|integer|min:1|max:60',
                'turns.*.is_prep_time' => 'nullable|boolean',
                'turns.*.is_questions' => 'nullable|boolean',
            ]);
        }

        // フリーフォーマットの場合の追加ルール
        if ($this->input('format_type') === 'free') {
            $rules = array_merge($rules, [
                'turn_duration' => 'required|integer|min:1|max:10',
                'max_turns' => 'required|integer|min:2|max:100',
            ]);
        }

        return $rules;
    }

    /**
     * カスタムバリデーションメッセージ
     */
    public function messages(): array
    {
        return [
            'format_type.required' => __('forms.validation.format_type.required'),
        ];
    }

    /**
     * バリデーション済みデータに追加処理を加えて返す
     */
    public function getProcessedData(): array
    {
        $validatedData = $this->validated();

        // カスタムフォーマットの場合は追加でバリデーション済みデータを取得
        if ($validatedData['format_type'] === 'custom') {
            $validatedData['turns'] = $this->input('turns');
        }

        // フリーフォーマットの場合は追加でバリデーション済みデータを取得
        if ($validatedData['format_type'] === 'free') {
            $validatedData['turn_duration'] = $this->input('turn_duration');
            $validatedData['max_turns'] = $this->input('max_turns');
        }

        return $validatedData;
    }

    /**
     * このリクエストを実行する権限があるかを判定
     */
    public function authorize(): bool
    {
        return true;
    }
}
