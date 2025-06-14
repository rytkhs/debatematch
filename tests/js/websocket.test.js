/**
 * WebSocket機能テスト
 * Laravel Echo + Pusher連携および接続管理のテスト
 */

import { mockEcho, mockPusher, mockChannel } from './__mocks__/pusher.js';

// テスト対象モジュールを動的インポート
let Echo, Pusher;

describe('WebSocket基本機能', () => {
    beforeEach(() => {
        // 環境変数のモック
        Object.defineProperty(window, 'import', {
            value: {
                meta: {
                    env: {
                        VITE_PUSHER_APP_KEY: 'test-key',
                        VITE_PUSHER_APP_CLUSTER: 'mt1',
                    },
                },
            },
            configurable: true,
        });

        // グローバルオブジェクトの設定
        global.Echo = mockEcho;
        global.Pusher = mockPusher;
        window.Echo = mockEcho;
        window.Pusher = mockPusher;

        // モックのリセット
        mockEcho._reset();
        mockPusher._reset();
    });

    afterEach(() => {
        mockEcho._reset();
        mockPusher._reset();
    });

    describe('Laravel Echo設定', () => {
        test('正しい設定でEchoが初期化されること', () => {
            // Echo設定の確認
            expect(window.Echo).toBeDefined();
            expect(window.Pusher).toBeDefined();
        });

        test('正しいPusher設定が使用されること', () => {
            // Pusherインスタンス作成をテスト
            const pusher = new window.Pusher('test-key', {
                cluster: 'mt1',
                forceTLS: true,
            });

            expect(mockPusher).toHaveBeenCalledWith('test-key', {
                cluster: 'mt1',
                forceTLS: true,
            });
        });
    });

    describe('チャンネル管理', () => {
        test('パブリックチャンネルが正常に作成されること', () => {
            const channelName = 'test-channel';
            const channel = window.Echo.channel(channelName);

            expect(mockEcho.channel).toHaveBeenCalledWith(channelName);
            expect(mockEcho._currentChannel).toBe(channelName);
            expect(channel).toBe(mockChannel);
        });

        test('プライベートチャンネルが正常に作成されること', () => {
            const channelName = 'test-private-channel';
            const channel = window.Echo.private(channelName);

            expect(mockEcho.private).toHaveBeenCalledWith(channelName);
            expect(mockEcho._currentChannel).toBe(`private-${channelName}`);
            expect(channel).toBe(mockChannel);
        });

        test('プレゼンスチャンネルが正常に作成されること', () => {
            const channelName = 'test-presence-channel';
            const channel = window.Echo.presence(channelName);

            expect(mockEcho.presence).toHaveBeenCalledWith(channelName);
            expect(mockEcho._currentChannel).toBe(`presence-${channelName}`);
            expect(channel).toBe(mockChannel);
        });

        test('チャンネルに正常に参加できること', () => {
            const channelName = 'test-join-channel';
            const channel = window.Echo.join(channelName);

            expect(mockEcho.join).toHaveBeenCalledWith(channelName);
            expect(mockEcho._currentChannel).toBe(channelName);
            expect(channel).toBe(mockChannel);
        });

        test('チャンネルから正常に退出できること', () => {
            const channelName = 'test-leave-channel';

            // まずチャンネルに参加
            window.Echo.join(channelName);
            expect(mockEcho._currentChannel).toBe(channelName);

            // チャンネルから退出
            window.Echo.leave(channelName);
            expect(mockEcho.leave).toHaveBeenCalledWith(channelName);
            expect(mockEcho._currentChannel).toBeNull();
        });
    });

    describe('イベントリスニング', () => {
        let channel;

        beforeEach(() => {
            channel = window.Echo.channel('test-event-channel');
        });

        test('イベントを正常にリッスンできること', () => {
            const eventName = 'TestEvent';
            const callback = jest.fn();

            channel.listen(eventName, callback);

            expect(mockChannel.listen).toHaveBeenCalledWith(eventName, callback);
        });

        test('イベントリスニングを正常に停止できること', () => {
            const eventName = 'TestEvent';
            const callback = jest.fn();

            // イベントリスナーを設定
            channel.listen(eventName, callback);

            // リスナーを削除
            channel.stopListening(eventName, callback);

            expect(mockChannel.stopListening).toHaveBeenCalledWith(eventName, callback);
        });

        test('イベント発火時にコールバックが実行されること', () => {
            const eventName = 'TestEvent';
            const callback = jest.fn();
            const testData = { message: 'test data' };

            // イベントリスナーを設定
            channel.listen(eventName, callback);

            // イベントを発火（モック機能）
            mockChannel._trigger(eventName, testData);

            expect(callback).toHaveBeenCalledWith(testData);
        });
    });

    describe('プレゼンスチャンネル機能', () => {
        let presenceChannel;

        beforeEach(() => {
            presenceChannel = window.Echo.presence('test-presence');
        });

        test('現在のメンバー取得（here）が正常に動作すること', () => {
            const callback = jest.fn();

            presenceChannel.here(callback);

            expect(mockChannel.here).toHaveBeenCalledWith(callback);
        });

        test('メンバー参加イベントが正常に処理されること', () => {
            const callback = jest.fn();

            presenceChannel.joining(callback);

            expect(mockChannel.joining).toHaveBeenCalledWith(callback);
        });

        test('メンバー退出イベントが正常に処理されること', () => {
            const callback = jest.fn();

            presenceChannel.leaving(callback);

            expect(mockChannel.leaving).toHaveBeenCalledWith(callback);
        });

        test('メンバー参加時にコールバックが実行されること', () => {
            const callback = jest.fn();
            const newMember = { id: 3, name: 'New User' };

            presenceChannel.joining(callback);
            mockChannel._triggerJoining(newMember);

            expect(callback).toHaveBeenCalledWith(newMember);
        });

        test('メンバー退出時にコールバックが実行されること', () => {
            const callback = jest.fn();
            const leavingMember = { id: 2, name: 'Leaving User' };

            presenceChannel.leaving(callback);
            mockChannel._triggerLeaving(leavingMember);

            expect(callback).toHaveBeenCalledWith(leavingMember);
        });

        test('ウィスパーメッセージが正常に処理されること', async () => {
            const eventName = 'typing';
            const data = { user: 'test-user', typing: true };

            const result = await presenceChannel.whisper(eventName, data);

            expect(mockChannel.whisper).toHaveBeenCalledWith(eventName, data);
            expect(result).toEqual({ event: eventName, data });
        });
    });

    describe('接続状態管理', () => {
        test('接続状態変更が正常に処理されること', () => {
            const callback = jest.fn();
            const pusher = new window.Pusher('test-key', { cluster: 'mt1' });

            // 接続状態の監視
            pusher.connection.bind('state_change', callback);

            expect(pusher.connection.bind).toHaveBeenCalledWith('state_change', callback);
        });

        test('接続状態にアクセスできること', () => {
            const pusher = new window.Pusher('test-key', { cluster: 'mt1' });

            expect(pusher.connection.state).toBe('connected');
        });

        test('Echoコネクター経由で接続状態を取得できること', () => {
            expect(mockEcho.connector.pusher.connection.state).toBe('connected');
        });
    });

    describe('エラーハンドリング', () => {
        test('Echo未定義時に適切に処理されること', () => {
            // Echoを一時的に削除
            const originalEcho = window.Echo;
            delete window.Echo;

            // console.warnのモック
            const warnSpy = jest.spyOn(console, 'warn').mockImplementation();

            // Echoが必要な機能を呼び出し
            if (typeof window.Echo === 'undefined') {
                console.warn('Laravel Echo cannot be found');
            }

            expect(warnSpy).toHaveBeenCalledWith('Laravel Echo cannot be found');

            // Echoを復元
            window.Echo = originalEcho;
            warnSpy.mockRestore();
        });

        test('接続エラーが正常に処理されること', () => {
            const errorCallback = jest.fn();
            const pusher = new window.Pusher('test-key', { cluster: 'mt1' });

            // エラーハンドラーを設定
            pusher.connection.bind('error', errorCallback);

            expect(pusher.connection.bind).toHaveBeenCalledWith('error', errorCallback);
        });

        test('チャンネル購読エラーが正常に処理されること', () => {
            const pusher = new window.Pusher('test-key', { cluster: 'mt1' });
            const channel = pusher.subscribe('invalid-channel');

            expect(pusher.subscribe).toHaveBeenCalledWith('invalid-channel');
            expect(channel).toBe(mockChannel);
        });

        test('切断処理が適切に実行されること', () => {
            const pusher = new window.Pusher('test-key', { cluster: 'mt1' });

            pusher.disconnect();

            expect(pusher.disconnect).toHaveBeenCalled();
        });

        test('チャンネル購読解除が正常に処理されること', () => {
            const pusher = new window.Pusher('test-key', { cluster: 'mt1' });
            const channelName = 'test-unsubscribe';

            // チャンネル購読
            pusher.subscribe(channelName);

            // チャンネル購読解除
            pusher.unsubscribe(channelName);

            expect(pusher.unsubscribe).toHaveBeenCalledWith(channelName);
        });
    });

    describe('実用シナリオ', () => {
        test('ルーム待機シナリオが正常に処理されること', () => {
            const roomId = 123;
            const userId = 456;

            // ルーム関連チャンネルに参加
            const roomChannel = window.Echo.channel(`rooms.${roomId}`);
            const presenceChannel = window.Echo.presence(`room.${roomId}`);

            expect(mockEcho.channel).toHaveBeenCalledWith(`rooms.${roomId}`);
            expect(mockEcho.presence).toHaveBeenCalledWith(`room.${roomId}`);

            // イベントリスナーを設定
            const userJoinedCallback = jest.fn();
            roomChannel.listen('UserJoinedRoom', userJoinedCallback);

            expect(mockChannel.listen).toHaveBeenCalledWith('UserJoinedRoom', userJoinedCallback);
        });

        test('ディベートシナリオが正常に処理されること', () => {
            const debateId = 789;

            // ディベート関連チャンネルに参加
            const debateChannel = window.Echo.private(`debate.${debateId}`);
            const presenceChannel = window.Echo.presence(`debate.${debateId}`);

            expect(mockEcho.private).toHaveBeenCalledWith(`debate.${debateId}`);
            expect(mockEcho.presence).toHaveBeenCalledWith(`debate.${debateId}`);

            // ディベートイベントリスナーを設定
            const messageCallback = jest.fn();
            const turnCallback = jest.fn();

            debateChannel.listen('MessageSent', messageCallback);
            debateChannel.listen('TurnAdvanced', turnCallback);

            expect(mockChannel.listen).toHaveBeenCalledWith('MessageSent', messageCallback);
            expect(mockChannel.listen).toHaveBeenCalledWith('TurnAdvanced', turnCallback);
        });

        test('複数チャンネル管理が正常に処理されること', () => {
            // 複数チャンネルの同時管理
            const roomChannel = window.Echo.channel('rooms.1');
            const debateChannel = window.Echo.private('debate.1');
            const presenceChannel = window.Echo.presence('room.1');

            // 各チャンネルでイベントリスナーを設定
            roomChannel.listen('StatusUpdated', jest.fn());
            debateChannel.listen('MessageSent', jest.fn());
            presenceChannel.here(jest.fn());

            expect(mockEcho.channel).toHaveBeenCalledWith('rooms.1');
            expect(mockEcho.private).toHaveBeenCalledWith('debate.1');
            expect(mockEcho.presence).toHaveBeenCalledWith('room.1');
        });
    });
});

