/**
 * Web Audio API モックファイル
 * 音声関連機能のテスト用モック
 */

// AudioContextモック
export const mockAudioContext = {
    state: 'running',
    sampleRate: 44100,
    destination: {},

    createGain: jest.fn(() => mockGainNode),
    createOscillator: jest.fn(() => mockOscillatorNode),
    createBufferSource: jest.fn(() => mockAudioBufferSourceNode),
    createBuffer: jest.fn(() => mockAudioBuffer),
    decodeAudioData: jest.fn(() => Promise.resolve(mockAudioBuffer)),

    suspend: jest.fn(() => Promise.resolve()),
    resume: jest.fn(() => Promise.resolve()),
    close: jest.fn(() => Promise.resolve()),

    currentTime: 0,

    _reset: () => {
        mockAudioContext.createGain.mockClear();
        mockAudioContext.createOscillator.mockClear();
        mockAudioContext.createBufferSource.mockClear();
        mockAudioContext.createBuffer.mockClear();
        mockAudioContext.decodeAudioData.mockClear();
        mockAudioContext.suspend.mockClear();
        mockAudioContext.resume.mockClear();
        mockAudioContext.close.mockClear();
    },
};

// GainNodeモック
export const mockGainNode = {
    gain: {
        value: 1,
        setValueAtTime: jest.fn(),
        linearRampToValueAtTime: jest.fn(),
        exponentialRampToValueAtTime: jest.fn(),
    },
    connect: jest.fn(),
    disconnect: jest.fn(),

    _reset: () => {
        mockGainNode.gain.setValueAtTime.mockClear();
        mockGainNode.gain.linearRampToValueAtTime.mockClear();
        mockGainNode.gain.exponentialRampToValueAtTime.mockClear();
        mockGainNode.connect.mockClear();
        mockGainNode.disconnect.mockClear();
    },
};

// OscillatorNodeモック
export const mockOscillatorNode = {
    type: 'sine',
    frequency: {
        value: 440,
        setValueAtTime: jest.fn(),
        linearRampToValueAtTime: jest.fn(),
    },
    connect: jest.fn(),
    disconnect: jest.fn(),
    start: jest.fn(),
    stop: jest.fn(),

    onended: null,

    _reset: () => {
        mockOscillatorNode.frequency.setValueAtTime.mockClear();
        mockOscillatorNode.frequency.linearRampToValueAtTime.mockClear();
        mockOscillatorNode.connect.mockClear();
        mockOscillatorNode.disconnect.mockClear();
        mockOscillatorNode.start.mockClear();
        mockOscillatorNode.stop.mockClear();
    },
};

// AudioBufferSourceNodeモック
export const mockAudioBufferSourceNode = {
    buffer: null,
    loop: false,
    loopStart: 0,
    loopEnd: 0,

    connect: jest.fn(),
    disconnect: jest.fn(),
    start: jest.fn(),
    stop: jest.fn(),

    onended: null,

    _reset: () => {
        mockAudioBufferSourceNode.connect.mockClear();
        mockAudioBufferSourceNode.disconnect.mockClear();
        mockAudioBufferSourceNode.start.mockClear();
        mockAudioBufferSourceNode.stop.mockClear();
    },
};

// AudioBufferモック
export const mockAudioBuffer = {
    sampleRate: 44100,
    length: 44100,
    duration: 1,
    numberOfChannels: 2,

    getChannelData: jest.fn(() => new Float32Array(44100)),
    copyFromChannel: jest.fn(),
    copyToChannel: jest.fn(),

    _reset: () => {
        mockAudioBuffer.getChannelData.mockClear();
        mockAudioBuffer.copyFromChannel.mockClear();
        mockAudioBuffer.copyToChannel.mockClear();
    },
};

// HTMLAudioElementモック
export const mockHTMLAudioElement = {
    src: '',
    volume: 1,
    muted: false,
    paused: true,
    ended: false,
    currentTime: 0,
    duration: 0,

    play: jest.fn(() => Promise.resolve()),
    pause: jest.fn(),
    load: jest.fn(),

    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),

    // イベントハンドラー
    onplay: null,
    onpause: null,
    onended: null,
    onerror: null,
    onloadeddata: null,

    _reset: () => {
        mockHTMLAudioElement.play.mockClear();
        mockHTMLAudioElement.pause.mockClear();
        mockHTMLAudioElement.load.mockClear();
        mockHTMLAudioElement.addEventListener.mockClear();
        mockHTMLAudioElement.removeEventListener.mockClear();
    },
};

// グローバルモック設定
global.AudioContext = jest.fn(() => mockAudioContext);
global.webkitAudioContext = jest.fn(() => mockAudioContext);

// HTMLAudioElementのコンストラクターモック
global.Audio = jest.fn(() => mockHTMLAudioElement);

// navigator.mediaDevicesモック
global.navigator = global.navigator || {};
global.navigator.mediaDevices = {
    getUserMedia: jest.fn(() =>
        Promise.resolve({
            getTracks: () => [],
        })
    ),
    enumerateDevices: jest.fn(() => Promise.resolve([])),
};

const mockAudio = {
    AudioContext: mockAudioContext,
    GainNode: mockGainNode,
    OscillatorNode: mockOscillatorNode,
    AudioBufferSourceNode: mockAudioBufferSourceNode,
    AudioBuffer: mockAudioBuffer,
    HTMLAudioElement: mockHTMLAudioElement,

    /**
     * 統一リセットメソッド
     */
    _reset: () => {
        mockAudioContext._reset();
        mockGainNode._reset();
        mockOscillatorNode._reset();
        mockAudioBufferSourceNode._reset();
        mockAudioBuffer._reset();
        mockHTMLAudioElement._reset();
    },
};

export default mockAudio;
