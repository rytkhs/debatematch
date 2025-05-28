<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM要素の取得
    const form = document.getElementById('filterForm');
    const filterInputs = document.querySelectorAll('select, input[type="text"]');
    const viewGridButton = document.getElementById('viewGrid');
    const viewListButton = document.getElementById('viewList');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    const resetButton = document.getElementById('resetFilters');

    // フィルター自動送信機能
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

    // フィルターリセット機能
    if (resetButton) {
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
    function switchView(activeView, inactiveView, activeButton, inactiveButton) {
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
    }

    // ビュー切り替えイベントリスナー
    if (viewGridButton && viewListButton) {
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
    const cards = document.querySelectorAll('.group');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // スムーズスクロール機能
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

    // フォーカス管理
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

    // ローディング状態の管理
    form.addEventListener('submit', function() {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ __('messages.searching') }}...
            `;
        }
    });
});
</script>

<style>
/* カスタムスタイル */
.view-toggle-btn.active {
    @apply bg-blue-100 text-blue-700;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* アニメーション */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.group {
    animation: fadeInUp 0.5s ease-out;
}

/* ホバーエフェクト */
.group:hover {
    transform: translateY(-2px);
}

/* スクロールバーのスタイリング */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
