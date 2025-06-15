import DOMUtils from '../../utils/dom-utils.js';

/**
 * ディベートUI管理
 * ディベートページのUI機能を管理
 * @class DebateUIManager
 */
class DebateUIManager {
    constructor() {
        this.isInitialized = false;
        this.eventListeners = new Map(); // イベントリスナー管理
    }

    /**
     * UI管理を初期化
     * @public
     */
    initialize() {
        if (this.isInitialized) return;

        try {
            // UIモジュールの初期化
            this.initTabsModule();
            this.initSidebarModule();
            this.initModalModule();
            this.initFullscreenModule();

            this.isInitialized = true;
        } catch (error) {
            console.error('[DebateUIManager] Initialization failed:', error);
        }
    }

    /**
     * タブ機能モジュール
     * デスクトップとモバイルのタブ切り替え
     * @private
     */
    initTabsModule() {
        const tabElements = this._getTabElements();
        this._bindDesktopTabEvents(tabElements);
        this._bindMobileTabEvents(tabElements);
    }

    /**
     * タブ要素の取得
     * @private
     * @returns {Object} タブ要素のオブジェクト
     */
    _getTabElements() {
        return {
            // デスクトップ用タブ
            timelineTab: DOMUtils.safeGetElement('timeline-tab', false, 'DebateUIManager'),
            participantsTab: DOMUtils.safeGetElement('participants-tab', false, 'DebateUIManager'),
            timelinePanel: DOMUtils.safeGetElement('timeline-panel', false, 'DebateUIManager'),
            participantsPanel: DOMUtils.safeGetElement(
                'participants-panel',
                false,
                'DebateUIManager'
            ),

            // モバイル用タブ
            mobileTimelineTab: DOMUtils.safeGetElement(
                'mobile-timeline-tab',
                false,
                'DebateUIManager'
            ),
            mobileParticipantsTab: DOMUtils.safeGetElement(
                'mobile-participants-tab',
                false,
                'DebateUIManager'
            ),
            mobileTimelinePanel: DOMUtils.safeGetElement(
                'mobile-timeline-panel',
                false,
                'DebateUIManager'
            ),
            mobileParticipantsPanel: DOMUtils.safeGetElement(
                'mobile-participants-panel',
                false,
                'DebateUIManager'
            ),
        };
    }

    /**
     * デスクトップタブイベントのバインド
     * @private
     * @param {Object} elements - タブ要素
     */
    _bindDesktopTabEvents(elements) {
        if (!elements.timelineTab || !elements.participantsTab) return;

        const timelineHandler = () =>
            this._switchTab(
                elements.timelineTab,
                elements.participantsTab,
                elements.timelinePanel,
                elements.participantsPanel
            );

        const participantsHandler = () =>
            this._switchTab(
                elements.participantsTab,
                elements.timelineTab,
                elements.participantsPanel,
                elements.timelinePanel
            );

        DOMUtils.safeAddEventListener(
            elements.timelineTab,
            'click',
            timelineHandler,
            false,
            'DebateUIManager'
        );
        DOMUtils.safeAddEventListener(
            elements.participantsTab,
            'click',
            participantsHandler,
            false,
            'DebateUIManager'
        );

        // イベントリスナー管理に追加
        this.eventListeners.set('desktop-timeline-tab', {
            element: elements.timelineTab,
            type: 'click',
            handler: timelineHandler,
        });
        this.eventListeners.set('desktop-participants-tab', {
            element: elements.participantsTab,
            type: 'click',
            handler: participantsHandler,
        });
    }

