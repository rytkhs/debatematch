import StepManager from './step-manager.js';
import FormatManager from './format-manager.js';
import CustomFormatManager from './custom-format-manager.js';
import Utils from './utils.js';

/**
 * ディベートフォーム用統括マネージャー
 * rooms.create と ai.debate.create で共用
 */
class DebateFormManager {
    constructor(config = {}) {
        this.config = {
            formType: config.formType || 'room', // 'room' or 'ai'
            formSelector: config.formSelector || '#room-create-form',
            formats: config.formats || {},
            translations: config.translations || {},
            requiredFields: config.requiredFields || [],
            ...config
        };

        this.stepManager = new StepManager(this.config);
        this.formatManager = new FormatManager(this.config);
        this.customFormatManager = new CustomFormatManager(this.config);
        this.utils = new Utils();
    }

    init() {
        // グローバル参照を先に設定（マネージャー間の連携のため）
        window.stepManager = this.stepManager;
        window.formatManager = this.formatManager;

        this.stepManager.init();
        this.formatManager.init();
        this.customFormatManager.init();
        this.setupFormValidation();
        this.setupGlobalFunctions();
    }

    setupFormValidation() {
        const form = document.querySelector(this.config.formSelector);
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                    this.stepManager.goToStep(1);
                }
            });
        }
    }

    validateForm() {
        const requiredFields = document.querySelectorAll('input[required], select[required]');
        let hasEmpty = false;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                hasEmpty = true;
                this.utils.showFieldError(field.name, this.config.translations.fieldRequired);
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

    handleFormatSelectionChange(selectionType) {
        const formatSelect = document.getElementById('format_type');
        const hiddenField = document.getElementById('free_format_hidden');

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
    }

    toggleFormatPreview() {
        const content = document.getElementById('format-preview-content');
        const icon = document.querySelector('.format-preview-icon');

        if (content?.classList.contains('hidden')) {
            content.classList.remove('hidden');
            setTimeout(() => content.classList.add('opacity-100'), 10);
            if (icon) icon.textContent = 'expand_less';
        } else {
            content?.classList.remove('opacity-100');
            content?.classList.add('opacity-0');
            setTimeout(() => {
                content?.classList.add('hidden');
                if (icon) icon.textContent = 'expand_more';
            }, 200);
        }
    }

    toggleFormatHelp() {
        const content = document.getElementById('format-help-content');
        const icon = document.querySelector('.format-help-icon');

        if (content?.classList.contains('hidden')) {
            content.classList.remove('hidden');
            setTimeout(() => content.classList.add('opacity-100'), 10);
            if (icon) icon.textContent = 'expand_less';
        } else {
            content?.classList.remove('opacity-100');
            content?.classList.add('opacity-0');
            setTimeout(() => {
                content?.classList.add('hidden');
                if (icon) icon.textContent = 'expand_more';
            }, 200);
        }
    }
}

export default DebateFormManager;
