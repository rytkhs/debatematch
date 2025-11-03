/**
 * DebateReportCopier
 * ディベート結果をマークダウン形式でクリップボードにコピーするクラス
 */
export class DebateReportCopier {
    /**
     * @param {Object} debateData - ディベートデータ
     * @param {string} locale - ロケール ('ja' or 'en')
     * @param {Object} translations - 翻訳データ
     */
    constructor(debateData, locale, translations) {
        this.debate = debateData;
        this.locale = locale;
        this.translations = translations;
    }

    /**
     * メインのマークダウン生成メソッド
     * @returns {string} 生成されたマークダウン
     */
    generateMarkdown() {
        const sections = [
            this.generateTitle(),
            this.generateDebateInfo(),
            this.generateDebaters(),
            this.generateEvaluation(),
            this.generateDebateContent(),
        ];

        return sections.filter(s => s).join('\n\n');
    }

    /**
     * タイトルセクション
     * @returns {string}
     */
    generateTitle() {
        return `# ${this.translations.debate_result_title}`;
    }

    /**
     * ディベート情報セクション
     * @returns {string}
     */
    generateDebateInfo() {
        const lines = [
            `## ${this.translations.debate_info_section}`,
            '',
            `- **${this.translations.topic_label}**: ${this.escapeMarkdown(this.debate.topic)}`,
            `- **${this.translations.room_name_label}**: ${this.escapeMarkdown(this.debate.room_name)}`,
            `- **${this.translations.host_name_label}**: ${this.escapeMarkdown(this.debate.host_name)}`,
        ];

        // 備考がある場合のみ追加
        if (this.debate.remarks) {
            lines.push(
                `- **${this.translations.remarks_label}**: ${this.escapeMarkdown(this.debate.remarks)}`
            );
        }

        lines.push(
            `- **${this.translations.datetime_label}**: ${this.formatDateTime(this.debate.created_at)}`
        );

        lines.push('', '---');

        return lines.join('\n');
    }

    /**
     * ディベーターセクション
     * @returns {string}
     */
    generateDebaters() {
        const lines = [`## ${this.translations.debaters_section}`, ''];

        // 肯定側
        const affirmativeName = this.escapeMarkdown(this.debate.affirmative.name);
        const affirmativeLabel = this.translations.affirmative_side;
        const affirmativeWinner = this.debate.affirmative.is_winner
            ? ` ${this.translations.winner_suffix}`
            : '';
        lines.push(`${affirmativeLabel}: ${affirmativeName}${affirmativeWinner}`);

        lines.push('');

        // 否定側
        const negativeName = this.escapeMarkdown(this.debate.negative.name);
        const negativeLabel = this.translations.negative_side;
        const negativeWinner = this.debate.negative.is_winner
            ? ` ${this.translations.winner_suffix}`
            : '';
        lines.push(`${negativeLabel}: ${negativeName}${negativeWinner}`);

        lines.push('', '---');

        return lines.join('\n');
    }

    /**
     * 講評セクション
     * @returns {string|null}
     */
    generateEvaluation() {
        if (!this.debate.evaluations) {
            return null;
        }

        const lines = [`## ${this.translations.evaluation_section}`, ''];

        const evaluations = this.debate.evaluations;

        // 論点の分析
        if (evaluations.analysis) {
            lines.push(`### ${this.translations.analysis_of_points}`, '');
            lines.push(this.escapeMarkdown(evaluations.analysis));
            lines.push('', '---', '');
        }

        // 判定結果
        if (evaluations.reason) {
            lines.push(`### ${this.translations.judgment_result}`, '');
            lines.push(this.escapeMarkdown(evaluations.reason));
            lines.push('');

            // 勝者表示
            if (evaluations.winner) {
                const winnerLabel =
                    evaluations.winner === 'affirmative'
                        ? this.translations.affirmative_side
                        : this.translations.negative_side;
                lines.push(`**${this.translations.winner_is}** ${winnerLabel}`);
            }

            lines.push('', '---', '');
        }

        // フィードバック
        if (evaluations.feedback_for_affirmative || evaluations.feedback_for_negative) {
            lines.push(`### ${this.translations.feedback}`, '');

            if (evaluations.feedback_for_affirmative) {
                lines.push(`#### ${this.translations.feedback_for_affirmative}`, '');
                lines.push(this.escapeMarkdown(evaluations.feedback_for_affirmative));
                lines.push('');
            }

            if (evaluations.feedback_for_negative) {
                lines.push(`#### ${this.translations.feedback_for_negative}`, '');
                lines.push(this.escapeMarkdown(evaluations.feedback_for_negative));
                lines.push('');
            }

            lines.push('---');
        }

        return lines.join('\n');
    }

