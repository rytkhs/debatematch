import DebateFormManager from '../features/debate-form/index.js';

/**
 * ルーム作成ページの初期化
 */
document.addEventListener('DOMContentLoaded', () => {
    // ページ固有の設定データを取得
    const formConfig = window.roomCreateConfig || {};

    // フォーム設定を構築
    const config = {
        formType: 'room',
        formSelector: '#room-create-form',
        ...formConfig,
    };

    // ディベートフォームマネージャーを初期化
    const debateFormManager = new DebateFormManager(config);
    debateFormManager.init();

    // グローバル参照設定（後方互換性のため）
    window.debateFormManager = debateFormManager;
});
