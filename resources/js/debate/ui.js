/**
 * DebateMatch UI Core Module
 * ディベートインターフェイスのコア機能
 */
document.addEventListener('DOMContentLoaded', function() {
    // UIモジュールの初期化
    initTabsModule();
    initSidebarModule();
    initModalModule();

    /**
     * タブ機能モジュール
     * デスクトップとモバイルのタブ切り替え
     */
    function initTabsModule() {
        // タブ要素の取得
        const elements = {
            // デスクトップ用タブ
            timelineTab: document.getElementById('timeline-tab'),
            participantsTab: document.getElementById('participants-tab'),
            timelinePanel: document.getElementById('timeline-panel'),
            participantsPanel: document.getElementById('participants-panel'),

            // モバイル用タブ
            mobileTimelineTab: document.getElementById('mobile-timeline-tab'),
            mobileParticipantsTab: document.getElementById('mobile-participants-tab'),
            mobileTimelinePanel: document.getElementById('mobile-timeline-panel'),
            mobileParticipantsPanel: document.getElementById('mobile-participants-panel')
        };

        // デスクトップタブ切り替え
        if (elements.timelineTab && elements.participantsTab) {
            elements.timelineTab.addEventListener('click', () => {
                switchTab(
                    elements.timelineTab,
                    elements.participantsTab,
                    elements.timelinePanel,
                    elements.participantsPanel
                );
            });

            elements.participantsTab.addEventListener('click', () => {
                switchTab(
                    elements.participantsTab,
                    elements.timelineTab,
                    elements.participantsPanel,
                    elements.timelinePanel
                );
            });
        }

        // モバイルタブ切り替え
        if (elements.mobileTimelineTab && elements.mobileParticipantsTab) {
            elements.mobileTimelineTab.addEventListener('click', () => {
                switchTab(
                    elements.mobileTimelineTab,
                    elements.mobileParticipantsTab,
                    elements.mobileTimelinePanel,
                    elements.mobileParticipantsPanel
                );
            });

            elements.mobileParticipantsTab.addEventListener('click', () => {
                switchTab(
                    elements.mobileParticipantsTab,
                    elements.mobileTimelineTab,
                    elements.mobileParticipantsPanel,
                    elements.mobileTimelinePanel
                );
            });
        }

        // タブ切り替え共通関数
        function switchTab(activeTab, inactiveTab, activePanel, inactivePanel) {
            // アクティブタブのスタイル
            activeTab.classList.add('border-b-2', 'border-primary', 'text-primary', 'font-medium');
            inactiveTab.classList.remove('border-b-2', 'border-primary', 'text-primary', 'font-medium');

            // パネル表示切替
            activePanel.classList.remove('hidden');
            activePanel.classList.add('block');
            inactivePanel.classList.remove('block');
            inactivePanel.classList.add('hidden');
        }
    }
  /**
     * サイドバーモジュール
     * モバイル対応とサイドバー表示制御
     */
    function initSidebarModule() {
        // DOM要素の取得
        const elements = {
            leftSidebar: document.getElementById('left-sidebar'),
            // デスクトップ用とモバイル用でIDを分ける
            desktopHamburgerBtn: document.getElementById('desktop-hamburger-menu'),
            mobileHamburgerBtns: document.querySelectorAll('#mobile-hamburger-menu'), // モバイル用は複数の場所に存在する可能性があるためquerySelectorAllを使用
            mobileSidebarOverlay: document.getElementById('mobile-sidebar-overlay'),
            mobileSidebarContent: document.getElementById('mobile-sidebar-content'),
            closeMobileSidebar: document.getElementById('close-mobile-sidebar')
        };

        // ウィンドウリサイズイベント
        window.addEventListener('resize', adjustLayout);

        // 初期レイアウト調整
        adjustLayout();

        // デスクトップ用ハンバーガーメニュークリックイベント
        if (elements.desktopHamburgerBtn) {
            elements.desktopHamburgerBtn.addEventListener('click', toggleDesktopSidebar);
        }

        // モバイル用ハンバーガーメニュークリックイベント
        elements.mobileHamburgerBtns.forEach(btn => {
            btn.addEventListener('click', openMobileSidebar);
        });

        // 閉じるボタン（モバイル用）
        if (elements.closeMobileSidebar) {
            elements.closeMobileSidebar.addEventListener('click', closeMobileSidebar);
        }

        // オーバーレイクリック（モバイル用）
        if (elements.mobileSidebarOverlay) {
            elements.mobileSidebarOverlay.addEventListener('click', (e) => {
                // イベントターゲットがオーバーレイ自身の場合のみ閉じる（コンテンツ部分のクリックでは閉じない）
                if (e.target === elements.mobileSidebarOverlay) {
                    closeMobileSidebar();
                }
            });
        }

        // デスクトップサイドバーの表示切替
        function toggleDesktopSidebar() {
            if (elements.leftSidebar) {
                elements.leftSidebar.classList.toggle('hidden');
            }
        }

        // レイアウト調整
        function adjustLayout() {
            const isMobileOrTablet = window.innerWidth < 768; // Tailwindのmdブレークポイント

            // leftSidebar要素が存在するか確認
            if (elements.leftSidebar) {
                if (isMobileOrTablet) {
                    // モバイル/タブレットビューでは、デスクトップサイドバーを常に非表示にする
                    elements.leftSidebar.classList.add('hidden');
                } else {

                }
            }

            // mobileSidebarOverlay要素が存在するか確認
            if (elements.mobileSidebarOverlay) {
                if (isMobileOrTablet) {
                    // モバイル/タブレット時はオーバーレイを利用可能にする
                } else {
                    // デスクトップ時はオーバーレイを強制的に非表示にし、閉じる
                    closeMobileSidebar();
                    elements.mobileSidebarOverlay.classList.add('hidden');
                }
            }
        }

        // モバイルサイドバーを開く
        function openMobileSidebar() {
            if (!elements.mobileSidebarOverlay || !elements.mobileSidebarContent) return;

            // オーバーレイ表示
            elements.mobileSidebarOverlay.classList.remove('hidden');
            // 少し遅延させてから transform を適用し、アニメーションを開始
            setTimeout(() => {
                elements.mobileSidebarContent.classList.remove('-translate-x-full');
            }, 10);
        }

        // モバイルサイドバーを閉じる
        function closeMobileSidebar() {
            if (!elements.mobileSidebarOverlay || !elements.mobileSidebarContent) return;

            // サイドバーを左にスライドアウト
            elements.mobileSidebarContent.classList.add('-translate-x-full');
            // トランジション完了後にオーバーレイを非表示にする
            setTimeout(() => {
                elements.mobileSidebarOverlay.classList.add('hidden');
            }, 300);
        }
    }
    /**
     * モーダルモジュール
     * ヘルプモーダルなどの表示制御
     */
    function initModalModule() {
        // DOM要素の取得
        const elements = {
            helpButton: document.getElementById('help-button'),
            helpModal: document.getElementById('help-modal'),
            closeHelp: document.getElementById('close-help')
        };

        // ヘルプボタン
        if (elements.helpButton && elements.helpModal) {
            elements.helpButton.addEventListener('click', () => {
                elements.helpModal.classList.remove('hidden');
            });
        }

        // 閉じるボタン
        if (elements.closeHelp && elements.helpModal) {
            elements.closeHelp.addEventListener('click', () => {
                elements.helpModal.classList.add('hidden');
            });
        }

        // モーダル外クリック
        if (elements.helpModal) {
            elements.helpModal.addEventListener('click', (e) => {
                if (e.target === elements.helpModal) {
                    elements.helpModal.classList.add('hidden');
                }
            });
        }
    }
});
