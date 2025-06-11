import DebateFormManager from '../features/debate-form/index.js';

/**
 * AIディベート作成ページの初期化
 */
document.addEventListener('DOMContentLoaded', () => {
    // ページ固有の設定データを取得
    const formConfig = window.aiDebateCreateConfig || {};

    // フォーム設定を構築
    const config = {
        formType: 'ai',
        formSelector: '#ai-debate-create-form',
        ...formConfig,
    };

    // ディベートフォームマネージャーを初期化
    const debateFormManager = new DebateFormManager(config);
    debateFormManager.init();

    // グローバル参照設定（後方互換性のため）
    window.debateFormManager = debateFormManager;
});
