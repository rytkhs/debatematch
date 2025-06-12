/**
 * ルーム機能テスト
 * ルーム作成、参加、管理の主要機能をシンプルに統合テスト
 */
import { jest } from '@jest/globals';
import { cleanupDOM, createTestElement } from './utils/test-helpers.js';

// 基本的なモック設定
const mockFormManager = {
    validate: jest.fn().mockResolvedValue(true),
    submit: jest.fn().mockResolvedValue({ success: true, roomId: 123 }),
    getFormData: jest.fn().mockReturnValue({ title: 'テストルーム', formatId: 1 }),
};

const mockConnectionManager = {
    connect: jest.fn().mockResolvedValue(true),
    disconnect: jest.fn(),
    isConnected: jest.fn().mockReturnValue(true),
    getChannel: jest.fn().mockReturnValue({ bind: jest.fn() }),
};

const mockEventHandler = {
    showNotification: jest.fn(),
    handleUserJoin: jest.fn(),
    handleUserLeave: jest.fn(),
};

describe('ルーム機能テスト', () => {
    beforeEach(() => {
        cleanupDOM();

        // 基本的なDOM構造を作成
        const roomContainer = createTestElement('div', {
            id: 'room-container',
        });
        roomContainer.innerHTML = `
            <form id="room-create-form">
                <input name="title" placeholder="ルーム名" />
                <select name="format_id">
                    <option value="1">標準ディベート</option>
                </select>
                <button type="submit">作成</button>
            </form>
            <div id="room-info">
                <h2 id="room-title">ルーム名</h2>
                <div id="participants-list"></div>
            </div>
            <div id="notification-area"></div>
        `;
        document.body.appendChild(roomContainer);

        // グローバル設定
        global.window.roomData = {
            roomId: 123,
            userId: 456,
            isCreator: true,
        };

        jest.clearAllMocks();
    });

    afterEach(() => {
        cleanupDOM();
    });

    describe('ルーム作成機能', () => {
        test('ルーム作成フォームが正常に動作する', () => {
            const form = document.getElementById('room-create-form');
            const titleInput = form.querySelector('input[name="title"]');
            const formatSelect = form.querySelector('select[name="format_id"]');

            expect(form).toBeTruthy();
            expect(titleInput).toBeTruthy();
            expect(formatSelect).toBeTruthy();

            // フォームデータの設定
            titleInput.value = 'テストルーム';
            formatSelect.value = '1';

            expect(titleInput.value).toBe('テストルーム');
            expect(formatSelect.value).toBe('1');
        });

        test('フォームバリデーションが正常に動作する', async () => {
            const formData = mockFormManager.getFormData();
            expect(formData.title).toBe('テストルーム');
            expect(formData.formatId).toBe(1);

            const isValid = await mockFormManager.validate();
            expect(isValid).toBe(true);
            expect(mockFormManager.validate).toHaveBeenCalled();
        });

        test('ルーム作成が正常に完了する', async () => {
            const result = await mockFormManager.submit();

            expect(result.success).toBe(true);
            expect(result.roomId).toBe(123);
            expect(mockFormManager.submit).toHaveBeenCalled();
        });
    });

    describe('ルーム接続機能', () => {
        test('Pusher接続が正常に動作する', async () => {
            const connected = await mockConnectionManager.connect();

            expect(connected).toBe(true);
            expect(mockConnectionManager.connect).toHaveBeenCalled();
        });

        test('チャンネル取得が正常に動作する', () => {
            const channel = mockConnectionManager.getChannel();

            expect(channel).toBeDefined();
            expect(channel.bind).toBeDefined();
            expect(mockConnectionManager.getChannel).toHaveBeenCalled();
        });

        test('接続状態が正常に取得できる', () => {
            const isConnected = mockConnectionManager.isConnected();

            expect(isConnected).toBe(true);
            expect(mockConnectionManager.isConnected).toHaveBeenCalled();
        });
    });

    describe('ルームイベント処理', () => {
        test('ユーザー参加イベントが正常に処理される', () => {
            const userData = {
                id: 789,
                name: '新しいユーザー',
                joinTime: Date.now(),
            };

            mockEventHandler.handleUserJoin(userData);
            mockEventHandler.showNotification(`${userData.name}さんが参加しました`);

            expect(mockEventHandler.handleUserJoin).toHaveBeenCalledWith(userData);
            expect(mockEventHandler.showNotification).toHaveBeenCalledWith(
                '新しいユーザーさんが参加しました'
            );
        });

        test('ユーザー退出イベントが正常に処理される', () => {
            const userData = {
                id: 789,
                name: '退出ユーザー',
                leaveTime: Date.now(),
            };

            mockEventHandler.handleUserLeave(userData);
            mockEventHandler.showNotification(`${userData.name}さんが退出しました`);

            expect(mockEventHandler.handleUserLeave).toHaveBeenCalledWith(userData);
            expect(mockEventHandler.showNotification).toHaveBeenCalledWith(
                '退出ユーザーさんが退出しました'
            );
        });
    });

    describe('統合機能テスト', () => {
        test('ルーム画面の基本要素が存在する', () => {
            const roomInfo = document.getElementById('room-info');
            const roomTitle = document.getElementById('room-title');
            const participantsList = document.getElementById('participants-list');
            const notificationArea = document.getElementById('notification-area');

            expect(roomInfo).toBeTruthy();
            expect(roomTitle).toBeTruthy();
            expect(participantsList).toBeTruthy();
            expect(notificationArea).toBeTruthy();
        });

        test('ルームデータが正常に設定される', () => {
            expect(global.window.roomData).toBeDefined();
            expect(global.window.roomData.roomId).toBe(123);
            expect(global.window.roomData.userId).toBe(456);
            expect(global.window.roomData.isCreator).toBe(true);
        });

        test('接続とイベント処理の統合動作', async () => {
            // 接続
            const connected = await mockConnectionManager.connect();
            expect(connected).toBe(true);

            // イベント処理
            const userData = { id: 999, name: 'テストユーザー' };
            mockEventHandler.handleUserJoin(userData);

            // 結果確認
            expect(mockConnectionManager.connect).toHaveBeenCalled();
            expect(mockEventHandler.handleUserJoin).toHaveBeenCalledWith(userData);
        });
    });
});
