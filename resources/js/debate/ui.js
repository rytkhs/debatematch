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
            hamburgerBtns: document.querySelectorAll('#hamburger-menu'),
            mobileSidebarOverlay: document.getElementById('mobile-sidebar-overlay'),
            mobileSidebarContent: document.getElementById('mobile-sidebar-content'),
            closeMobileSidebar: document.getElementById('close-mobile-sidebar')
        };

        // ウィンドウリサイズイベント
        window.addEventListener('resize', adjustLayout);

        // 初期レイアウト調整
        adjustLayout();

        // ハンバーガーメニュークリックイベント
        elements.hamburgerBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const isMobile = window.innerWidth < 1024;
                if (isMobile) {
                    openMobileSidebar();
                } else {
                    toggleDesktopSidebar();
                }
            });
        });

        // デスクトップサイドバーの表示切替
        function toggleDesktopSidebar() {
            if (elements.leftSidebar) {
                elements.leftSidebar.classList.toggle('hidden');
            }
        }

        // レイアウト調整
        function adjustLayout() {
            const isMobile = window.innerWidth < 1024;

            if (elements.leftSidebar) {
                if (isMobile) {
                    elements.leftSidebar.classList.add('hidden');
                }
            }

            if (isMobile) {
                // モバイル時はオーバーレイを使用可能に
                if (elements.mobileSidebarOverlay) {
                    elements.mobileSidebarOverlay.classList.remove('hidden');
                }
            } else {
                // デスクトップ時はオーバーレイを非表示
                if (elements.mobileSidebarOverlay) {
                    elements.mobileSidebarOverlay.classList.add('hidden');
                }
            }
        }

        // モバイルサイドバーを開く
        function openMobileSidebar() {
            if (!elements.mobileSidebarOverlay || !elements.mobileSidebarContent) return;

            elements.mobileSidebarOverlay.classList.remove('hidden');
            setTimeout(() => {
                elements.mobileSidebarContent.classList.remove('-translate-x-full');
            }, 10);
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
