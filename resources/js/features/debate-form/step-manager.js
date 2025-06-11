import Utils from './utils.js';

/**
 * ディベートフォームのステップ管理クラス
 */
class StepManager {
    constructor(config) {
        this.config = config;
        this.currentStep = 1;
        this.totalSteps = 2;
        this.hasUserInteracted = false;
        this.utils = new Utils();
    }

    init() {
        this.setupEventListeners();
        this.updateStepDisplay();
        this.handleFormatElementsVisibility();
        // 初期状態での検証は実行するが、エラー表示はしない
        this.validateCurrentStep(false);
    }

    setupEventListeners() {
        // ステップナビゲーション
        document.getElementById('next-to-step2')?.addEventListener('click', () => {
            this.hasUserInteracted = true;
            if (this.validateStep1(true)) {
                this.goToStep(2);
            }
        });

        document.getElementById('back-to-step1')?.addEventListener('click', () => {
            this.goToStep(1);
        });

        // リアルタイム検証
        this.attachRealtimeValidation('#step1-content');
        this.attachRealtimeValidation('#step2-content');
    }

    attachRealtimeValidation(selectorPrefix) {
        document.querySelectorAll(`${selectorPrefix} input, ${selectorPrefix} select`).forEach(field => {
            ['input', 'change'].forEach(eventType => {
                field.addEventListener(eventType, () => {
                    this.hasUserInteracted = true;
                    this.validateCurrentStep();
                });
            });
        });
    }

    goToStep(step) {
        const currentContent = document.getElementById(`step${this.currentStep}-content`);
        const nextContent = document.getElementById(`step${step}-content`);

        if (currentContent && nextContent) {
            currentContent.style.opacity = '0';
            setTimeout(() => {
                currentContent.classList.add('hidden');
                nextContent.classList.remove('hidden');
                nextContent.style.opacity = '0';
                setTimeout(() => {
                    nextContent.style.opacity = '1';
                }, 50);
            }, 300);
        }

        this.currentStep = step;
        this.updateStepDisplay();
        this.handleFormatElementsVisibility();
        this.validateCurrentStep();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    handleFormatElementsVisibility() {
        const formatPreview = document.getElementById('format-preview');
        const customFormatSettings = document.getElementById('custom-format-settings');
        const freeFormatSettings = document.getElementById('free-format-settings');

        if (this.currentStep === 1) {
            // ステップ1では全てのフォーマット関連要素を非表示
            formatPreview?.classList.add('hidden');
            customFormatSettings?.classList.add('hidden');
            freeFormatSettings?.classList.add('hidden');
        } else if (this.currentStep === 2) {
            // ステップ2では選択されたフォーマットに応じて表示を制御
            const formatTypeHidden = document.getElementById('free_format_hidden');
            const formatType = formatTypeHidden?.value || '';

            if (formatType === 'custom') {
                formatPreview?.classList.add('hidden');
                customFormatSettings?.classList.remove('hidden');
                freeFormatSettings?.classList.add('hidden');
            } else if (formatType === 'free') {
                formatPreview?.classList.add('hidden');
                customFormatSettings?.classList.add('hidden');
                freeFormatSettings?.classList.remove('hidden');
            } else if (formatType && formatType !== '') {
                // 標準フォーマットが選択されている場合
                formatPreview?.classList.remove('hidden');
                customFormatSettings?.classList.add('hidden');
                freeFormatSettings?.classList.add('hidden');
            } else {
                // フォーマットが選択されていない場合、全て非表示
                formatPreview?.classList.add('hidden');
                customFormatSettings?.classList.add('hidden');
                freeFormatSettings?.classList.add('hidden');
            }
        }
    }

    updateStepDisplay() {
        // ステップインジケーターの更新
        for (let i = 1; i <= this.totalSteps; i++) {
            const stepElement = document.getElementById(`step-${i}`);
            const isActive = i === this.currentStep;
            const isCompleted = i < this.currentStep;

            if (stepElement) {
                stepElement.classList.toggle('text-blue-600', isActive);
                stepElement.classList.toggle('border-blue-600', isActive);
                stepElement.classList.toggle('bg-blue-50', isActive);
                stepElement.classList.toggle('text-gray-500', !isActive && !isCompleted);
                stepElement.classList.toggle('border-gray-300', !isActive && !isCompleted);
                stepElement.classList.toggle('text-green-600', isCompleted);
                stepElement.classList.toggle('border-green-600', isCompleted);
                stepElement.classList.toggle('bg-green-50', isCompleted);
            }
        }

        // ナビゲーションボタンの表示/非表示
        const nextButton = document.getElementById('next-to-step2');
        const backButton = document.getElementById('back-to-step1');
        const submitButton = document.getElementById('submit-form');

        if (nextButton) nextButton.style.display = this.currentStep === 1 ? 'inline-flex' : 'none';
        if (backButton) backButton.style.display = this.currentStep === 2 ? 'inline-flex' : 'none';
        if (submitButton) submitButton.style.display = this.currentStep === 2 ? 'inline-flex' : 'none';
    }

        validateStep1(showErrors = true) {
        if (showErrors) {
            this.utils.clearFieldErrors();
        }
        let isValid = true;

        this.config.requiredFields.forEach(field => {
            const element = document.querySelector(`[name="${field.name}"]`);
            if (!element || !element.value.trim()) {
                isValid = false;
                if (showErrors && this.hasUserInteracted) {
                    this.utils.showFieldError(field.name, field.message);
                }
            }
        });

        return isValid;
    }

    validateStep2() {
        const side = document.querySelector('input[name="side"]:checked');
        const formatTypeHidden = document.getElementById('free_format_hidden');
        const formatType = formatTypeHidden?.value || '';

        // AI用の場合は evidence_allowed チェックをスキップ
        if (this.config.formType === 'ai') {
            // サイドとフォーマットが選択されているかチェック
            let formatValid = false;
            if (formatType === 'custom') {
                const customTurns = document.querySelectorAll('#custom-format-settings .turn-card');
                formatValid = customTurns.length > 0;
            } else if (formatType === 'free') {
                const freeFormatText = document.getElementById('free_format_text');
                formatValid = freeFormatText?.value.trim() !== '';
            } else {
                formatValid = formatType && formatType.trim() !== '';
            }
            return side && formatValid;
        }

        const evidence = document.querySelector('input[name="evidence_allowed"]:checked');

        // フォーマットが選択されているかチェック
        let formatValid = false;
        if (formatType === 'custom') {
            const customTurns = document.querySelectorAll('#custom-format-settings .turn-card');
            formatValid = customTurns.length > 0;
        } else if (formatType === 'free') {
            const freeFormatText = document.getElementById('free_format_text');
            formatValid = freeFormatText?.value.trim() !== '';
        } else {
            formatValid = formatType && formatType.trim() !== '';
        }

        return side && evidence && formatValid;
    }

    validateCurrentStep(showErrors = true) {
        const nextButton = document.getElementById('next-to-step2');
        const submitButton = document.getElementById('submit-form');

        if (this.currentStep === 1) {
            const isValid = this.validateStep1(showErrors);
            if (nextButton) {
                nextButton.disabled = !isValid;
                nextButton.classList.toggle('opacity-50', !isValid);
                nextButton.classList.toggle('cursor-not-allowed', !isValid);
            }
        } else if (this.currentStep === 2) {
            const isValid = this.validateStep2();
            if (submitButton) {
                submitButton.disabled = !isValid;
                submitButton.classList.toggle('opacity-50', !isValid);
                submitButton.classList.toggle('cursor-not-allowed', !isValid);
            }
        }
    }
}

export default StepManager;
