import StepManager from './step-manager.js';
import FormatManager from './format-manager.js';
import CustomFormatManager from './custom-format-manager.js';
import Utils from './utils.js';
import DOMUtils from '@/utils/dom-utils.js';

/**
 * ディベートフォーム用統括マネージャー
 * rooms.create と ai.debate.create で共用
 * @class DebateFormManager
 * @param {Object} config - 設定オブジェクト
 * @param {string} [config.formType='room'] - フォームタイプ ('room' | 'ai')
 * @param {string} [config.formSelector='#room-create-form'] - フォームセレクタ
 * @param {Object} [config.formats={}] - フォーマット設定
 * @param {Object} [config.translations={}] - 翻訳データ
 * @param {Array} [config.requiredFields=[]] - 必須フィールドリスト
 */
class DebateFormManager {
    constructor(config = {}) {
        this.config = {
            formType: config.formType || 'room', // 'room' or 'ai'
            formSelector: config.formSelector || '#room-create-form',
            formats: config.formats || {},
            translations: config.translations || {},
            requiredFields: config.requiredFields || [],
            ...config,
        };

        this.stepManager = new StepManager(this.config);
        this.formatManager = new FormatManager(this.config);
        this.customFormatManager = new CustomFormatManager(this.config);
        this.utils = new Utils();
    }

    init() {
        try {
            // グローバル参照を先に設定（マネージャー間の連携のため）
            window.stepManager = this.stepManager;
            window.formatManager = this.formatManager;

            this.stepManager.init();
            this.formatManager.init();
            this.customFormatManager.init();
            this.setupFormValidation();
            this.setupGlobalFunctions();
        } catch (error) {
            console.error('[DebateFormManager] Initialization failed:', error);
            // 初期化に失敗した場合でも、可能な限り続行
        }
    }

    setupFormValidation() {
        const form = DOMUtils.safeQuerySelector(
            this.config.formSelector,
            false,
            'DebateFormManager'
        );
        if (form) {
            DOMUtils.safeAddEventListener(
                form,
                'submit',
                e => {
                    if (!this.validateForm()) {
                        e.preventDefault();
                        this.stepManager.goToStep(1);
                    }
                },
                false,
                'DebateFormManager'
            );
        } else {
            console.warn('[DebateFormManager] Form not found:', this.config.formSelector);
        }
    }

    validateForm() {
        const requiredFields = DOMUtils.safeQuerySelectorAll(
            'input[required], select[required]',
            false,
            'DebateFormManager'
        );
        let hasEmpty = false;

        requiredFields.forEach(field => {
            if (!field.value || !field.value.trim()) {
                hasEmpty = true;
                const fieldName = field.name || field.id || 'unknown';
                const errorMessage =
                    this.config.translations.fieldRequired || 'This field is required';
                this.utils.showFieldError(fieldName, errorMessage);
            }
        });

        return !hasEmpty;
    }

    setupGlobalFunctions() {
        // グローバル関数を設定（後方互換性のため）
        window.handleFormatSelectionChange = this.handleFormatSelectionChange.bind(this);
        window.toggleFormatPreview = this.toggleFormatPreview.bind(this);
        window.toggleFormatHelp = this.toggleFormatHelp.bind(this);
    }

