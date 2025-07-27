<?php

namespace App\Services\Room;

class FormatManager
{
    /**
     * ロケールに基づいて翻訳されたフォーマットを取得
     */
    public function getTranslatedFormats(string $locale = null): array
    {
        if ($locale === null) {
            $locale = app()->getLocale();
        }

        $rawFormats = config('debate.formats');
        $translatedFormats = [];

        // アメリカと日本のフォーマットに分ける
        $usFormats = [
            'format_name_nsda_policy' => $rawFormats['format_name_nsda_policy'],
            'format_name_nsda_ld' => $rawFormats['format_name_nsda_ld'],
            // 'format_name_npda_parliamentary' => $rawFormats['format_name_npda_parliamentary'],
        ];

        $jpFormats = [
            'format_name_nada_high' => $rawFormats['format_name_nada_high'],
            'format_name_nada_junior_high' => $rawFormats['format_name_nada_junior_high'],
            'format_name_henda' => $rawFormats['format_name_henda'],
            'format_name_coda' => $rawFormats['format_name_coda'],
            'format_name_jda' => $rawFormats['format_name_jda'],
        ];

        // ロケールに基づいて順序を決定
        $sortedFormats = $locale === 'ja'
            ? array_merge($jpFormats, $usFormats)
            : array_merge($usFormats, $jpFormats);

        foreach ($sortedFormats as $formatKey => $turns) {
            $translatedFormatName = __('debates.' . $formatKey);

            $translatedTurns = [];
            foreach ($turns as $index => $turn) {
                $translatedTurn = $turn;
                $translatedTurn['name'] = __('debates.' . $turn['name']);
                $translatedTurns[$index] = $translatedTurn;
            }

            $translatedFormats[$formatKey] = [
                'name' => $translatedFormatName,
                'turns' => $translatedTurns
            ];
        }

        return $translatedFormats;
    }

    /**
     * ロケールに基づく言語順序を取得
     */
    public function getLanguageOrder(string $locale = null): array
    {
        if ($locale === null) {
            $locale = app()->getLocale();
        }

        return $locale === 'ja'
            ? ['japanese', 'english']
            : ['english', 'japanese'];
    }

    /**
     * カスタムフォーマット設定を生成
     */
    public function generateCustomFormat(array $turns): array
    {
        $customFormatSettings = [];

        foreach ($turns as $index => $turn) {
            // 分を秒に変換
            $durationInSeconds = (int)$turn['duration'] * 60;

            $isPrepTime = isset($turn['is_prep_time']) && $turn['is_prep_time'] == '1';
            $isQuestions = isset($turn['is_questions']) && $turn['is_questions'] == '1';

            $customFormatSettings[$index + 1] = [
                'name' => $turn['name'],
                'duration' => $durationInSeconds,
                'speaker' => $turn['speaker'],
                'is_prep_time' => $isPrepTime,
                'is_questions' => $isQuestions,
            ];
        }


        return $customFormatSettings;
    }

    /**
     * フリーフォーマット設定を動的生成
     */
    public function generateFreeFormat(int $turnDuration, int $maxTurns): array
    {
        $settings = [];
        $durationInSeconds = $turnDuration * 60;

        for ($i = 1; $i <= $maxTurns; $i++) {
            $speaker = ($i % 2 === 1) ? 'affirmative' : 'negative';
            $settings[$i] = [
                'name' => 'suggestion_free_speech',
                'duration' => $durationInSeconds,
                'speaker' => $speaker,
                'is_prep_time' => false,
                'is_questions' => false,
            ];
        }

        return $settings;
    }

    /**
     * フォーマット設定を処理（RoomController対応）
     */
    public function processFormatSettings(string $formatType, array $requestData): ?array
    {
        if ($formatType === 'custom') {
            return $this->generateCustomFormat($requestData['turns']);
        } elseif ($formatType === 'free') {
            return $this->generateFreeFormat(
                $requestData['turn_duration'],
                $requestData['max_turns']
            );
        }

        return null;
    }
}
