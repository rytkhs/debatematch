import debounce from '../../utils/debounce.js';

/**
 * レコードページのフィルター機能を管理するクラス
 */
export class FilterController {
    constructor(formElement) {
        if (!formElement) return;
        this.form = formElement;
        this.elements = {
            mobileContainer: this.form.querySelector('[data-filter-container="mobile"]'),
            desktopContainer: this.form.querySelector('[data-filter-container="desktop"]'),
            resetButtons: this.form.querySelectorAll('[data-filter-reset]'),
            submitButtons: this.form.querySelectorAll('[data-filter-submit]'),
            autoSubmitInputs: this.form.querySelectorAll('[data-filter-autosubmit]'),
        };

        this.init();
    }

    init() {
        this.toggleDisabledState();
        this.initAutoSubmit();
        this.initReset();
        this.initLoadingState();

        window.addEventListener('resize', debounce(this.toggleDisabledState.bind(this), 200));
    }

    /**
     * 画面幅に応じてフォーム要素の disabled 状態を切り替える
     */
    toggleDisabledState() {
        // Tailwindの 'lg' ブレークポイント (1024px) に基づいて判定
        const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
        this.setInputsDisabled(this.elements.desktopContainer, !isDesktop);
        this.setInputsDisabled(this.elements.mobileContainer, isDesktop);
    }

    /**
     * 指定されたコンテナ内の入力要素の disabled 状態を設定
     */
    setInputsDisabled(container, isDisabled) {
        if (!container) return;
        container.querySelectorAll('select, input, button').forEach(el => {
            el.disabled = isDisabled;
        });
    }

    /**
     * フィルター自動送信機能の初期化
     */
    initAutoSubmit() {
        this.elements.autoSubmitInputs.forEach(input => {
            const eventType = input.type === 'text' ? 'input' : 'change';
            const handler = () => {
                if (input.disabled) return;
                this.form.submit();
            };
            const debouncedHandler = input.type === 'text' ? debounce(handler, 800) : handler;
            input.addEventListener(eventType, debouncedHandler);
        });
    }

    /**
     * フィルターリセット機能の初期化
     */
    initReset() {
        this.elements.resetButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (button.disabled) return;

                // 現在有効なコンテナを取得
                const activeContainer = window.matchMedia('(min-width: 1024px)').matches
                    ? this.elements.desktopContainer
                    : this.elements.mobileContainer;

                // フィルター要素をリセット
                const sideSelect = activeContainer.querySelector('select[name="side"]');
                const resultSelect = activeContainer.querySelector('select[name="result"]');
                const sortSelect = activeContainer.querySelector('select[name="sort"]');
                const keywordInput = activeContainer.querySelector('input[name="keyword"]');

                if (sideSelect) sideSelect.value = 'all';
                if (resultSelect) resultSelect.value = 'all';
                if (sortSelect) sortSelect.value = 'newest';
                if (keywordInput) keywordInput.value = '';

                this.form.submit();
            });
        });
    }

    /**
     * ローディング状態の管理
     */
    initLoadingState() {
        this.form.addEventListener('submit', () => {
            // 現在有効な送信ボタンを取得
            const activeSubmitButton = Array.from(this.elements.submitButtons).find(
                btn => !btn.disabled
            );
            if (activeSubmitButton) {
                activeSubmitButton.disabled = true;
                activeSubmitButton.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    適用中...
                `;
            }
        });
    }
}
