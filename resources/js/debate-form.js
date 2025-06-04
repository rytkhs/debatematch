/**
 * ディベートフォーム用共通JavaScript
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
}

// ユーティリティクラス
class Utils {
    showFieldError(fieldName, message) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.add('border-red-500', 'bg-red-50');

            // 既存のエラーメッセージを削除
            const existingError = field.parentNode.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-600 text-xs mt-1 error-message animate-pulse';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }
    }

    clearFieldErrors() {
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500', 'bg-red-50');
        });
    }

    animateClass(element, className, duration = 300) {
        element.classList.add(className);
        setTimeout(() => element.classList.remove(className), duration);
    }
}

// ステップ管理クラス
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
        this.validateCurrentStep();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    updateStepDisplay() {
        for (let i = 1; i <= this.totalSteps; i++) {
            const indicator = document.getElementById(`step${i}-indicator`);
            const text = document.getElementById(`step${i}-text`);
            const connector = document.getElementById('step-connector');

            if (indicator && text) {
                if (i === this.currentStep) {
                    indicator.className = 'step-indicator w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-medium transition-colors duration-300';
                    text.className = 'ml-2 text-sm font-medium text-indigo-600 transition-colors duration-300';
                    indicator.textContent = i;
                } else if (i < this.currentStep) {
                    indicator.className = 'step-indicator w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-medium transition-colors duration-300';
                    text.className = 'ml-2 text-sm font-medium text-green-600 transition-colors duration-300';
                    indicator.innerHTML = '<span class="material-icons-outlined text-sm">check</span>';
                } else {
                    indicator.className = 'step-indicator w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium transition-colors duration-300';
                    text.className = 'ml-2 text-sm font-medium text-gray-500 transition-colors duration-300';
                    indicator.textContent = i;
                }
            }

            if (connector && this.currentStep > 1) {
                connector.className = 'w-8 h-0.5 bg-green-300 transition-colors duration-300';
            }
        }
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
        const formatType = document.getElementById('free_format_hidden')?.value;

        // AI用の場合は evidence_allowed チェックをスキップ
        if (this.config.formType === 'ai') {
            // フォーマットが選択されているかチェック（空文字列は無効）
            return side && formatType && formatType.trim() !== '';
        }

        const evidence = document.querySelector('input[name="evidence_allowed"]:checked');
        // フォーマットが選択されているかチェック（空文字列は無効）
        return side && evidence && formatType && formatType.trim() !== '';
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

// フォーマット管理クラス
class FormatManager {
    constructor(config) {
        this.config = config;
    }

    init() {
        this.setupEventListeners();
        this.initializeFromOldValues();
        // 初期化後の検証はエラー表示なしで実行
        this.triggerValidation(false);
    }

    setupEventListeners() {
        // サイド選択
        const sideSelector = this.config.formType === 'ai' ? 'input[name="side"]' : 'input[name="side"], input[name="evidence_allowed"]';
        document.querySelectorAll(sideSelector).forEach(radio => {
            radio.addEventListener('change', () => {
                this.updateIndicators();
                this.triggerValidation();
            });
        });

        // フォーマット選択
        document.getElementById('format_type')?.addEventListener('change', (e) => {
            document.getElementById('free_format_hidden').value = e.target.value;
            this.toggleCustomFormat(e.target.value === 'custom');
            this.toggleFreeFormat(e.target.value === 'free');
            this.updateFormatPreview(e.target.value);
            this.triggerValidation();
        });
    }

    initializeFromOldValues() {
        this.updateIndicators();

        // フォーマットタイプの初期化処理はサーバーサイドのold()値に依存
        const formatTypeElement = document.getElementById('format_type');
        if (formatTypeElement) {
            this.updateFormatPreview(formatTypeElement.value);
        }
    }

    updateIndicators() {
        document.querySelectorAll('.side-indicator, .evidence-indicator').forEach(indicator => {
            indicator.style.opacity = '0';
        });

        const checkedSide = document.querySelector('input[name="side"]:checked');
        const checkedEvidence = document.querySelector('input[name="evidence_allowed"]:checked');

        if (checkedSide) {
            checkedSide.closest('label').querySelector('.side-indicator').style.opacity = '1';
        }
        if (checkedEvidence) {
            checkedEvidence.closest('label').querySelector('.evidence-indicator').style.opacity = '1';
        }
    }

    toggleCustomFormat(show) {
        const element = document.getElementById('custom-format-settings');
        const preview = document.getElementById('format-preview');

        if (show) {
            element?.classList.remove('hidden');
            preview?.classList.add('hidden');
        } else {
            element?.classList.add('hidden');
            preview?.classList.remove('hidden');
        }
    }

    toggleFreeFormat(show) {
        const element = document.getElementById('free-format-settings');
        const preview = document.getElementById('format-preview');

        if (show) {
            element?.classList.remove('hidden');
            preview?.classList.add('hidden');
        } else {
            element?.classList.add('hidden');
            preview?.classList.remove('hidden');
        }
    }

    updateFormatPreview(formatKey) {
        if (!formatKey || formatKey === 'custom' || formatKey === 'free') return;

        const previewBody = document.getElementById('format-preview-body');
        const previewTitle = document.getElementById('format-preview-title');

        if (!previewBody || !previewTitle) return;

        previewBody.innerHTML = '';

        if (!this.config.formats[formatKey] || !this.config.formats[formatKey].turns) {
            previewTitle.textContent = this.config.translations.formatInfoMissing;
            previewBody.innerHTML = `<tr><td colspan="4" class="px-3 py-2 text-sm text-gray-500">${this.config.translations.formatInfoMissing}</td></tr>`;
            return;
        }

        previewTitle.textContent = this.config.formats[formatKey].name;

        Object.entries(this.config.formats[formatKey].turns).forEach(([index, turn]) => {
            const row = this.createPreviewRow(index, turn);
            previewBody.appendChild(row);
        });
    }

    createPreviewRow(index, turn) {
        const row = document.createElement('tr');
        const speakerInfo = this.getSpeakerInfo(turn.speaker);
        const typeIcon = this.getTypeIcon(turn);

        row.className = speakerInfo.bgClass;
        row.innerHTML = `
            <td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700">${index}</td>
            <td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm">
                <span class="px-2 py-0.5 inline-flex items-center rounded-full ${speakerInfo.badgeClass} ${speakerInfo.textClass} text-xs font-medium">
                    ${speakerInfo.text}
                </span>
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700 flex items-center">
                ${typeIcon}${turn.name}
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700">
                ${turn.duration / 60}${this.config.translations.minuteSuffix}
            </td>
        `;
        return row;
    }

    getSpeakerInfo(speaker) {
        const speakerMap = {
            'affirmative': {
                text: this.config.translations.affirmative,
                bgClass: 'bg-green-50',
                textClass: 'text-green-800',
                badgeClass: 'bg-green-100'
            },
            'negative': {
                text: this.config.translations.negative,
                bgClass: 'bg-red-50',
                textClass: 'text-red-800',
                badgeClass: 'bg-red-100'
            }
        };

        return speakerMap[speaker] || {
            text: speaker,
            bgClass: 'bg-gray-50',
            textClass: 'text-gray-800',
            badgeClass: 'bg-gray-100'
        };
    }

    getTypeIcon(turn) {
        if (turn.is_prep_time) {
            return '<span class="material-icons-outlined text-xs mr-1 text-gray-500">timer</span>';
        } else if (turn.is_questions) {
            return '<span class="material-icons-outlined text-xs mr-1 text-gray-500">help</span>';
        }
        return '';
    }

    triggerValidation(showErrors = true) {
        // stepManagerの検証を呼び出し
        if (window.stepManager) {
            if (showErrors) {
                window.stepManager.hasUserInteracted = true;
            }
            window.stepManager.validateCurrentStep(showErrors);
        }
    }
}

// カスタムフォーマット管理クラス
class CustomFormatManager {
    constructor(config) {
        this.config = config;
        this.turnCount = 0;
        this.turnsContainer = null;
        this.maxDuration = config.formType === 'ai' ? 14 : 60;
    }

    init() {
        this.turnsContainer = document.getElementById('turns-container');
        this.turnCount = this.turnsContainer ? this.turnsContainer.children.length : 1;
        this.setupEventListeners();
        this.initializeExistingTurns();
    }

    setupEventListeners() {
        document.getElementById('add-turn')?.addEventListener('click', () => {
            this.addTurn();
        });
    }

    initializeExistingTurns() {
        if (this.turnsContainer) {
            this.attachDeleteListeners();
            this.turnsContainer.querySelectorAll('.turn-card').forEach(card => {
                this.attachInputListenersToElement(card);
            });
        }
    }

    addTurn() {
        if (!this.turnsContainer) return;

        const turnHtml = this.createTurnHtml(this.turnCount);
        const newTurn = document.createElement('div');
        newTurn.className = 'turn-card border rounded-lg p-3 sm:p-4 bg-white shadow-sm hover:shadow-md transition-shadow';
        newTurn.innerHTML = turnHtml;

        this.turnsContainer.appendChild(newTurn);
        this.turnCount++;

        this.attachDeleteListeners();
        this.attachInputListenersToElement(newTurn);
    }

    createTurnHtml(index) {
        return `
            <div class="flex justify-between items-center mb-2 sm:mb-3">
                <div class="flex items-center">
                    <span class="turn-number w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-medium text-xs sm:text-sm">${index + 1}</span>
                    <h4 class="turn-title text-xs sm:text-sm font-medium ml-2 text-gray-700">${this.config.translations.part} ${index + 1}</h4>
                </div>
                <button type="button" class="delete-turn text-gray-400 hover:text-red-500 transition-colors">
                    <span class="material-icons-outlined text-sm sm:text-base">delete</span>
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                <div class="sm:col-span-3">
                    <label class="block text-xs text-gray-500">${this.config.translations.side}</label>
                    <select name="turns[${index}][speaker]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                        <option value="affirmative">${this.config.translations.affirmative}</option>
                        <option value="negative">${this.config.translations.negative}</option>
                    </select>
                </div>
                <div class="sm:col-span-5">
                    <label class="block text-xs text-gray-500">${this.config.translations.partName}</label>
                    <input type="text" name="turns[${index}][name]" placeholder="${this.config.translations.placeholderPartName}" list="part-suggestions" class="part-name mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500">${this.config.translations.durationMinutes}</label>
                    <input type="number" name="turns[${index}][duration]" value="5" min="1" max="${this.maxDuration}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                </div>
                <div class="sm:col-span-2 flex flex-col justify-end">
                    <div class="flex items-center space-x-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="turns[${index}][is_questions]" value="1" class="question-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                            <span class="ml-1 text-xs text-gray-500">${this.config.translations.questionTime}</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="turns[${index}][is_prep_time]" value="1" class="prep-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                            <span class="ml-1 text-xs text-gray-500">${this.config.translations.prepTime}</span>
                        </label>
                    </div>
                </div>
            </div>
        `;
    }

    attachDeleteListeners() {
        document.querySelectorAll('.delete-turn').forEach(button => {
            button.replaceWith(button.cloneNode(true));
        });

        document.querySelectorAll('.delete-turn').forEach(button => {
            button.addEventListener('click', (event) => {
                if (this.turnsContainer.children.length > 1) {
                    event.target.closest('.turn-card').remove();
                    this.updateTurnNumbersAndNames();
                } else {
                    const turnCard = event.target.closest('.turn-card');
                    turnCard.classList.add('border-red-500', 'animate-pulse');
                    setTimeout(() => {
                        turnCard.classList.remove('border-red-500', 'animate-pulse');
                    }, 1000);
                }
            });
        });
    }

    attachInputListenersToElement(element) {
        // パート名、チェックボックス、サイド選択のリスナー設定
        const partNameInput = element.querySelector('.part-name');
        const prepTimeCheckbox = element.querySelector('.prep-time-checkbox');
        const questionTimeCheckbox = element.querySelector('.question-time-checkbox');
        const speakerSelect = element.querySelector('select[name*="[speaker]"]');

        if (partNameInput) {
            partNameInput.addEventListener('input', (e) => this.handlePartNameInput(e));
        }

        [prepTimeCheckbox, questionTimeCheckbox].forEach(checkbox => {
            if (checkbox) {
                checkbox.addEventListener('change', (e) => this.handleCheckboxChange(e));
            }
        });

        if (speakerSelect) {
            speakerSelect.addEventListener('change', (e) => {
                const turnCard = e.target.closest('.turn-card');
                this.updateTurnCardBackground(turnCard, e.target.value);
            });
            this.updateTurnCardBackground(element, speakerSelect.value);
        }
    }

    updateTurnNumbersAndNames() {
        const turns = this.turnsContainer.querySelectorAll('.turn-card');
        turns.forEach((turn, index) => {
            const displayTurnNumber = index + 1;
            const numberDisplay = turn.querySelector('.turn-number');
            const titleDisplay = turn.querySelector('.turn-title');

            if (numberDisplay) numberDisplay.textContent = displayTurnNumber;
            if (titleDisplay) titleDisplay.textContent = `${this.config.translations.part} ${displayTurnNumber}`;

            turn.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/turns\[\d+\]/, `turns[${index}]`);
                    input.setAttribute('name', newName);
                }
            });

            const speakerSelect = turn.querySelector('select[name*="[speaker]"]');
            if (speakerSelect) {
                this.updateTurnCardBackground(turn, speakerSelect.value);
            }
        });
        this.turnCount = turns.length;
    }

    updateTurnCardBackground(turnCard, speakerValue) {
        turnCard.classList.remove('bg-green-50', 'bg-red-50', 'bg-white');

        if (speakerValue === 'affirmative') {
            turnCard.classList.add('bg-green-50');
        } else if (speakerValue === 'negative') {
            turnCard.classList.add('bg-red-50');
        } else {
            turnCard.classList.add('bg-white');
        }
    }

    handlePartNameInput(event) {
        const turnCard = event.target.closest('.turn-card');
        if (!turnCard) return;

        const partNameInput = event.target;
        const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
        const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

        if (!prepTimeCheckbox || !questionTimeCheckbox) return;

        if (partNameInput.value.trim() === this.config.translations.prepTimeSuggestion) {
            prepTimeCheckbox.checked = true;
            questionTimeCheckbox.checked = false;
        } else if (partNameInput.value.trim() === this.config.translations.questionTimeSuggestion) {
            questionTimeCheckbox.checked = true;
            prepTimeCheckbox.checked = false;
        }
    }

    handleCheckboxChange(event) {
        const turnCard = event.target.closest('.turn-card');
        if (!turnCard) return;

        const partNameInput = turnCard.querySelector('.part-name');
        const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
        const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

        if (!partNameInput || !prepTimeCheckbox || !questionTimeCheckbox) return;

        const isPrepTime = event.target === prepTimeCheckbox;
        const isQuestionTime = event.target === questionTimeCheckbox;

        if (isPrepTime && event.target.checked) {
            partNameInput.value = this.config.translations.prepTimeSuggestion;
            questionTimeCheckbox.checked = false;
        } else if (isQuestionTime && event.target.checked) {
            partNameInput.value = this.config.translations.questionTimeSuggestion;
            prepTimeCheckbox.checked = false;
        } else if (
            (isPrepTime && !event.target.checked && partNameInput.value === this.config.translations.prepTimeSuggestion) ||
            (isQuestionTime && !event.target.checked && partNameInput.value === this.config.translations.questionTimeSuggestion)
        ) {
            partNameInput.value = '';
        }
    }
}

// グローバル関数（後方互換性のため）
function handleFormatSelectionChange(selectionType) {
    const formatSelect = document.getElementById('format_type');
    const hiddenField = document.getElementById('free_format_hidden');

    if (selectionType === 'standard') {
        formatSelect.disabled = false;
        hiddenField.value = formatSelect.value || '';
        window.formatManager?.toggleCustomFormat(false);
        window.formatManager?.toggleFreeFormat(false);
        window.formatManager?.updateFormatPreview(formatSelect.value);
    } else if (selectionType === 'free') {
        formatSelect.disabled = true;
        formatSelect.value = '';
        hiddenField.value = 'free';
        window.formatManager?.toggleCustomFormat(false);
        window.formatManager?.toggleFreeFormat(true);
    } else if (selectionType === 'custom') {
        formatSelect.disabled = true;
        formatSelect.value = '';
        hiddenField.value = 'custom';
        window.formatManager?.toggleFreeFormat(false);
        window.formatManager?.toggleCustomFormat(true);
    }

    // 検証を実行
    if (window.stepManager) {
        window.stepManager.hasUserInteracted = true;
        window.stepManager.validateCurrentStep();
    }
}

function toggleFormatPreview() {
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

function toggleFormatHelp() {
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

// エクスポート
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DebateFormManager, StepManager, FormatManager, CustomFormatManager, Utils };
}

// Vite環境でグローバルスコープに公開
if (typeof window !== 'undefined') {
    window.DebateFormManager = DebateFormManager;
    window.StepManager = StepManager;
    window.FormatManager = FormatManager;
    window.CustomFormatManager = CustomFormatManager;
    window.Utils = Utils;

    // グローバル関数も公開
    window.handleFormatSelectionChange = handleFormatSelectionChange;
    window.toggleFormatPreview = toggleFormatPreview;
    window.toggleFormatHelp = toggleFormatHelp;
}
