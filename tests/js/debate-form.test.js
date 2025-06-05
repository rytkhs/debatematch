/**
 * DebateFormManager ユニットテスト
 */

// テスト用にdebate-form.jsをインポート
const fs = require('fs');
const path = require('path');

// debate-form.jsの内容を読み込み
const debateFormCode = fs.readFileSync(
    path.join(process.cwd(), 'resources/js/debate-form.js'),
    'utf8'
);

// Node.js環境でコードを評価
eval(debateFormCode);

describe('DebateFormManager', () => {
    let container;
    let mockConfig;

    beforeEach(() => {
        // DOMのセットアップ
        document.body.innerHTML = '';
        container = document.createElement('div');
        container.innerHTML = `
            <form id="test-form">
                <div id="step1-content">
                    <input name="topic" required>
                    <input name="name" required>
                    <select name="language" required>
                        <option value="ja">日本語</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <div id="step2-content" class="hidden">
                    <input type="radio" name="side" value="affirmative">
                    <input type="radio" name="side" value="negative">
                    <input type="radio" name="evidence_allowed" value="1">
                    <input type="radio" name="evidence_allowed" value="0">
                    <select id="format_type">
                        <option value="">選択してください</option>
                        <option value="bp">BP形式</option>
                    </select>
                    <input type="hidden" id="free_format_hidden" name="format_type">
                </div>
                <div id="step1-indicator"></div>
                <div id="step2-indicator"></div>
                <div id="step1-text"></div>
                <div id="step2-text"></div>
                <div id="step-connector"></div>
                <button type="button" id="next-to-step2">次へ</button>
                <button type="button" id="back-to-step1">戻る</button>
                <button type="submit" id="submit-form">送信</button>
            </form>
        `;
        document.body.appendChild(container);

        mockConfig = {
            formType: 'room',
            formSelector: '#test-form',
            formats: {
                bp: {
                    name: 'BP形式',
                    turns: {
                        1: { speaker: 'affirmative', name: '立論1', duration: 420 },
                        2: { speaker: 'negative', name: '立論2', duration: 420 }
                    }
                }
            },
            translations: {
                affirmative: '肯定側',
                negative: '否定側',
                formatInfoMissing: 'フォーマット情報がありません',
                minuteSuffix: '分',
                fieldRequired: 'この項目は必須です'
            },
            requiredFields: [
                { name: 'topic', message: '論題は必須です' },
                { name: 'name', message: 'ルーム名は必須です' },
                { name: 'language', message: '言語は必須です' }
            ]
        };

        jest.clearAllTimers();
    });

    afterEach(() => {
        document.body.innerHTML = '';
        jest.runOnlyPendingTimers();
    });

    describe('初期化', () => {
        test('デフォルト設定で初期化される', () => {
            const manager = new DebateFormManager();

            expect(manager.config.formType).toBe('room');
            expect(manager.config.formSelector).toBe('#room-create-form');
            expect(manager.stepManager).toBeInstanceOf(StepManager);
            expect(manager.formatManager).toBeInstanceOf(FormatManager);
            expect(manager.customFormatManager).toBeInstanceOf(CustomFormatManager);
            expect(manager.utils).toBeInstanceOf(Utils);
        });

        test('カスタム設定で初期化される', () => {
            const manager = new DebateFormManager(mockConfig);

            expect(manager.config.formType).toBe('room');
            expect(manager.config.formSelector).toBe('#test-form');
            expect(manager.config.formats).toEqual(mockConfig.formats);
        });

        test('init()メソッドが各マネージャーを初期化する', () => {
            const manager = new DebateFormManager(mockConfig);

            const stepInitSpy = jest.spyOn(manager.stepManager, 'init');
            const formatInitSpy = jest.spyOn(manager.formatManager, 'init');
            const customFormatInitSpy = jest.spyOn(manager.customFormatManager, 'init');

            manager.init();

            expect(stepInitSpy).toHaveBeenCalled();
            expect(formatInitSpy).toHaveBeenCalled();
            expect(customFormatInitSpy).toHaveBeenCalled();
        });
    });

    describe('フォーム検証', () => {
        test('必須フィールドが空の場合はfalseを返す', () => {
            const manager = new DebateFormManager(mockConfig);
            manager.init();

            const isValid = manager.validateForm();
            expect(isValid).toBe(false);
        });

        test('必須フィールドが入力されている場合はtrueを返す', () => {
            const manager = new DebateFormManager(mockConfig);
            manager.init();

            // 必須フィールドに値を設定
            document.querySelector('[name="topic"]').value = 'テスト論題';
            document.querySelector('[name="name"]').value = 'テストルーム';
            document.querySelector('[name="language"]').value = 'ja';

            const isValid = manager.validateForm();
            expect(isValid).toBe(true);
        });

        test('フォーム送信時に検証が実行される', () => {
            const manager = new DebateFormManager(mockConfig);
            manager.init();

            const validateSpy = jest.spyOn(manager, 'validateForm').mockReturnValue(false);
            const form = document.querySelector('#test-form');

            const event = new Event('submit', { cancelable: true });
            form.dispatchEvent(event);

            expect(validateSpy).toHaveBeenCalled();
            expect(event.defaultPrevented).toBe(true);
        });
    });
});