    /**
     * モバイルタブイベントのバインド
     * @private
     * @param {Object} elements - タブ要素
     */
    _bindMobileTabEvents(elements) {
        if (!elements.mobileTimelineTab || !elements.mobileParticipantsTab) return;

        const mobileTimelineHandler = () =>
            this._switchTab(
                elements.mobileTimelineTab,
                elements.mobileParticipantsTab,
                elements.mobileTimelinePanel,
                elements.mobileParticipantsPanel
            );

        const mobileParticipantsHandler = () =>
            this._switchTab(
                elements.mobileParticipantsTab,
                elements.mobileTimelineTab,
                elements.mobileParticipantsPanel,
                elements.mobileTimelinePanel
            );

        DOMUtils.safeAddEventListener(
            elements.mobileTimelineTab,
            'click',
            mobileTimelineHandler,
            false,
            'DebateUIManager'
        );
        DOMUtils.safeAddEventListener(
            elements.mobileParticipantsTab,
            'click',
            mobileParticipantsHandler,
            false,
            'DebateUIManager'
        );

        // イベントリスナー管理に追加
        this.eventListeners.set('mobile-timeline-tab', {
            element: elements.mobileTimelineTab,
            type: 'click',
            handler: mobileTimelineHandler,
        });
        this.eventListeners.set('mobile-participants-tab', {
            element: elements.mobileParticipantsTab,
            type: 'click',
            handler: mobileParticipantsHandler,
        });
    }

    /**
     * タブ切り替え共通関数
     * @private
     * @param {Element} activeTab - アクティブにするタブ
     * @param {Element} inactiveTab - 非アクティブにするタブ
     * @param {Element} activePanel - 表示するパネル
     * @param {Element} inactivePanel - 非表示にするパネル
     */
    _switchTab(activeTab, inactiveTab, activePanel, inactivePanel) {
        if (!activeTab || !inactiveTab || !activePanel || !inactivePanel) {
            console.warn('[DebateUIManager] Missing tab elements for switch operation');
            return;
        }

        // アクティブタブのスタイル
        DOMUtils.safeClassOperation(
            activeTab,
            'add',
            ['border-b-2', 'border-primary', 'text-primary', 'font-medium'],
            'DebateUIManager'
        );
        DOMUtils.safeClassOperation(
            inactiveTab,
            'remove',
            ['border-b-2', 'border-primary', 'text-primary', 'font-medium'],
            'DebateUIManager'
        );

        // パネル表示切替
        DOMUtils.safeClassOperation(activePanel, 'remove', 'hidden', 'DebateUIManager');
        DOMUtils.safeClassOperation(activePanel, 'add', 'block', 'DebateUIManager');
        DOMUtils.safeClassOperation(inactivePanel, 'remove', 'block', 'DebateUIManager');
        DOMUtils.safeClassOperation(inactivePanel, 'add', 'hidden', 'DebateUIManager');
    }

    /**
     * サイドバーモジュール
     * モバイル対応とサイドバー表示制御
     * @private
     */
    initSidebarModule() {
        const sidebarElements = this._getSidebarElements();
        this._setupResponsiveLayout(sidebarElements);
        this._bindSidebarEvents(sidebarElements);
    }

    /**
     * サイドバー要素の取得
     * @private
     * @returns {Object} サイドバー要素のオブジェクト
     */
    _getSidebarElements() {
        return {
            leftSidebar: DOMUtils.safeGetElement('left-sidebar', false, 'DebateUIManager'),
            desktopHamburgerBtn: DOMUtils.safeGetElement(
                'desktop-hamburger-menu',
                false,
                'DebateUIManager'
            ),
            mobileHamburgerBtns: DOMUtils.safeQuerySelectorAll(
                '#mobile-hamburger-menu',
                false,
                'DebateUIManager'
            ),
            mobileSidebarOverlay: DOMUtils.safeGetElement(
                'mobile-sidebar-overlay',
                false,
                'DebateUIManager'
            ),
            mobileSidebarContent: DOMUtils.safeGetElement(
                'mobile-sidebar-content',
                false,
                'DebateUIManager'
            ),
            closeMobileSidebar: DOMUtils.safeGetElement(
                'close-mobile-sidebar',
                false,
                'DebateUIManager'
            ),
        };
    }