    /**
     * フォーマット選択変更処理
     * @public
     * @param {string} selectionType - 選択タイプ ('standard' | 'free' | 'custom')
     */
    handleFormatSelectionChange(selectionType) {
        try {
            const formatSelect = DOMUtils.safeGetElement('format_type', false, 'DebateFormManager');
            const hiddenField = DOMUtils.safeGetElement(
                'free_format_hidden',
                false,
                'DebateFormManager'
            );

            if (!formatSelect || !hiddenField) {
                console.warn(
                    '[DebateFormManager] Required elements not found for format selection'
                );
                return;
            }

            if (selectionType === 'standard') {
                formatSelect.disabled = false;
                hiddenField.value = formatSelect.value || '';
                this.formatManager.toggleCustomFormat(false);
                this.formatManager.toggleFreeFormat(false);
                this.formatManager.updateFormatPreview(formatSelect.value);
            } else if (selectionType === 'free') {
                formatSelect.disabled = true;
                formatSelect.value = '';
                hiddenField.value = 'free';
                this.formatManager.toggleCustomFormat(false);
                this.formatManager.toggleFreeFormat(true);
            } else if (selectionType === 'custom') {
                formatSelect.disabled = true;
                formatSelect.value = '';
                hiddenField.value = 'custom';
                this.formatManager.toggleFreeFormat(false);
                this.formatManager.toggleCustomFormat(true);
            }

            // 検証を実行
            this.stepManager.hasUserInteracted = true;
            this.stepManager.handleFormatElementsVisibility();
            this.stepManager.validateCurrentStep();
        } catch (error) {
            console.error('[DebateFormManager] Error in handleFormatSelectionChange:', error);
        }
    }

    /**
     * フォーマットプレビューの表示切替
     * @public
     */
    toggleFormatPreview() {
        const content = DOMUtils.safeGetElement(
            'format-preview-content',
            false,
            'DebateFormManager'
        );
        const icon = DOMUtils.safeQuerySelector('.format-preview-icon', false, 'DebateFormManager');

        if (!content) {
            console.warn('[DebateFormManager] Format preview content not found');
            return;
        }

        DOMUtils.safeExecute(() => {
            if (DOMUtils.safeClassOperation(content, 'contains', 'hidden', 'DebateFormManager')) {
                DOMUtils.safeClassOperation(content, 'remove', 'hidden', 'DebateFormManager');
                setTimeout(
                    () =>
                        DOMUtils.safeClassOperation(
                            content,
                            'add',
                            'opacity-100',
                            'DebateFormManager'
                        ),
                    10
                );
                if (icon) icon.textContent = 'expand_less';
            } else {
                DOMUtils.safeClassOperation(content, 'remove', 'opacity-100', 'DebateFormManager');
                DOMUtils.safeClassOperation(content, 'add', 'opacity-0', 'DebateFormManager');
                setTimeout(() => {
                    DOMUtils.safeClassOperation(content, 'add', 'hidden', 'DebateFormManager');
                    if (icon) icon.textContent = 'expand_more';
                }, 200);
            }
        }, 'DebateFormManager');
    }

    /**
     * フォーマットヘルプの表示切替
     * @public
     */
    toggleFormatHelp() {
        const content = DOMUtils.safeGetElement('format-help-content', false, 'DebateFormManager');
        const icon = DOMUtils.safeQuerySelector('.format-help-icon', false, 'DebateFormManager');

        if (!content) {
            console.warn('[DebateFormManager] Format help content not found');
            return;
        }

        DOMUtils.safeExecute(() => {
            if (DOMUtils.safeClassOperation(content, 'contains', 'hidden', 'DebateFormManager')) {
                DOMUtils.safeClassOperation(content, 'remove', 'hidden', 'DebateFormManager');
                setTimeout(
                    () =>
                        DOMUtils.safeClassOperation(
                            content,
                            'add',
                            'opacity-100',
                            'DebateFormManager'
                        ),
                    10
                );
                if (icon) icon.textContent = 'expand_less';
            } else {
                DOMUtils.safeClassOperation(content, 'remove', 'opacity-100', 'DebateFormManager');
                DOMUtils.safeClassOperation(content, 'add', 'opacity-0', 'DebateFormManager');
                setTimeout(() => {
                    DOMUtils.safeClassOperation(content, 'add', 'hidden', 'DebateFormManager');
                    if (icon) icon.textContent = 'expand_more';
                }, 200);
            }
        }, 'DebateFormManager');
    }
}

export default DebateFormManager;
