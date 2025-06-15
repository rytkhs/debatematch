/**
 * Pusher/Echo モックファイル
 * リアルタイム通信機能のテスト用モック
 */

// チャンネルモック
export const mockChannel = {
    // イベントリスナー
    listen: jest.fn((event, callback) => {
        mockChannel._callbacks = mockChannel._callbacks || {};
        mockChannel._callbacks[event] = mockChannel._callbacks[event] || [];
        mockChannel._callbacks[event].push(callback);
        return mockChannel;
    }),

    // リスナー停止
    stopListening: jest.fn((event, callback) => {
        if (mockChannel._callbacks && mockChannel._callbacks[event]) {
            if (callback) {
                const index = mockChannel._callbacks[event].indexOf(callback);
                if (index > -1) {
                    mockChannel._callbacks[event].splice(index, 1);
                }
            } else {
                mockChannel._callbacks[event] = [];
            }
        }
        return mockChannel;
    }),

    // プライベートメッセージ送信
    whisper: jest.fn((event, data) => {
        return Promise.resolve({ event, data });
    }),

    // メンバー（チャンネル参加者）
    here: jest.fn(callback => {
        const members = [
            { id: 1, name: 'Test User 1' },
            { id: 2, name: 'Test User 2' },
        ];
        callback(members);
        return mockChannel;
    }),

    // メンバー参加イベント
    joining: jest.fn(callback => {
        mockChannel._joiningCallback = callback;
        return mockChannel;
    }),

    // メンバー退出イベント
    leaving: jest.fn(callback => {
        mockChannel._leavingCallback = callback;
        return mockChannel;
    }),

    // イベントを発火（テスト用）
    _trigger: (event, data) => {
        if (mockChannel._callbacks && mockChannel._callbacks[event]) {
            mockChannel._callbacks[event].forEach(callback => {
                callback(data);
            });
        }
    },

    // メンバー参加を発火（テスト用）
    _triggerJoining: member => {
        if (mockChannel._joiningCallback) {
            mockChannel._joiningCallback(member);
        }
    },

    // メンバー退出を発火（テスト用）
    _triggerLeaving: member => {
        if (mockChannel._leavingCallback) {
            mockChannel._leavingCallback(member);
        }
    },

    // モックをリセット
    _reset: () => {
        mockChannel._callbacks = {};
        mockChannel._joiningCallback = null;
        mockChannel._leavingCallback = null;
        mockChannel.listen.mockClear();
        mockChannel.stopListening.mockClear();
        mockChannel.whisper.mockClear();
        mockChannel.here.mockClear();
        mockChannel.joining.mockClear();
        mockChannel.leaving.mockClear();
    },
};

// Echoモック
export const mockEcho = {
    // チャンネルに参加
    join: jest.fn(channelName => {
        mockEcho._currentChannel = channelName;
        return mockChannel;
    }),

    // チャンネルから退出
    leave: jest.fn(channelName => {
        mockEcho._currentChannel = null;
        return mockEcho;
    }),

    // プライベートチャンネル
    private: jest.fn(channelName => {
        mockEcho._currentChannel = `private-${channelName}`;
        return mockChannel;
    }),

    // プレゼンスチャンネル
    presence: jest.fn(channelName => {
        mockEcho._currentChannel = `presence-${channelName}`;
        return mockChannel;
    }),

    // パブリックチャンネル
    channel: jest.fn(channelName => {
        mockEcho._currentChannel = channelName;
        return mockChannel;
    }),

    // 接続情報
    connector: {
        pusher: {
            connection: {
                state: 'connected',
                bind: jest.fn(),
                unbind: jest.fn(),
            },
        },
    },

    // 現在のチャンネル（テスト用）
    _currentChannel: null,

    // モックをリセット
    _reset: () => {
        mockEcho._currentChannel = null;
        mockEcho.join.mockClear();
        mockEcho.leave.mockClear();
        mockEcho.private.mockClear();
        mockEcho.presence.mockClear();
        mockEcho.channel.mockClear();
        mockChannel._reset();
    },
};

// Pusherクライアントモック（コンストラクター関数として機能）
export const mockPusher = jest.fn().mockImplementation((key, options) => ({
    connection: {
        state: 'connected',
        bind: jest.fn(),
        unbind: jest.fn(),
    },
    subscribe: jest.fn(() => mockChannel),
    unsubscribe: jest.fn(),
    disconnect: jest.fn(),
    key,
    options,
}));

// Pusherインスタンスの追加プロパティ
mockPusher._reset = () => {
    mockPusher.mockClear();
};

// グローバルに設定
global.Echo = mockEcho;
global.Pusher = mockPusher;

export default {
    Echo: mockEcho,
    Pusher: mockPusher,
    Channel: mockChannel,
};