    /**
     * レスポンシブレイアウトの設定
     * @private
     * @param {Object} elements - サイドバー要素
     */
    _setupResponsiveLayout(elements) {
        const resizeHandler = () => this._adjustLayout(elements);

        DOMUtils.safeAddEventListener(window, 'resize', resizeHandler, false, 'DebateUIManager');
        this.eventListeners.set('window-resize', {
            element: window,
            type: 'resize',
            handler: resizeHandler,
        });

        // 初期レイアウト調整
        this._adjustLayout(elements);
    }

    /**
     * サイドバーイベントのバインド
     * @private
     * @param {Object} elements - サイドバー要素
     */
    _bindSidebarEvents(elements) {
        // デスクトップハンバーガーメニュー
        if (elements.desktopHamburgerBtn) {
            const desktopHandler = () => this._toggleDesktopSidebar(elements);
            DOMUtils.safeAddEventListener(
                elements.desktopHamburgerBtn,
                'click',
                desktopHandler,
                false,
                'DebateUIManager'
            );
            this.eventListeners.set('desktop-hamburger', {
                element: elements.desktopHamburgerBtn,
                type: 'click',
                handler: desktopHandler,
            });
        }

        // モバイルハンバーガーメニュー
        elements.mobileHamburgerBtns.forEach((btn, index) => {
            const mobileHandler = () => this._openMobileSidebar(elements);
            DOMUtils.safeAddEventListener(btn, 'click', mobileHandler, false, 'DebateUIManager');
            this.eventListeners.set(`mobile-hamburger-${index}`, {
                element: btn,
                type: 'click',
                handler: mobileHandler,
            });
        });

        // モバイルサイドバー閉じるボタン
        if (elements.closeMobileSidebar) {
            const closeHandler = () => this._closeMobileSidebar(elements);
            DOMUtils.safeAddEventListener(
                elements.closeMobileSidebar,
                'click',
                closeHandler,
                false,
                'DebateUIManager'
            );
            this.eventListeners.set('close-mobile-sidebar', {
                element: elements.closeMobileSidebar,
                type: 'click',
                handler: closeHandler,
            });
        }

        // オーバーレイクリック
        if (elements.mobileSidebarOverlay) {
            const overlayHandler = e => {
                if (e.target === elements.mobileSidebarOverlay) {
                    this._closeMobileSidebar(elements);
                }
            };
            DOMUtils.safeAddEventListener(
                elements.mobileSidebarOverlay,
                'click',
                overlayHandler,
                false,
                'DebateUIManager'
            );
            this.eventListeners.set('mobile-overlay', {
                element: elements.mobileSidebarOverlay,
                type: 'click',
                handler: overlayHandler,
            });
        }
    }

    /**
     * デスクトップサイドバーの表示切替
     * @private
     * @param {Object} elements - サイドバー要素
     */
    _toggleDesktopSidebar(elements) {
        if (elements.leftSidebar) {
            DOMUtils.safeClassOperation(
                elements.leftSidebar,
                'toggle',
                'hidden',
                'DebateUIManager'
            );
        }
    }

    /**
     * レイアウト調整
     * @private
     * @param {Object} elements - サイドバー要素
     */
    _adjustLayout(elements) {
        const isMobileOrTablet = window.innerWidth < 768; // Tailwindのmdブレークポイント

        if (elements.leftSidebar) {
            if (isMobileOrTablet) {
                DOMUtils.safeClassOperation(
                    elements.leftSidebar,
                    'add',
                    'hidden',
                    'DebateUIManager'
                );
            }
        }

        if (elements.mobileSidebarOverlay) {
            if (!isMobileOrTablet) {
                this._closeMobileSidebar(elements);
                DOMUtils.safeClassOperation(
                    elements.mobileSidebarOverlay,
                    'add',
                    'hidden',
                    'DebateUIManager'
                );
            }
        }
    }

