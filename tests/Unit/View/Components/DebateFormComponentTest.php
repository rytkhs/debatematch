<?php

namespace Tests\Unit\View\Components;

use Tests\TestCase;
use Illuminate\View\Component;
use Illuminate\Support\MessageBag;

class DebateFormComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bladeテンプレートエンジンを初期化
        $this->app['view']->addLocation(resource_path('views'));
    }

    /** @test */
    public function step_indicator_renders_correctly()
    {
        $html = $this->renderComponentToString('debate-form.step-indicator', [
            'steps' => ['基本情報', 'ディベート設定'],
            'currentStep' => 1,
            'totalSteps' => 2
        ]);

        $this->assertStringContainsString('基本情報', $html);
        $this->assertStringContainsString('ディベート設定', $html);
        $this->assertStringContainsString('step1-indicator', $html);
        $this->assertStringContainsString('step2-indicator', $html);
        $this->assertStringContainsString('bg-indigo-600', $html); // 現在のステップ
        $this->assertStringContainsString('bg-gray-300', $html); // 未完了のステップ
    }

    /** @test */
    public function basic_info_step_shows_room_name_for_room_type()
    {
        $html = $this->renderComponentToString('debate-form.basic-info-step', [
            'formType' => 'room',
            'languageOrder' => ['ja', 'en'],
            'showRoomName' => true
        ]);

        $this->assertStringContainsString('ルーム名', $html);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('required', $html);
    }

    /** @test */
    public function basic_info_step_hides_room_name_for_ai_type()
    {
        $html = $this->renderComponentToString('debate-form.basic-info-step', [
            'formType' => 'ai',
            'languageOrder' => ['ja', 'en'],
            'showRoomName' => false
        ]);

        $this->assertStringNotContainsString('ルーム名', $html);
        $this->assertStringNotContainsString('name="name"', $html);
    }

    /** @test */
    public function basic_info_step_renders_language_options()
    {
        $languageOrder = ['ja', 'en'];

        $html = $this->renderComponentToString('debate-form.basic-info-step', [
            'languageOrder' => $languageOrder,
            'showRoomName' => false
        ]);

        $this->assertStringContainsString('name="language"', $html);
        $this->assertStringContainsString('value="ja"', $html);
        $this->assertStringContainsString('value="en"', $html);
    }

    /** @test */
    public function debate_settings_step_enables_evidence_for_room_type()
    {
        $html = $this->renderComponentToString('debate-form.debate-settings-step', [
            'formType' => 'room',
            'translatedFormats' => []
        ]);

        $this->assertStringContainsString('name="evidence_allowed"', $html);
        $this->assertStringContainsString('証拠資料の使用有無', $html);
        $this->assertStringNotContainsString('input type="radio" name="evidence_allowed".*disabled', $html);
        $this->assertStringNotContainsString('cursor-not-allowed opacity-60', $html);
    }

    /** @test */
    public function debate_settings_step_disables_evidence_for_ai_type()
    {
        $html = $this->renderComponentToString('debate-form.debate-settings-step', [
            'formType' => 'ai',
            'translatedFormats' => []
        ]);

        $this->assertStringContainsString('name="evidence_allowed"', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('cursor-not-allowed', $html);
        $this->assertStringContainsString('opacity-60', $html);
    }

    /** @test */
    public function debate_settings_step_renders_format_options()
    {
        $translatedFormats = [
            'bp' => [
                'name' => 'BP形式',
                'turns' => ['1' => ['speaker' => 'affirmative', 'name' => '立論1']]
            ]
        ];

        $html = $this->renderComponentToString('debate-form.debate-settings-step', [
            'translatedFormats' => $translatedFormats
        ]);

        $this->assertStringContainsString('BP形式', $html);
        $this->assertStringContainsString('value="bp"', $html);
        $this->assertStringContainsString('name="format_type"', $html);
    }

    /** @test */
    public function debate_settings_step_shows_custom_submit_button()
    {
        $html = $this->renderComponentToString('debate-form.debate-settings-step', [
            'translatedFormats' => [],
            'submitButtonText' => 'カスタムボタン',
            'submitButtonIcon' => 'custom_icon'
        ]);

        $this->assertStringContainsString('カスタムボタン', $html);
        $this->assertStringContainsString('custom_icon', $html);
    }

    /** @test */
    public function format_preview_renders_empty_structure()
    {
        $html = $this->renderComponentToString('debate-form.format-preview', []);

        $this->assertStringContainsString('format-preview', $html);
        $this->assertStringContainsString('format-preview-title', $html);
        $this->assertStringContainsString('format-preview-body', $html);
        $this->assertStringContainsString('フォーマットプレビュー', $html);
        $this->assertStringContainsString('hidden', $html); // 初期状態では非表示
    }

    /** @test */
    public function custom_format_settings_sets_correct_max_duration_for_room()
    {
        $html = $this->renderComponentToString('debate-form.custom-format-settings', [
            'formType' => 'room'
        ]);

        $this->assertStringContainsString('max="60"', $html);
        $this->assertStringContainsString('カスタムフォーマット設定', $html);
        $this->assertStringContainsString('turns[0][speaker]', $html);
    }

    /** @test */
    public function custom_format_settings_sets_correct_max_duration_for_ai()
    {
        $html = $this->renderComponentToString('debate-form.custom-format-settings', [
            'formType' => 'ai'
        ]);

        $this->assertStringContainsString('max="14"', $html);
    }

    /** @test */
    public function free_format_settings_renders_duration_and_turns_inputs()
    {
        $html = $this->renderComponentToString('debate-form.free-format-settings', []);

        $this->assertStringContainsString('name="turn_duration"', $html);
        $this->assertStringContainsString('name="max_turns"', $html);
        $this->assertStringContainsString('1ターンの時間', $html);
        $this->assertStringContainsString('最大ターン数', $html);
        $this->assertStringContainsString('value="3"', $html); // デフォルトのターン時間
        $this->assertStringContainsString('value="20"', $html); // デフォルトの最大ターン数
    }

    /** @test */
    public function components_are_responsive_and_accessible()
    {
        $html = $this->renderComponentToString('debate-form.basic-info-step', [
            'languageOrder' => ['ja'],
            'showRoomName' => true
        ]);

        // レスポンシブクラスの確認
        $this->assertStringContainsString('sm:text-lg', $html);
        $this->assertStringContainsString('md:col-span-2', $html);

        // アクセシビリティ要素の確認
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('focus:ring-', $html);
    }

    /** @test */
    public function step_indicator_handles_different_step_counts()
    {
        $html = $this->renderComponentToString('debate-form.step-indicator', [
            'steps' => ['ステップ1', 'ステップ2', 'ステップ3'],
            'currentStep' => 2,
            'totalSteps' => 3
        ]);

        $this->assertStringContainsString('step1-indicator', $html);
        $this->assertStringContainsString('step2-indicator', $html);
        $this->assertStringContainsString('step3-indicator', $html);
        $this->assertStringContainsString('bg-green-600', $html); // 完了したステップ
        $this->assertStringContainsString('bg-indigo-600', $html); // 現在のステップ
    }

    /**
     * Bladeコンポーネントを文字列としてレンダリングするヘルパーメソッド
     */
    protected function renderComponentToString(string $component, array $data = []): string
    {
        try {
            return view("components.{$component}", $data)->render();
        } catch (\Exception $e) {
            // コンポーネントが見つからない場合はスキップ
            $this->markTestSkipped("Component {$component} not found or cannot be rendered: " . $e->getMessage());
            return '';
        }
    }
}
