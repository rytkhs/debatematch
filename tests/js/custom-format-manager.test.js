/**
 * CustomFormatManager ユニットテスト
 */

const fs = require('fs');
const path = require('path');

// debate-form.jsの内容を読み込み
const debateFormCode = fs.readFileSync(
    path.join(process.cwd(), 'resources/js/debate-form.js'),
    'utf8'
);

// Node.js環境でコードを評価
eval(debateFormCode);

describe('CustomFormatManager', () => {
    let customFormatManager;
    let mockConfig;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="turns-container">
                <div class="turn-card">
                    <button class="delete-turn">削除</button>
                    <span class="turn-number">1</span>
                    <h4 class="turn-title">パート 1</h4>
                    <select name="turns[0][speaker]">
                        <option value="affirmative">肯定側</option>
                        <option value="negative">否定側</option>
                    </select>
                    <input class="part-name" name="turns[0][name]">
                    <input name="turns[0][duration]" type="number" value="5">
                    <input class="question-time-checkbox" name="turns[0][is_questions]" type="checkbox">
                    <input class="prep-time-checkbox" name="turns[0][is_prep_time]" type="checkbox">
                </div>
            </div>
            <button id="add-turn">ターン追加</button>
        `;

        mockConfig = {
            formType: 'room',
            translations: {
                part: 'パート',
                side: 'サイド',
                partName: 'パート名',
                durationMinutes: '時間（分）',
                questionTime: '質問時間',
                prepTime: '準備時間',
                placeholderPartName: 'パート名を入力',
                prepTimeSuggestion: '準備時間',
                questionTimeSuggestion: '質問時間',
                affirmative: '肯定側',
                negative: '否定側'
            }
        };

        customFormatManager = new CustomFormatManager(mockConfig);
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('初期化', () => {
        test('デフォルト設定で初期化される', () => {
            expect(customFormatManager.config).toEqual(mockConfig);
            expect(customFormatManager.turnCount).toBe(0);
            expect(customFormatManager.maxDuration).toBe(60); // roomタイプのデフォルト
        });

        test('AI形式では最大時間が14分に設定される', () => {
            const aiConfig = { ...mockConfig, formType: 'ai' };
            const aiCustomFormatManager = new CustomFormatManager(aiConfig);

            expect(aiCustomFormatManager.maxDuration).toBe(14);
        });

        test('init()でturnsContainerが設定される', () => {
            customFormatManager.init();

            expect(customFormatManager.turnsContainer).toBe(document.getElementById('turns-container'));
            expect(customFormatManager.turnCount).toBe(1); // 既存のターンカード数
        });
    });

    describe('ターン追加', () => {
        test('addTurn()で新しいターンが追加される', () => {
            customFormatManager.init();
            const initialCount = customFormatManager.turnCount;

            customFormatManager.addTurn();

            expect(customFormatManager.turnCount).toBe(initialCount + 1);
            expect(customFormatManager.turnsContainer.children.length).toBe(2);
        });

        test('追加ボタンクリックでターンが追加される', () => {
            customFormatManager.init();
            const addButton = document.getElementById('add-turn');

            const initialCount = customFormatManager.turnsContainer.children.length;
            addButton.click();

            expect(customFormatManager.turnsContainer.children.length).toBe(initialCount + 1);
        });
    });

    describe('ターン削除', () => {
        test('削除ボタンクリックでターンが削除される', () => {
            customFormatManager.init();
            customFormatManager.addTurn(); // 2つのターンにする

            const deleteButton = document.querySelector('.delete-turn');
            deleteButton.click();

            expect(customFormatManager.turnsContainer.children.length).toBe(1);
        });

        test('最後の1つのターンは削除できない', () => {
            customFormatManager.init();

            const deleteButton = document.querySelector('.delete-turn');
            const turnCard = deleteButton.closest('.turn-card');

            deleteButton.click();

            // ターンはまだ存在する
            expect(customFormatManager.turnsContainer.children.length).toBe(1);
            // エラーアニメーションが適用される
            expect(turnCard.classList.contains('border-red-500')).toBe(true);
            expect(turnCard.classList.contains('animate-pulse')).toBe(true);
        });
    });

    describe('ターン番号とname属性の更新', () => {
        test('updateTurnNumbersAndNames()でターン番号が更新される', () => {
            customFormatManager.init();
            customFormatManager.addTurn();
            customFormatManager.addTurn();

            // 最初のターンを削除
            const firstTurn = customFormatManager.turnsContainer.children[0];
            firstTurn.remove();

            customFormatManager.updateTurnNumbersAndNames();

            const turnNumbers = document.querySelectorAll('.turn-number');
            const turnTitles = document.querySelectorAll('.turn-title');

            expect(turnNumbers[0].textContent).toBe('1');
            expect(turnNumbers[1].textContent).toBe('2');
            expect(turnTitles[0].textContent).toBe('パート 1');
            expect(turnTitles[1].textContent).toBe('パート 2');
        });

        test('name属性のインデックスが正しく更新される', () => {
            customFormatManager.init();
            customFormatManager.addTurn();

            // 最初のターンを削除
            const firstTurn = customFormatManager.turnsContainer.children[0];
            firstTurn.remove();

            customFormatManager.updateTurnNumbersAndNames();

            const inputs = document.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('turns[')) {
                    expect(name).toMatch(/turns\[0\]/);
                }
            });
        });
    });

    describe('背景色の更新', () => {
        test('updateTurnCardBackground()でサイドに応じて背景色が変更される', () => {
            customFormatManager.init();

            const turnCard = document.querySelector('.turn-card');

            // 肯定側選択
            customFormatManager.updateTurnCardBackground(turnCard, 'affirmative');
            expect(turnCard.classList.contains('bg-green-50')).toBe(true);

            // 否定側選択
            customFormatManager.updateTurnCardBackground(turnCard, 'negative');
            expect(turnCard.classList.contains('bg-red-50')).toBe(true);
            expect(turnCard.classList.contains('bg-green-50')).toBe(false);

            // デフォルト
            customFormatManager.updateTurnCardBackground(turnCard, '');
            expect(turnCard.classList.contains('bg-white')).toBe(true);
            expect(turnCard.classList.contains('bg-red-50')).toBe(false);
        });
    });

    describe('パート名入力処理', () => {
        test('準備時間の入力でチェックボックスが自動選択される', () => {
            customFormatManager.init();

            const partNameInput = document.querySelector('.part-name');
            const prepTimeCheckbox = document.querySelector('.prep-time-checkbox');
            const questionTimeCheckbox = document.querySelector('.question-time-checkbox');

            partNameInput.value = '準備時間';
            const event = new Event('input');
            partNameInput.dispatchEvent(event);

            expect(prepTimeCheckbox.checked).toBe(true);
            expect(questionTimeCheckbox.checked).toBe(false);
        });

        test('質問時間の入力でチェックボックスが自動選択される', () => {
            customFormatManager.init();

            const partNameInput = document.querySelector('.part-name');
            const prepTimeCheckbox = document.querySelector('.prep-time-checkbox');
            const questionTimeCheckbox = document.querySelector('.question-time-checkbox');

            partNameInput.value = '質問時間';
            const event = new Event('input');
            partNameInput.dispatchEvent(event);

            expect(questionTimeCheckbox.checked).toBe(true);
            expect(prepTimeCheckbox.checked).toBe(false);
        });
    });

    describe('チェックボックス変更処理', () => {
        test('準備時間チェックボックスでパート名が自動入力される', () => {
            customFormatManager.init();

            const partNameInput = document.querySelector('.part-name');
            const prepTimeCheckbox = document.querySelector('.prep-time-checkbox');
            const questionTimeCheckbox = document.querySelector('.question-time-checkbox');

            prepTimeCheckbox.checked = true;
            const event = new Event('change');
            prepTimeCheckbox.dispatchEvent(event);

            expect(partNameInput.value).toBe('準備時間');
            expect(questionTimeCheckbox.checked).toBe(false);
        });

        test('質問時間チェックボックスでパート名が自動入力される', () => {
            customFormatManager.init();

            const partNameInput = document.querySelector('.part-name');
            const prepTimeCheckbox = document.querySelector('.prep-time-checkbox');
            const questionTimeCheckbox = document.querySelector('.question-time-checkbox');

            questionTimeCheckbox.checked = true;
            const event = new Event('change');
            questionTimeCheckbox.dispatchEvent(event);

            expect(partNameInput.value).toBe('質問時間');
            expect(prepTimeCheckbox.checked).toBe(false);
        });

        test('チェックボックスのオフでパート名がクリアされる', () => {
            customFormatManager.init();

            const partNameInput = document.querySelector('.part-name');
            const prepTimeCheckbox = document.querySelector('.prep-time-checkbox');

            // まず準備時間を選択
            prepTimeCheckbox.checked = true;
            prepTimeCheckbox.dispatchEvent(new Event('change'));

            // チェックを外す
            prepTimeCheckbox.checked = false;
            prepTimeCheckbox.dispatchEvent(new Event('change'));

            expect(partNameInput.value).toBe('');
        });
    });

    describe('ターンHTML生成', () => {
        test('createTurnHtml()で正しいHTMLが生成される', () => {
            customFormatManager.init();

            const html = customFormatManager.createTurnHtml(1);

            expect(html).toContain('turns[1][speaker]');
            expect(html).toContain('turns[1][name]');
            expect(html).toContain('turns[1][duration]');
            expect(html).toContain('turns[1][is_questions]');
            expect(html).toContain('turns[1][is_prep_time]');
            expect(html).toContain('パート 2'); // インデックス1なので表示は2
            expect(html).toContain(`max="${customFormatManager.maxDuration}"`);
        });
    });

    describe('イベントリスナーの設定', () => {
        test('attachInputListenersToElement()でリスナーが正しく設定される', () => {
            customFormatManager.init();

            const turnCard = document.querySelector('.turn-card');
            const partNameInput = turnCard.querySelector('.part-name');
            const speakerSelect = turnCard.querySelector('select[name*="[speaker]"]');

            // スピーカー変更で背景色が更新されることを確認
            speakerSelect.value = 'affirmative';
            speakerSelect.dispatchEvent(new Event('change'));

            expect(turnCard.classList.contains('bg-green-50')).toBe(true);
        });
    });
});