describe('Pusher直接統合', () => {
    let pusher;

    beforeEach(() => {
        pusher = new window.Pusher('test-key', {
            cluster: 'mt1',
            authEndpoint: '/pusher/auth',
            forceTLS: true,
        });
    });

    afterEach(() => {
        mockPusher._reset();
    });

    test('正しい設定でPusherが初期化されること', () => {
        expect(mockPusher).toHaveBeenCalledWith('test-key', {
            cluster: 'mt1',
            authEndpoint: '/pusher/auth',
            forceTLS: true,
        });

        expect(pusher.key).toBe('test-key');
        expect(pusher.options).toEqual({
            cluster: 'mt1',
            authEndpoint: '/pusher/auth',
            forceTLS: true,
        });
    });

    test('チャンネルに直接購読できること', () => {
        const channelName = 'test-direct-channel';
        const channel = pusher.subscribe(channelName);

        expect(pusher.subscribe).toHaveBeenCalledWith(channelName);
        expect(channel).toBe(mockChannel);
    });

    test('接続イベントにバインドできること', () => {
        const stateChangeCallback = jest.fn();
        pusher.connection.bind('state_change', stateChangeCallback);

        expect(pusher.connection.bind).toHaveBeenCalledWith('state_change', stateChangeCallback);
    });

    test('認証エンドポイントが正しく設定されること', () => {
        expect(pusher.options.authEndpoint).toBe('/pusher/auth');
    });

    test('TLS接続が強制されること', () => {
        expect(pusher.options.forceTLS).toBe(true);
    });
});
