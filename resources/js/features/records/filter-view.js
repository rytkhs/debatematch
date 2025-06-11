/**
 * Records ページのフィルター表示機能を管理するクラス
 */
class RecordsFilterView {
    constructor() {
        this.initFilterAutoSubmit();
        this.initFilterReset();
        this.initViewToggle();
        this.initCardEffects();
        this.initSmoothScroll();
        this.initFocusManagement();
        this.initLoadingState();
    }

    // フィルター自動送信機能
    initFilterAutoSubmit() {
        const form = document.getElementById('filterForm');
        const filterInputs = document.querySelectorAll('select, input[type="text"]');

        if (!form || !filterInputs.length) return;

        filterInputs.forEach(input => {
            if (input.type === 'text') {
                // テキスト入力の場合はデバウンス処理
                let timeout;
                input.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        form.submit();
                    }, 800);
                });
            } else {
                // セレクトボックスの場合は即座に送信
                input.addEventListener('change', function() {
                    form.submit();
                });
            }
        });
    }

    // フィルターリセット機能
    initFilterReset() {
        const form = document.getElementById('filterForm');
        const resetButton = document.getElementById('resetFilters');

        if (!resetButton || !form) return;

        resetButton.addEventListener('click', function() {
            // フィルターをリセット
            document.querySelector('select[name="side"]').value = 'all';
            document.querySelector('select[name="result"]').value = 'all';
            document.querySelector('select[name="sort"]').value = 'newest';
            document.querySelector('input[name="keyword"]').value = '';

            // フォーム送信
            form.submit();
        });
    }

    // ビュー切り替え機能
    initViewToggle() {
        const viewGridButton = document.getElementById('viewGrid');
        const viewListButton = document.getElementById('viewList');
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');

        if (!viewGridButton || !viewListButton || !gridView || !listView) return;

        const switchView = (activeView, inactiveView, activeButton, inactiveButton) => {
            // ビューの切り替え
            activeView.classList.remove('hidden');
            inactiveView.classList.add('hidden');

            // ボタンのスタイル切り替え
            activeButton.classList.add('bg-blue-100', 'text-blue-700');
            activeButton.classList.remove('text-gray-600', 'hover:text-gray-800');

            inactiveButton.classList.remove('bg-blue-100', 'text-blue-700');
            inactiveButton.classList.add('text-gray-600', 'hover:text-gray-800');

            // ローカルストレージに保存
            localStorage.setItem('debateRecordsView', activeView.id);

            // アニメーション効果
            activeView.style.opacity = '0';
            activeView.style.transform = 'translateY(10px)';

            setTimeout(() => {
                activeView.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                activeView.style.opacity = '1';
                activeView.style.transform = 'translateY(0)';
            }, 10);
        };

        // ビュー切り替えイベントリスナー
        viewGridButton.addEventListener('click', function() {
            switchView(gridView, listView, viewGridButton, viewListButton);
        });

        viewListButton.addEventListener('click', function() {
            switchView(listView, gridView, viewListButton, viewGridButton);
        });

        // 保存されたビュー設定を復元
        const savedView = localStorage.getItem('debateRecordsView');
        if (savedView === 'listView') {
            switchView(listView, gridView, viewListButton, viewGridButton);
        } else {
            // デフォルトはグリッドビュー
            switchView(gridView, listView, viewGridButton, viewListButton);
        }
    }

    // カードホバーエフェクト
    initCardEffects() {
        const cards = document.querySelectorAll('.group');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    // スムーズスクロール機能
    initSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // フォーカス管理
    initFocusManagement() {
        const focusableElements = document.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        focusableElements.forEach(element => {
            element.addEventListener('focus', function() {
                this.style.outline = '2px solid #3B82F6';
                this.style.outlineOffset = '2px';
            });

            element.addEventListener('blur', function() {
                this.style.outline = '';
                this.style.outlineOffset = '';
            });
        });
    }

    // ローディング状態の管理
    initLoadingState() {
        const form = document.getElementById('filterForm');
        if (!form) return;

        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    検索中...
                `;
            }
        });
    }
}

export default RecordsFilterView;