describe('StepManager', () => {
    let stepManager;
    let mockConfig;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="step1-content">ステップ1</div>
            <div id="step2-content" class="hidden">ステップ2</div>
            <div id="step1-indicator"></div>
            <div id="step2-indicator"></div>
            <div id="step1-text"></div>
            <div id="step2-text"></div>
            <div id="step-connector"></div>
            <button id="next-to-step2">次へ</button>
            <button id="back-to-step1">戻る</button>
            <button id="submit-form">送信</button>
            <input name="topic" required>
            <input name="name" required>
            <input name="side" type="radio" value="affirmative">
            <input name="evidence_allowed" type="radio" value="1">
            <input type="hidden" id="free_format_hidden">
        `;

        mockConfig = {
            formType: 'room',
            requiredFields: [
                { name: 'topic', message: '論題は必須です' },
                { name: 'name', message: 'ルーム名は必須です' }
            ],
            translations: {
                fieldRequired: 'この項目は必須です'
            }
        };

        stepManager = new StepManager(mockConfig);
    });

    describe('ステップナビゲーション', () => {
        test('初期状態は1ステップ目', () => {
            expect(stepManager.currentStep).toBe(1);
        });

        test('goToStep()でステップが変更される', () => {
            stepManager.goToStep(2);
            expect(stepManager.currentStep).toBe(2);
        });

        test('次へボタンクリックで2ステップ目に移動', () => {
            stepManager.init();

            // 必須フィールドに値を設定
            document.querySelector('[name="topic"]').value = 'テスト論題';
            document.querySelector('[name="name"]').value = 'テストルーム';

            const nextButton = document.getElementById('next-to-step2');
            nextButton.click();

            expect(stepManager.currentStep).toBe(2);
        });

        test('戻るボタンクリックで1ステップ目に戻る', () => {
            stepManager.init();
            stepManager.goToStep(2);

            const backButton = document.getElementById('back-to-step1');
            backButton.click();

            expect(stepManager.currentStep).toBe(1);
        });
    });

    describe('検証', () => {
        test('validateStep1()で必須フィールドをチェック', () => {
            stepManager.init();

            // 空の状態でfalse
            expect(stepManager.validateStep1()).toBe(false);

            // 値を設定してtrue
            document.querySelector('[name="topic"]').value = 'テスト論題';
            document.querySelector('[name="name"]').value = 'テストルーム';
            expect(stepManager.validateStep1()).toBe(true);
        });

        test('validateStep2()でサイドと証拠資料をチェック（room形式）', () => {
            stepManager.init();

            // 未選択でfalse
            expect(stepManager.validateStep2()).toBe(false);

            // サイドのみ選択でfalse
            document.querySelector('[name="side"]').checked = true;
            expect(stepManager.validateStep2()).toBe(false);

            // 証拠資料も選択でtrue
            document.querySelector('[name="evidence_allowed"]').checked = true;
            document.getElementById('free_format_hidden').value = 'bp';
            expect(stepManager.validateStep2()).toBe(true);
        });

        test('validateStep2()でAI形式では証拠資料チェックをスキップ', () => {
            const aiConfig = { ...mockConfig, formType: 'ai' };
            const aiStepManager = new StepManager(aiConfig);
            aiStepManager.init();

            // サイドのみ選択で十分
            document.querySelector('[name="side"]').checked = true;
            document.getElementById('free_format_hidden').value = 'bp';
            expect(aiStepManager.validateStep2()).toBe(true);
        });
    });

    describe('UI更新', () => {
        test('updateStepDisplay()でステップインジケーターが更新される', () => {
            stepManager.init();
            stepManager.currentStep = 2;
            stepManager.updateStepDisplay();

            const step1Indicator = document.getElementById('step1-indicator');
            const step2Indicator = document.getElementById('step2-indicator');

            expect(step1Indicator.className).toContain('bg-green-600');
            expect(step2Indicator.className).toContain('bg-indigo-600');
        });
    });
});

describe('FormatManager', () => {
    let formatManager;
    let mockConfig;

    beforeEach(() => {
        document.body.innerHTML = `
            <input type="radio" name="side" value="affirmative">
            <input type="radio" name="side" value="negative">
            <input type="radio" name="evidence_allowed" value="1">
            <input type="radio" name="evidence_allowed" value="0">
            <select id="format_type">
                <option value="">選択してください</option>
                <option value="bp">BP形式</option>
            </select>
            <input type="hidden" id="free_format_hidden">
            <div id="format-preview" class="hidden">
                <div id="format-preview-title"></div>
                <tbody id="format-preview-body"></tbody>
            </div>
            <div id="custom-format-settings" class="hidden"></div>
            <div id="free-format-settings" class="hidden"></div>
            <label><span class="side-indicator"></span></label>
            <label><span class="evidence-indicator"></span></label>
        `;

        mockConfig = {
            formType: 'room',
            formats: {
                bp: {
                    name: 'BP形式',
                    turns: {
                        1: { speaker: 'affirmative', name: '立論1', duration: 420, is_prep_time: false, is_questions: false },
                        2: { speaker: 'negative', name: '立論2', duration: 420, is_prep_time: false, is_questions: false }
                    }
                }
            },
            translations: {
                affirmative: '肯定側',
                negative: '否定側',
                formatInfoMissing: 'フォーマット情報がありません',
                minuteSuffix: '分'
            }
        };

        formatManager = new FormatManager(mockConfig);
    });

    describe('フォーマット選択', () => {
        test('フォーマット変更時にプレビューが更新される', () => {
            formatManager.init();

            const formatSelect = document.getElementById('format_type');
            formatSelect.value = 'bp';
            formatSelect.dispatchEvent(new Event('change'));

            const hiddenField = document.getElementById('free_format_hidden');
            expect(hiddenField.value).toBe('bp');
        });

        test('カスタムフォーマット表示の切り替え', () => {
            const customSettings = document.getElementById('custom-format-settings');
            const preview = document.getElementById('format-preview');

            formatManager.toggleCustomFormat(true);
            expect(customSettings.classList.contains('hidden')).toBe(false);
            expect(preview.classList.contains('hidden')).toBe(true);

            formatManager.toggleCustomFormat(false);
            expect(customSettings.classList.contains('hidden')).toBe(true);
            expect(preview.classList.contains('hidden')).toBe(false);
        });

        test('フリーフォーマット表示の切り替え', () => {
            const freeSettings = document.getElementById('free-format-settings');
            const preview = document.getElementById('format-preview');

            formatManager.toggleFreeFormat(true);
            expect(freeSettings.classList.contains('hidden')).toBe(false);
            expect(preview.classList.contains('hidden')).toBe(true);

            formatManager.toggleFreeFormat(false);
            expect(freeSettings.classList.contains('hidden')).toBe(true);
            expect(preview.classList.contains('hidden')).toBe(false);
        });
    });

    describe('プレビュー生成', () => {
        test('updateFormatPreview()でテーブルが生成される', () => {
            formatManager.init();
            formatManager.updateFormatPreview('bp');

            const previewTitle = document.getElementById('format-preview-title');
            const previewBody = document.getElementById('format-preview-body');

            expect(previewTitle.textContent).toBe('BP形式');
            expect(previewBody.children.length).toBe(2);
        });

        test('存在しないフォーマットでエラーメッセージ表示', () => {
            formatManager.init();
            formatManager.updateFormatPreview('invalid');

            const previewTitle = document.getElementById('format-preview-title');
            expect(previewTitle.textContent).toBe('フォーマット情報がありません');
        });
    });

    describe('インジケーター更新', () => {
        test('サイド選択時にインジケーターが表示される', () => {
            formatManager.init();

            const sideRadio = document.querySelector('[name="side"]');
            const indicator = document.querySelector('.side-indicator');

            sideRadio.checked = true;
            sideRadio.dispatchEvent(new Event('change'));

            expect(indicator.style.opacity).toBe('1');
        });
    });
});

describe('Utils', () => {
    let utils;

    beforeEach(() => {
        document.body.innerHTML = `
            <div>
                <input name="test-field">
            </div>
        `;
        utils = new Utils();
    });

    describe('エラー表示', () => {
        test('showFieldError()でエラーメッセージが表示される', () => {
            const field = document.querySelector('[name="test-field"]');

            utils.showFieldError('test-field', 'エラーメッセージ');

            expect(field.classList.contains('border-red-500')).toBe(true);
            expect(field.classList.contains('bg-red-50')).toBe(true);

            const errorMessage = field.parentNode.querySelector('.error-message');
            expect(errorMessage).not.toBeNull();
            expect(errorMessage.textContent).toBe('エラーメッセージ');
        });

        test('clearFieldErrors()でエラーが削除される', () => {
            const field = document.querySelector('[name="test-field"]');

            // エラーを設定
            utils.showFieldError('test-field', 'エラーメッセージ');

            // エラーをクリア
            utils.clearFieldErrors();

            expect(field.classList.contains('border-red-500')).toBe(false);
            expect(field.classList.contains('bg-red-50')).toBe(false);

            const errorMessage = field.parentNode.querySelector('.error-message');
            expect(errorMessage).toBeNull();
        });
    });

    describe('アニメーション', () => {
        test('animateClass()で一時的にクラスが追加される', () => {
            const element = document.querySelector('[name="test-field"]');

            utils.animateClass(element, 'test-animation', 100);

            expect(element.classList.contains('test-animation')).toBe(true);

            // タイマーを進める
            jest.advanceTimersByTime(100);

            expect(element.classList.contains('test-animation')).toBe(false);
        });
    });
});

describe('グローバル関数', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <select id="format_type">
                <option value="">選択してください</option>
                <option value="bp">BP形式</option>
            </select>
            <input type="hidden" id="free_format_hidden">
            <div id="format-preview-content" class="hidden opacity-0"></div>
            <span class="format-preview-icon">expand_more</span>
            <div id="format-help-content" class="hidden opacity-0"></div>
            <span class="format-help-icon">expand_more</span>
        `;

        // グローバル変数のモック
        window.formatManager = {
            toggleCustomFormat: jest.fn(),
            toggleFreeFormat: jest.fn(),
            updateFormatPreview: jest.fn()
        };
        window.stepManager = {
            hasUserInteracted: false,
            validateCurrentStep: jest.fn()
        };
    });

    test('handleFormatSelectionChange()でstandardが選択される', () => {
        handleFormatSelectionChange('standard');

        const formatSelect = document.getElementById('format_type');
        const hiddenField = document.getElementById('free_format_hidden');

        expect(formatSelect.disabled).toBe(false);
        expect(window.formatManager.toggleCustomFormat).toHaveBeenCalledWith(false);
        expect(window.formatManager.toggleFreeFormat).toHaveBeenCalledWith(false);
    });

    test('handleFormatSelectionChange()でfreeが選択される', () => {
        handleFormatSelectionChange('free');

        const formatSelect = document.getElementById('format_type');
        const hiddenField = document.getElementById('free_format_hidden');

        expect(formatSelect.disabled).toBe(true);
        expect(hiddenField.value).toBe('free');
        expect(window.formatManager.toggleFreeFormat).toHaveBeenCalledWith(true);
    });

    test('handleFormatSelectionChange()でcustomが選択される', () => {
        handleFormatSelectionChange('custom');

        const formatSelect = document.getElementById('format_type');
        const hiddenField = document.getElementById('free_format_hidden');

        expect(formatSelect.disabled).toBe(true);
        expect(hiddenField.value).toBe('custom');
        expect(window.formatManager.toggleCustomFormat).toHaveBeenCalledWith(true);
    });

    test('toggleFormatPreview()で表示が切り替わる', () => {
        const content = document.getElementById('format-preview-content');
        const icon = document.querySelector('.format-preview-icon');

        // 表示
        toggleFormatPreview();
        expect(content.classList.contains('hidden')).toBe(false);

        // 非表示
        content.classList.remove('hidden');
        content.classList.add('opacity-100');
        toggleFormatPreview();

        jest.advanceTimersByTime(200);
        expect(content.classList.contains('hidden')).toBe(true);
    });

    test('toggleFormatHelp()で表示が切り替わる', () => {
        const content = document.getElementById('format-help-content');
        const icon = document.querySelector('.format-help-icon');

        // 表示
        toggleFormatHelp();
        expect(content.classList.contains('hidden')).toBe(false);

        // 非表示
        content.classList.remove('hidden');
        content.classList.add('opacity-100');
        toggleFormatHelp();

        jest.advanceTimersByTime(200);
        expect(content.classList.contains('hidden')).toBe(true);
    });
});