    /**
     * ディベート内容セクション
     * @returns {string}
     */
    generateDebateContent() {
        const lines = [`## ${this.translations.debate_content_section}`, ''];

        if (!this.debate.messages || this.debate.messages.length === 0) {
            lines.push('メッセージなし');
            return lines.join('\n');
        }

        // ターンごとにメッセージをグループ化
        const groupedMessages = this.groupMessagesByTurn(this.debate.messages, this.debate.turns);

        groupedMessages.forEach(group => {
            // ターン名（サイド情報を前に付ける）
            let turnTitle = group.turnName;
            if (group.speaker) {
                const sideLabel =
                    group.speaker === 'affirmative'
                        ? this.translations.affirmative_side
                        : this.translations.negative_side;
                turnTitle = `${sideLabel}${turnTitle}`;
            }
            lines.push(`### ${this.escapeMarkdown(turnTitle)}`, '');

            // メッセージ
            group.messages.forEach(msg => {
                const sideLabel =
                    msg.side === 'affirmative'
                        ? this.translations.affirmative_side
                        : this.translations.negative_side;
                const timestamp = msg.created_at;

                lines.push(`**${sideLabel}** ${timestamp}`, '');

                // メッセージ内容をプレーンテキストとして追加
                const messageLines = msg.message.split('\n');
                messageLines.forEach(line => {
                    lines.push(this.escapeMarkdown(line));
                });

                lines.push('');
            });
        });

        return lines.join('\n');
    }

    /**
     * クリップボードにコピー
     * @returns {Promise<boolean>} 成功した場合true
     */
    async copyToClipboard() {
        const markdown = this.generateMarkdown();

        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(markdown);
                return true;
            } else {
                console.error('Clipboard API not available');
                return false;
            }
        } catch (error) {
            console.error('Copy failed:', error);
            return false;
        }
    }

    /**
     * 日時フォーマット
     * @param {string} dateString - ISO 8601形式の日時文字列
     * @returns {string} フォーマットされた日時
     */
    formatDateTime(dateString) {
        const date = new Date(dateString);

        if (this.locale === 'ja') {
            // 日本語: YYYY年MM月DD日 HH:mm
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}年${month}月${day}日 ${hours}:${minutes}`;
        } else {
            // 英語: MMM DD, YYYY HH:mm
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            };
            return date.toLocaleString('en-US', options);
        }
    }

    /**
     * メッセージをターンごとにグループ化
     * @param {Array} messages - メッセージ配列
     * @param {Object} turns - ターン情報オブジェクト
     * @returns {Array} グループ化されたメッセージ
     */
    groupMessagesByTurn(messages, turns) {
        const grouped = [];
        const turnMap = new Map();

        // ターン情報をマップに変換
        if (turns && typeof turns === 'object') {
            Object.keys(turns).forEach(key => {
                const turnNumber = parseInt(key, 10);
                const turn = turns[key];
                if (turn && turn.name && !isNaN(turnNumber)) {
                    turnMap.set(turnNumber, {
                        name: turn.name,
                        speaker: turn.speaker,
                    });
                }
            });
        }

        // フォールバック用のターン名生成関数
        const getFallbackTurnName = turnNumber => {
            if (this.locale === 'ja') {
                return `${turnNumber}`;
            } else {
                return `${turnNumber}`;
            }
        };

        // メッセージをターンごとにグループ化
        messages.forEach(msg => {
            const turnNumber = msg.turn;
            const turnInfo = turnMap.get(turnNumber);
            const turnName = turnInfo ? turnInfo.name : getFallbackTurnName(turnNumber);

            let group = grouped.find(g => g.turn === turnNumber);
            if (!group) {
                group = {
                    turn: turnNumber,
                    turnName: turnName,
                    speaker: turnInfo ? turnInfo.speaker : null,
                    messages: [],
                };
                grouped.push(group);
            }

            group.messages.push(msg);
        });

        return grouped;
    }

    /**
     * マークダウン特殊文字をエスケープ
     * @param {string} text - エスケープするテキスト
     * @returns {string} エスケープされたテキスト
     */
    escapeMarkdown(text) {
        if (!text) return '';

        // マークダウンの特殊文字をエスケープ
        // ただし、既にブロッククォート内にある場合は不要
        return text
            .replace(/\\/g, '\\\\')
            .replace(/\*/g, '\\*')
            .replace(/_/g, '\\_')
            .replace(/\[/g, '\\[')
            .replace(/\]/g, '\\]')
            .replace(/\(/g, '\\(')
            .replace(/\)/g, '\\)')
            .replace(/~/g, '\\~')
            .replace(/`/g, '\\`')
            .replace(/>/g, '\\>')
            .replace(/#/g, '\\#')
            .replace(/\+/g, '\\+')
            .replace(/-/g, '\\-')
            .replace(/\./g, '\\.')
            .replace(/!/g, '\\!')
            .replace(/\|/g, '\\|');
    }
}