    /**
     * モバイルサイドバーを開く
     * @private
     * @param {Object} elements - サイドバー要素
     */
    _openMobileSidebar(elements) {
        if (!elements.mobileSidebarOverlay || !elements.mobileSidebarContent) return;

        DOMUtils.safeClassOperation(
            elements.mobileSidebarOverlay,
            'remove',
            'hidden',
            'DebateUIManager'
        );
        setTimeout(() => {
            DOMUtils.safeClassOperation(
                elements.mobileSidebarContent,
                'remove',
                '-translate-x-full',
                'DebateUIManager'
            );
        }, 10);
    }

    /**
     * モバイルサイドバーを閉じる
     * @private
     * @param {Object} elements - サイドバー要素
     */
    _closeMobileSidebar(elements) {
        if (!elements.mobileSidebarOverlay || !elements.mobileSidebarContent) return;

        DOMUtils.safeClassOperation(
            elements.mobileSidebarContent,
            'add',
            '-translate-x-full',
            'DebateUIManager'
        );
        setTimeout(() => {
            DOMUtils.safeClassOperation(
                elements.mobileSidebarOverlay,
                'add',
                'hidden',
                'DebateUIManager'
            );
        }, 300);
    }

    /**
     * モーダルモジュール
     * ヘルプモーダルなどの表示制御
     * @private
     */
    initModalModule() {
        const modalElements = this._getModalElements();
        this._bindModalEvents(modalElements);
    }

    /**
     * モーダル要素の取得
     * @private
     * @returns {Object} モーダル要素のオブジェクト
     */
    _getModalElements() {
        return {
            helpButton: DOMUtils.safeGetElement('help-button', false, 'DebateUIManager'),
            helpModal: DOMUtils.safeGetElement('help-modal', false, 'DebateUIManager'),
            closeHelp: DOMUtils.safeGetElement('close-help', false, 'DebateUIManager'),
        };
    }

    /**
     * モーダルイベントのバインド
     * @private
     * @param {Object} elements - モーダル要素
     */
    _bindModalEvents(elements) {
        // ヘルプボタン
        if (elements.helpButton && elements.helpModal) {
            const helpHandler = () =>
                DOMUtils.safeClassOperation(
                    elements.helpModal,
                    'remove',
                    'hidden',
                    'DebateUIManager'
                );
            DOMUtils.safeAddEventListener(
                elements.helpButton,
                'click',
                helpHandler,
                false,
                'DebateUIManager'
            );
            this.eventListeners.set('help-button', {
                element: elements.helpButton,
                type: 'click',
                handler: helpHandler,
            });
        }

        // 閉じるボタン
        if (elements.closeHelp && elements.helpModal) {
            const closeHandler = () =>
                DOMUtils.safeClassOperation(elements.helpModal, 'add', 'hidden', 'DebateUIManager');
            DOMUtils.safeAddEventListener(
                elements.closeHelp,
                'click',
                closeHandler,
                false,
                'DebateUIManager'
            );
            this.eventListeners.set('close-help', {
                element: elements.closeHelp,
                type: 'click',
                handler: closeHandler,
            });
        }

        // モーダル外クリック
        if (elements.helpModal) {
            const outsideHandler = e => {
                if (e.target === elements.helpModal) {
                    DOMUtils.safeClassOperation(
                        elements.helpModal,
                        'add',
                        'hidden',
                        'DebateUIManager'
                    );
                }
            };
            DOMUtils.safeAddEventListener(
                elements.helpModal,
                'click',
                outsideHandler,
                false,
                'DebateUIManager'
            );
            this.eventListeners.set('modal-outside', {
                element: elements.helpModal,
                type: 'click',
                handler: outsideHandler,
            });
        }
    }

    /**
     * 全画面表示モジュール
     * @private
     */
    initFullscreenModule() {
        const fullscreenElements = this._getFullscreenElements();

        if (!this._isFullscreenSupported()) {
            this._hideFullscreenButton(fullscreenElements.fullscreenToggle);
            return;
        }

        this._bindFullscreenEvents(fullscreenElements);
        this._updateFullscreenButtonIcon(fullscreenElements.fullscreenIcon);
    }

