/**
 * DebateReportCopier ユニットテスト
 */

import { DebateReportCopier } from '../../../resources/js/components/debate-report-copier.js';

describe('DebateReportCopier', () => {
    let mockDebateData;
    let mockTranslations;

    beforeEach(() => {
        // モックデータのセットアップ
        mockDebateData = {
            id: 1,
            topic: 'テストトピック',
            room_name: 'テストルーム',
            host_name: 'ホスト太郎',
            remarks: 'テスト備考',
            created_at: '2024-01-15T10:30:00.000Z',
            affirmative: {
                name: '肯定太郎',
                is_ai: false,
                is_winner: true,
            },
            negative: {
                name: '否定花子',
                is_ai: false,
                is_winner: false,
            },
            evaluations: {
                analysis: '論点の分析内容',
                reason: '判定理由',
                winner: 'affirmative',
                feedback_for_affirmative: '肯定側へのフィードバック',
                feedback_for_negative: '否定側へのフィードバック',
            },
            messages: [
                {
                    user_name: '肯定太郎',
                    message: 'メッセージ1',
                    turn: 1,
                    created_at: '10:30',
                    side: 'affirmative',
                },
                {
                    user_name: '否定花子',
                    message: 'メッセージ2',
                    turn: 1,
                    created_at: '10:31',
                    side: 'negative',
                },
            ],
            turns: {
                1: {
                    name: '立論',
                    speaker: 'affirmative',
                    duration: 300,
                    is_prep_time: false,
                    is_questions: false,
                },
            },
        };

        mockTranslations = {
            debate_result_title: 'ディベート結果',
            debate_info_section: 'ディベート情報',
            debaters_section: 'ディベーター',
            evaluation_section: '講評',
            debate_content_section: 'ディベート内容',
            topic_label: 'トピック',
            room_name_label: 'ルーム',
            host_name_label: 'ホスト',
            remarks_label: '備考',
            datetime_label: '日時',
            affirmative_side: '肯定側',
            negative_side: '否定側',
            winner_suffix: '(勝利)',
            analysis_of_points: '論点の分析',
            judgment_result: '判定結果',
            winner_is: '勝者',
            feedback: 'フィードバック',
            feedback_for_affirmative: '肯定側へのフィードバック',
            feedback_for_negative: '否定側へのフィードバック',
        };
    });

    describe('マークダウン生成', () => {
        test('完全なデータでマークダウンが正しく生成されること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const markdown = copier.generateMarkdown();

            expect(markdown).toContain('# ディベート結果');
            expect(markdown).toContain('## ディベート情報');
            expect(markdown).toContain('テストトピック');
            expect(markdown).toContain('テストルーム');
            expect(markdown).toContain('ホスト太郎');
            expect(markdown).toContain('テスト備考');
            expect(markdown).toContain('## ディベーター');
            expect(markdown).toContain('肯定太郎');
            expect(markdown).toContain('否定花子');
            expect(markdown).toContain('(勝利)');
            expect(markdown).toContain('## 講評');
            expect(markdown).toContain('論点の分析内容');
            expect(markdown).toContain('判定理由');
            expect(markdown).toContain('## ディベート内容');
            expect(markdown).toContain('メッセージ1');
            expect(markdown).toContain('メッセージ2');
        });

        test('評価データなしでマークダウンが生成されること', () => {
            const dataWithoutEval = { ...mockDebateData, evaluations: null };
            const copier = new DebateReportCopier(dataWithoutEval, 'ja', mockTranslations);
            const markdown = copier.generateMarkdown();

            expect(markdown).toContain('# ディベート結果');
            expect(markdown).toContain('## ディベート情報');
            expect(markdown).toContain('## ディベーター');
            expect(markdown).not.toContain('## 講評');
            expect(markdown).toContain('## ディベート内容');
        });

        test('備考なしでマークダウンが生成されること', () => {
            const dataWithoutRemarks = { ...mockDebateData, remarks: null };
            const copier = new DebateReportCopier(dataWithoutRemarks, 'ja', mockTranslations);
            const markdown = copier.generateMarkdown();

            expect(markdown).toContain('# ディベート結果');
            expect(markdown).not.toContain('備考');
        });

        test('メッセージなしでマークダウンが生成されること', () => {
            const dataWithoutMessages = { ...mockDebateData, messages: [] };
            const copier = new DebateReportCopier(dataWithoutMessages, 'ja', mockTranslations);
            const markdown = copier.generateMarkdown();

            expect(markdown).toContain('## ディベート内容');
            expect(markdown).toContain('メッセージなし');
        });
    });

    describe('日時フォーマット', () => {
        test('日本語ロケールで正しくフォーマットされること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const formatted = copier.formatDateTime('2024-01-15T10:30:00.000Z');

            expect(formatted).toMatch(/2024年01月15日 \d{2}:\d{2}/);
        });

        test('英語ロケールで正しくフォーマットされること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'en', mockTranslations);
            const formatted = copier.formatDateTime('2024-01-15T10:30:00.000Z');

            expect(formatted).toMatch(/Jan 15, 2024/);
        });
    });

    describe('メッセージグループ化', () => {
        test('ターンごとに正しくグループ化されること', () => {
            const messages = [
                {
                    user_name: 'ユーザー1',
                    message: 'メッセージ1',
                    turn: 1,
                    created_at: '10:30',
                    side: 'affirmative',
                },
                {
                    user_name: 'ユーザー2',
                    message: 'メッセージ2',
                    turn: 1,
                    created_at: '10:31',
                    side: 'negative',
                },
                {
                    user_name: 'ユーザー1',
                    message: 'メッセージ3',
                    turn: 2,
                    created_at: '10:35',
                    side: 'affirmative',
                },
            ];

            const turns = {
                1: { name: 'ターン1', speaker: 'affirmative' },
                2: { name: 'ターン2', speaker: 'negative' },
            };

            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const grouped = copier.groupMessagesByTurn(messages, turns);

            expect(grouped).toHaveLength(2);
            expect(grouped[0].turn).toBe(1);
            expect(grouped[0].turnName).toBe('ターン1');
            expect(grouped[0].messages).toHaveLength(2);
            expect(grouped[1].turn).toBe(2);
            expect(grouped[1].turnName).toBe('ターン2');
            expect(grouped[1].messages).toHaveLength(1);
        });

        test('ターン情報がない場合にデフォルト名が使用されること', () => {
            const messages = [
                {
                    user_name: 'ユーザー1',
                    message: 'メッセージ1',
                    turn: 1,
                    created_at: '10:30',
                    side: 'affirmative',
                },
            ];

            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const grouped = copier.groupMessagesByTurn(messages, []);

            expect(grouped).toHaveLength(1);
            expect(grouped[0].turnName).toBe('1');
        });

        test('複数メッセージが同じターンにグループ化されること', () => {
            const messages = [
                {
                    user_name: 'ユーザー1',
                    message: 'メッセージ1',
                    turn: 1,
                    created_at: '10:30',
                    side: 'affirmative',
                },
                {
                    user_name: 'ユーザー2',
                    message: 'メッセージ2',
                    turn: 1,
                    created_at: '10:31',
                    side: 'negative',
                },
                {
                    user_name: 'ユーザー1',
                    message: 'メッセージ3',
                    turn: 1,
                    created_at: '10:32',
                    side: 'affirmative',
                },
            ];

            const turns = {
                1: { name: 'ターン1', speaker: 'affirmative' },
            };

            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const grouped = copier.groupMessagesByTurn(messages, turns);

            expect(grouped).toHaveLength(1);
            expect(grouped[0].messages).toHaveLength(3);
        });
    });

    describe('エスケープ処理', () => {
        test('マークダウン特殊文字が正しくエスケープされること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);

            expect(copier.escapeMarkdown('*bold*')).toBe('\\*bold\\*');
            expect(copier.escapeMarkdown('_italic_')).toBe('\\_italic\\_');
            expect(copier.escapeMarkdown('[link](url)')).toBe('\\[link\\]\\(url\\)');
            expect(copier.escapeMarkdown('# heading')).toBe('\\# heading');
            expect(copier.escapeMarkdown('> quote')).toBe('\\> quote');
            expect(copier.escapeMarkdown('`code`')).toBe('\\`code\\`');
        });

        test('バックスラッシュが正しくエスケープされること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);

            expect(copier.escapeMarkdown('test\\text')).toBe('test\\\\text');
        });

        test('空文字列が正しく処理されること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);

            expect(copier.escapeMarkdown('')).toBe('');
            expect(copier.escapeMarkdown(null)).toBe('');
            expect(copier.escapeMarkdown(undefined)).toBe('');
        });

        test('複数の特殊文字が含まれるテキストが正しくエスケープされること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const text = '**重要** _注意_ [リンク](url) `コード`';
            const escaped = copier.escapeMarkdown(text);

            expect(escaped).toContain('\\*\\*');
            expect(escaped).toContain('\\_');
            expect(escaped).toContain('\\[');
            expect(escaped).toContain('\\]');
            expect(escaped).toContain('\\(');
            expect(escaped).toContain('\\)');
            expect(escaped).toContain('\\`');
        });
    });

    describe('タイトル生成', () => {
        test('正しいタイトルが生成されること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const title = copier.generateTitle();

            expect(title).toBe('# ディベート結果');
        });
    });

    describe('ディベート情報生成', () => {
        test('完全な情報が含まれること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const info = copier.generateDebateInfo();

            expect(info).toContain('## ディベート情報');
            expect(info).toContain('トピック');
            expect(info).toContain('テストトピック');
            expect(info).toContain('ルーム');
            expect(info).toContain('テストルーム');
            expect(info).toContain('ホスト');
            expect(info).toContain('ホスト太郎');
            expect(info).toContain('備考');
            expect(info).toContain('テスト備考');
            expect(info).toContain('日時');
            expect(info).toContain('---');
        });

        test('備考がない場合は備考行が含まれないこと', () => {
            const dataWithoutRemarks = { ...mockDebateData, remarks: null };
            const copier = new DebateReportCopier(dataWithoutRemarks, 'ja', mockTranslations);
            const info = copier.generateDebateInfo();

            expect(info).not.toContain('備考');
        });
    });

    describe('ディベーター生成', () => {
        test('勝者表示が正しく含まれること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const debaters = copier.generateDebaters();

            expect(debaters).toContain('肯定側: 肯定太郎 (勝利)');
            expect(debaters).toContain('否定側: 否定花子');
            expect(debaters).not.toContain('否定花子 (勝利)');
        });

        test('否定側が勝者の場合に正しく表示されること', () => {
            const dataWithNegativeWinner = {
                ...mockDebateData,
                affirmative: { ...mockDebateData.affirmative, is_winner: false },
                negative: { ...mockDebateData.negative, is_winner: true },
            };
            const copier = new DebateReportCopier(dataWithNegativeWinner, 'ja', mockTranslations);
            const debaters = copier.generateDebaters();

            expect(debaters).toContain('肯定側: 肯定太郎');
            expect(debaters).toContain('否定側: 否定花子 (勝利)');
        });
    });

    describe('講評生成', () => {
        test('完全な講評が生成されること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const evaluation = copier.generateEvaluation();

            expect(evaluation).toContain('## 講評');
            expect(evaluation).toContain('### 論点の分析');
            expect(evaluation).toContain('論点の分析内容');
            expect(evaluation).toContain('### 判定結果');
            expect(evaluation).toContain('判定理由');
            expect(evaluation).toContain('### フィードバック');
            expect(evaluation).toContain('#### 肯定側へのフィードバック');
            expect(evaluation).toContain('肯定側へのフィードバック');
            expect(evaluation).toContain('#### 否定側へのフィードバック');
            expect(evaluation).toContain('否定側へのフィードバック');
        });

        test('評価データがない場合にnullが返されること', () => {
            const dataWithoutEval = { ...mockDebateData, evaluations: null };
            const copier = new DebateReportCopier(dataWithoutEval, 'ja', mockTranslations);
            const evaluation = copier.generateEvaluation();

            expect(evaluation).toBeNull();
        });

        test('部分的な評価データで正しく生成されること', () => {
            const dataWithPartialEval = {
                ...mockDebateData,
                evaluations: {
                    analysis: '論点の分析のみ',
                    reason: null,
                    winner: null,
                    feedback_for_affirmative: null,
                    feedback_for_negative: null,
                },
            };
            const copier = new DebateReportCopier(dataWithPartialEval, 'ja', mockTranslations);
            const evaluation = copier.generateEvaluation();

            expect(evaluation).toContain('論点の分析のみ');
            expect(evaluation).not.toContain('判定結果');
            expect(evaluation).not.toContain('フィードバック');
        });
    });

    describe('ディベート内容生成', () => {
        test('メッセージが正しくフォーマットされること', () => {
            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const content = copier.generateDebateContent();

            expect(content).toContain('## ディベート内容');
            expect(content).toContain('### 肯定側立論');
            expect(content).toContain('**肯定側** 10:30');
            expect(content).toContain('メッセージ1');
            expect(content).toContain('**否定側** 10:31');
            expect(content).toContain('メッセージ2');
        });

        test('複数行メッセージが正しくフォーマットされること', () => {
            const dataWithMultilineMessage = {
                ...mockDebateData,
                messages: [
                    {
                        user_name: '肯定太郎',
                        message: '行1\n行2\n行3',
                        turn: 1,
                        created_at: '10:30',
                        side: 'affirmative',
                    },
                ],
            };
            const copier = new DebateReportCopier(dataWithMultilineMessage, 'ja', mockTranslations);
            const content = copier.generateDebateContent();

            expect(content).toContain('行1');
            expect(content).toContain('行2');
            expect(content).toContain('行3');
        });
    });

    describe('クリップボードコピー', () => {
        test('クリップボードAPIが利用可能な場合に成功すること', async () => {
            const mockWriteText = jest.fn().mockResolvedValue(undefined);
            global.navigator.clipboard = {
                writeText: mockWriteText,
            };

            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const result = await copier.copyToClipboard();

            expect(result).toBe(true);
            expect(mockWriteText).toHaveBeenCalledTimes(1);
            expect(mockWriteText).toHaveBeenCalledWith(expect.stringContaining('# ディベート結果'));
        });

        test('クリップボードAPIが利用できない場合に失敗すること', async () => {
            global.navigator.clipboard = undefined;

            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const result = await copier.copyToClipboard();

            expect(result).toBe(false);
        });

        test('クリップボードAPIがエラーを返す場合に失敗すること', async () => {
            const mockWriteText = jest.fn().mockRejectedValue(new Error('Permission denied'));
            global.navigator.clipboard = {
                writeText: mockWriteText,
            };

            const copier = new DebateReportCopier(mockDebateData, 'ja', mockTranslations);
            const result = await copier.copyToClipboard();

            expect(result).toBe(false);
        });
    });
});
