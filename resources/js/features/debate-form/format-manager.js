/**
 * ディベートフォーマット管理クラス
 */
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

export default FormatManager;