    /**
     * 全画面要素の取得
     * @private
     * @returns {Object} 全画面要素のオブジェクト
     */
    _getFullscreenElements() {
        const fullscreenToggle = DOMUtils.safeGetElement(
            'fullscreen-toggle',
            false,
            'DebateUIManager'
        );
        return {
            fullscreenToggle,
            fullscreenIcon: fullscreenToggle
                ? DOMUtils.safeQuerySelector('.fullscreen-icon', false, 'DebateUIManager')
                : null,
        };
    }

    /**
     * 全画面サポート確認
     * @private
     * @returns {boolean} 全画面がサポートされているか
     */
    _isFullscreenSupported() {
        return !!(
            document.fullscreenEnabled ||
            document.webkitFullscreenEnabled ||
            document.mozFullScreenEnabled ||
            document.msFullscreenEnabled
        );
    }

    /**
     * 全画面ボタンを非表示
     * @private
     * @param {Element} button - 全画面ボタン
     */
    _hideFullscreenButton(button) {
        if (button) {
            DOMUtils.safeStyleOperation(button, 'display', 'none', 'DebateUIManager');
        }
    }

    /**
     * 全画面イベントのバインド
     * @private
     * @param {Object} elements - 全画面要素
     */
    _bindFullscreenEvents(elements) {
        if (!elements.fullscreenToggle) return;

        // 全画面切替処理
        const toggleHandler = () => {
            if (this._isCurrentlyFullscreen()) {
                this._exitFullscreen();
            } else {
                this._enterFullscreen();
            }
        };

        DOMUtils.safeAddEventListener(
            elements.fullscreenToggle,
            'click',
            toggleHandler,
            false,
            'DebateUIManager'
        );
        this.eventListeners.set('fullscreen-toggle', {
            element: elements.fullscreenToggle,
            type: 'click',
            handler: toggleHandler,
        });

        // 全画面状態変更イベント
        const changeHandler = () => this._updateFullscreenButtonIcon(elements.fullscreenIcon);
        [
            'fullscreenchange',
            'webkitfullscreenchange',
            'mozfullscreenchange',
            'msfullscreenchange',
        ].forEach(event => {
            DOMUtils.safeAddEventListener(document, event, changeHandler, false, 'DebateUIManager');
            this.eventListeners.set(`fullscreen-${event}`, {
                element: document,
                type: event,
                handler: changeHandler,
            });
        });
    }

    /**
     * 現在全画面かどうか確認
     * @private
     * @returns {boolean} 全画面状態かどうか
     */
    _isCurrentlyFullscreen() {
        return !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement
        );
    }

    /**
     * 全画面表示開始
     * @private
     */
    _enterFullscreen() {
        const element = document.documentElement;
        DOMUtils.safeExecute(() => {
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        }, 'DebateUIManager');
    }

    /**
     * 全画面表示終了
     * @private
     */
    _exitFullscreen() {
        DOMUtils.safeExecute(() => {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }, 'DebateUIManager');
    }

    /**
     * 全画面ボタンアイコンを更新
     * @private
     * @param {Element} fullscreenIcon - 全画面アイコン要素
     */
    _updateFullscreenButtonIcon(fullscreenIcon) {
        if (!fullscreenIcon) return;

        const isFullscreen = this._isCurrentlyFullscreen();
        fullscreenIcon.textContent = isFullscreen ? 'fullscreen_exit' : 'fullscreen';
    }

    /**
     * クリーンアップ（メモリリーク防止）
     * @public
     */
    cleanup() {
        // 全てのイベントリスナーを削除
        this.eventListeners.forEach((listener, key) => {
            DOMUtils.safeRemoveEventListener(
                listener.element,
                listener.type,
                listener.handler,
                false,
                'DebateUIManager'
            );
        });

        this.eventListeners.clear();
        this.isInitialized = false;
    }
}

export default DebateUIManager;
