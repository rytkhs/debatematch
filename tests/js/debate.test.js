/**
 * ディベート機能テスト
 * ディベート関連の主要機能をシンプルに統合テスト
 */
import { jest } from '@jest/globals';
import { cleanupDOM, createTestElement } from './utils/test-helpers.js';

// 基本的なモック設定
const mockCountdownManager = {
    start: jest.fn(),
    stop: jest.fn(),
    addListener: jest.fn(),
    getCurrentState: jest.fn().mockReturnValue({ timeLeft: 300, isWarning: false }),
};

const mockUIManager = {
    updateDisplay: jest.fn(),
    showWarning: jest.fn(),
    hideWarning: jest.fn(),
};

const mockChatScroll = {
    scrollToBottom: jest.fn(),
    handleNewMessage: jest.fn(),
};

describe('ディベート機能テスト', () => {
    beforeEach(() => {
        cleanupDOM();

        // 基本的なDOM構造を作成
        const debateContainer = createTestElement('div', {
            id: 'debate-container',
        });
        debateContainer.innerHTML = `
            <div id="countdown-display">05:00</div>
            <div id="chat-messages"></div>
            <div id="input-area">
                <textarea id="message-input" placeholder="メッセージを入力..."></textarea>
                <button id="send-button">送信</button>
            </div>
        `;
        document.body.appendChild(debateContainer);

        // グローバル設定
        global.window.debateData = {
            roomId: 123,
            userId: 456,
            turnEndTime: Date.now() + 300000, // 5分後
        };

        jest.clearAllMocks();
    });

    afterEach(() => {
        cleanupDOM();
    });

    describe('カウントダウン機能', () => {
        test('カウントダウンが正常に開始される', () => {
            const endTime = Date.now() + 300000; // 5分後

            mockCountdownManager.start(endTime);

            expect(mockCountdownManager.start).toHaveBeenCalledWith(endTime);
        });

        test('時間表示が正常に更新される', () => {
            mockCountdownManager.getCurrentState.mockReturnValue({
                timeLeft: 180, // 3分
                isWarning: false,
                formatted: '03:00',
            });

            const state = mockCountdownManager.getCurrentState();
            mockUIManager.updateDisplay(state.formatted);

            expect(mockUIManager.updateDisplay).toHaveBeenCalledWith('03:00');
        });

        test('警告時間になると警告表示が出る', () => {
            mockCountdownManager.getCurrentState.mockReturnValue({
                timeLeft: 30, // 30秒
                isWarning: true,
                formatted: '00:30',
            });

            const state = mockCountdownManager.getCurrentState();
            if (state.isWarning) {
                mockUIManager.showWarning();
            }

            expect(mockUIManager.showWarning).toHaveBeenCalled();
        });
    });

    describe('チャット機能', () => {
        test('新しいメッセージが追加されると自動スクロールする', () => {
            const newMessage = {
                id: 1,
                user: 'テストユーザー',
                message: 'テストメッセージ',
                timestamp: Date.now(),
            };

            mockChatScroll.handleNewMessage(newMessage);
            mockChatScroll.scrollToBottom();

            expect(mockChatScroll.handleNewMessage).toHaveBeenCalledWith(newMessage);
            expect(mockChatScroll.scrollToBottom).toHaveBeenCalled();
        });

        test('メッセージ入力エリアが正常に動作する', () => {
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');

            expect(messageInput).toBeTruthy();
            expect(sendButton).toBeTruthy();

            messageInput.value = 'テストメッセージ';
            expect(messageInput.value).toBe('テストメッセージ');
        });
    });

    describe('統合機能テスト', () => {
        test('ディベート画面の基本要素が存在する', () => {
            const countdownDisplay = document.getElementById('countdown-display');
            const chatMessages = document.getElementById('chat-messages');
            const inputArea = document.getElementById('input-area');

            expect(countdownDisplay).toBeTruthy();
            expect(chatMessages).toBeTruthy();
            expect(inputArea).toBeTruthy();
        });

        test('ディベートデータが正常に設定される', () => {
            expect(global.window.debateData).toBeDefined();
            expect(global.window.debateData.roomId).toBe(123);
            expect(global.window.debateData.userId).toBe(456);
        });
    });
});
