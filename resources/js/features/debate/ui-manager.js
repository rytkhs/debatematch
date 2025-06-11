/**
 * ディベートUI管理
 * ディベートページのUI機能を管理
 */
class DebateUIManager {
    constructor() {
        this.isInitialized = false;
    }

    /**
     * UI管理を初期化
     */
    initialize() {
        if (this.isInitialized) return;

        // UIモジュールの初期化
        this.initTabsModule();
        this.initSidebarModule();
        this.initModalModule();
        this.initFullscreenModule();

        this.isInitialized = true;
    }

    /**
     * タブ機能モジュール
     * デスクトップとモバイルのタブ切り替え
     */
    initTabsModule() {
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
                this.switchTab(
                    elements.timelineTab,
                    elements.participantsTab,
                    elements.timelinePanel,
                    elements.participantsPanel
                );
            });

            elements.participantsTab.addEventListener('click', () => {
                this.switchTab(
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
                this.switchTab(
                    elements.mobileTimelineTab,
                    elements.mobileParticipantsTab,
                    elements.mobileTimelinePanel,
                    elements.mobileParticipantsPanel
                );
            });

            elements.mobileParticipantsTab.addEventListener('click', () => {
                this.switchTab(
                    elements.mobileParticipantsTab,
                    elements.mobileTimelineTab,
                    elements.mobileParticipantsPanel,
                    elements.mobileTimelinePanel
                );
            });
        }
    }

    /**
     * タブ切り替え共通関数
     */
    switchTab(activeTab, inactiveTab, activePanel, inactivePanel) {
        // アクティブタブのスタイル
        activeTab.classList.add('border-b-2', 'border-primary', 'text-primary', 'font-medium');
        inactiveTab.classList.remove('border-b-2', 'border-primary', 'text-primary', 'font-medium');

        // パネル表示切替
        activePanel.classList.remove('hidden');
        activePanel.classList.add('block');
        inactivePanel.classList.remove('block');
        inactivePanel.classList.add('hidden');
    }

    /**
     * サイドバーモジュール
     * モバイル対応とサイドバー表示制御
     */
    initSidebarModule() {
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
        window.addEventListener('resize', () => this.adjustLayout(elements));

        // 初期レイアウト調整
        this.adjustLayout(elements);

        // デスクトップ用ハンバーガーメニュークリックイベント
        if (elements.desktopHamburgerBtn) {
            elements.desktopHamburgerBtn.addEventListener('click', () => this.toggleDesktopSidebar(elements));
        }

        // モバイル用ハンバーガーメニュークリックイベント
        elements.mobileHamburgerBtns.forEach(btn => {
            btn.addEventListener('click', () => this.openMobileSidebar(elements));
        });

        // 閉じるボタン（モバイル用）
        if (elements.closeMobileSidebar) {
            elements.closeMobileSidebar.addEventListener('click', () => this.closeMobileSidebar(elements));
        }

        // オーバーレイクリック（モバイル用）
        if (elements.mobileSidebarOverlay) {
            elements.mobileSidebarOverlay.addEventListener('click', (e) => {
                // イベントターゲットがオーバーレイ自身の場合のみ閉じる（コンテンツ部分のクリックでは閉じない）
                if (e.target === elements.mobileSidebarOverlay) {
                    this.closeMobileSidebar(elements);
                }
            });
        }
    }

    /**
     * デスクトップサイドバーの表示切替
     */
    toggleDesktopSidebar(elements) {
        if (elements.leftSidebar) {
            elements.leftSidebar.classList.toggle('hidden');
        }
    }

    /**
     * レイアウト調整
     */
    adjustLayout(elements) {
        const isMobileOrTablet = window.innerWidth < 768; // Tailwindのmdブレークポイント

        // leftSidebar要素が存在するか確認
        if (elements.leftSidebar) {
            if (isMobileOrTablet) {
                // モバイル/タブレットビューでは、デスクトップサイドバーを常に非表示にする
                elements.leftSidebar.classList.add('hidden');
            }
        }

        // mobileSidebarOverlay要素が存在するか確認
        if (elements.mobileSidebarOverlay) {
            if (isMobileOrTablet) {
                // モバイル/タブレット時はオーバーレイを利用可能にする
            } else {
                // デスクトップ時はオーバーレイを強制的に非表示にし、閉じる
                this.closeMobileSidebar(elements);
                elements.mobileSidebarOverlay.classList.add('hidden');
            }
        }
    }

    /**
     * モバイルサイドバーを開く
     */
    openMobileSidebar(elements) {
        if (!elements.mobileSidebarOverlay || !elements.mobileSidebarContent) return;

        // オーバーレイ表示
        elements.mobileSidebarOverlay.classList.remove('hidden');
        // 少し遅延させてから transform を適用し、アニメーションを開始
        setTimeout(() => {
            elements.mobileSidebarContent.classList.remove('-translate-x-full');
        }, 10);
    }

    /**
     * モバイルサイドバーを閉じる
     */
    closeMobileSidebar(elements) {
        if (!elements.mobileSidebarOverlay || !elements.mobileSidebarContent) return;

        // サイドバーを左にスライドアウト
        elements.mobileSidebarContent.classList.add('-translate-x-full');
        // トランジション完了後にオーバーレイを非表示にする
        setTimeout(() => {
            elements.mobileSidebarOverlay.classList.add('hidden');
        }, 300);
    }

    /**
     * モーダルモジュール
     * ヘルプモーダルなどの表示制御
     */
    initModalModule() {
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

    /**
     * 全画面表示モジュール
     */
    initFullscreenModule() {
        const fullscreenToggle = document.getElementById('fullscreen-toggle');
        const fullscreenIcon = fullscreenToggle?.querySelector('.fullscreen-icon');

        if (!fullscreenToggle) return;

        // 全画面APIのプレフィックス対応
        const fullscreenEnabled = document.fullscreenEnabled ||
                                document.webkitFullscreenEnabled ||
                                document.mozFullScreenEnabled ||
                                document.msFullscreenEnabled;

        // 全画面非対応ブラウザでは表示しない
        if (!fullscreenEnabled) {
            fullscreenToggle.style.display = 'none';
            return;
        }

        // 全画面切替処理
        fullscreenToggle.addEventListener('click', () => {
            if (document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.mozFullScreenElement ||
                document.msFullscreenElement) {
                // 全画面終了
                this.exitFullscreen();
            } else {
                // 全画面開始
                this.enterFullscreen();
            }
        });

        // 全画面状態変更イベント
        ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'msfullscreenchange'].forEach(event => {
            document.addEventListener(event, () => this.updateFullscreenButtonIcon(fullscreenIcon));
        });

        // 初期アイコン設定
        this.updateFullscreenButtonIcon(fullscreenIcon);
    }

    /**
     * 全画面表示開始
     */
    enterFullscreen() {
        const element = document.documentElement;
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
    }

    /**
     * 全画面表示終了
     */
    exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }

    /**
     * 全画面ボタンアイコンを更新
     */
    updateFullscreenButtonIcon(fullscreenIcon) {
        if (!fullscreenIcon) return;

        const isFullscreen = document.fullscreenElement ||
                           document.webkitFullscreenElement ||
                           document.mozFullScreenElement ||
                           document.msFullscreenElement;

        fullscreenIcon.textContent = isFullscreen ? 'fullscreen_exit' : 'fullscreen';
    }
}

export default DebateUIManager;
