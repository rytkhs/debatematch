/**
 * カスタムフォーマット管理クラス
 */
class CustomFormatManager {
    constructor(config) {
        this.config = config;
        this.turnCount = 0;
        this.turnsContainer = null;
        this.maxDuration = config.formType === 'ai' ? 14 : 60;
    }

    init() {
        this.turnsContainer = document.getElementById('turns-container');
        this.turnCount = this.turnsContainer ? this.turnsContainer.children.length : 1;
        this.setupEventListeners();
        this.initializeExistingTurns();
    }

    setupEventListeners() {
        document.getElementById('add-turn')?.addEventListener('click', () => {
            this.addTurn();
        });
    }

    initializeExistingTurns() {
        if (this.turnsContainer) {
            this.attachDeleteListeners();
            this.turnsContainer.querySelectorAll('.turn-card').forEach(card => {
                this.attachInputListenersToElement(card);
            });
        }
    }

    addTurn() {
        if (!this.turnsContainer) return;

        const turnHtml = this.createTurnHtml(this.turnCount);
        const newTurn = document.createElement('div');
        newTurn.className = 'turn-card border rounded-lg p-3 sm:p-4 bg-white shadow-sm hover:shadow-md transition-shadow';
        newTurn.innerHTML = turnHtml;

        this.turnsContainer.appendChild(newTurn);
        this.turnCount++;

        this.attachDeleteListeners();
        this.attachInputListenersToElement(newTurn);
    }

    createTurnHtml(index) {
        return `
            <div class="flex justify-between items-center mb-2 sm:mb-3">
                <div class="flex items-center">
                    <span class="turn-number w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-medium text-xs sm:text-sm">${index + 1}</span>
                    <h4 class="turn-title text-xs sm:text-sm font-medium ml-2 text-gray-700">${this.config.translations.part} ${index + 1}</h4>
                </div>
                <button type="button" class="delete-turn text-gray-400 hover:text-red-500 transition-colors">
                    <span class="material-icons-outlined text-sm sm:text-base">delete</span>
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                <div class="sm:col-span-3">
                    <label class="block text-xs text-gray-500">${this.config.translations.side}</label>
                    <select name="turns[${index}][speaker]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                        <option value="affirmative">${this.config.translations.affirmative}</option>
                        <option value="negative">${this.config.translations.negative}</option>
                    </select>
                </div>
                <div class="sm:col-span-5">
                    <label class="block text-xs text-gray-500">${this.config.translations.partName}</label>
                    <input type="text" name="turns[${index}][name]" placeholder="${this.config.translations.placeholderPartName}" list="part-suggestions" class="part-name mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500">${this.config.translations.durationMinutes}</label>
                    <input type="number" name="turns[${index}][duration]" value="5" min="1" max="${this.maxDuration}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                </div>
                <div class="sm:col-span-2 flex flex-col justify-end">
                    <div class="flex items-center space-x-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="turns[${index}][is_questions]" value="1" class="question-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                            <span class="ml-1 text-xs text-gray-500">${this.config.translations.questionTime}</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="turns[${index}][is_prep_time]" value="1" class="prep-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                            <span class="ml-1 text-xs text-gray-500">${this.config.translations.prepTime}</span>
                        </label>
                    </div>
                </div>
            </div>
        `;
    }

    attachDeleteListeners() {
        document.querySelectorAll('.delete-turn').forEach(button => {
            button.replaceWith(button.cloneNode(true));
        });

        document.querySelectorAll('.delete-turn').forEach(button => {
            button.addEventListener('click', (event) => {
                if (this.turnsContainer.children.length > 1) {
                    event.target.closest('.turn-card').remove();
                    this.updateTurnNumbersAndNames();
                } else {
                    const turnCard = event.target.closest('.turn-card');
                    turnCard.classList.add('border-red-500', 'animate-pulse');
                    setTimeout(() => {
                        turnCard.classList.remove('border-red-500', 'animate-pulse');
                    }, 1000);
                }
            });
        });
    }

    attachInputListenersToElement(element) {
        // パート名、チェックボックス、サイド選択のリスナー設定
        const partNameInput = element.querySelector('.part-name');
        const prepTimeCheckbox = element.querySelector('.prep-time-checkbox');
        const questionTimeCheckbox = element.querySelector('.question-time-checkbox');
        const speakerSelect = element.querySelector('select[name*="[speaker]"]');

        if (partNameInput) {
            partNameInput.addEventListener('input', (e) => this.handlePartNameInput(e));
        }

        [prepTimeCheckbox, questionTimeCheckbox].forEach(checkbox => {
            if (checkbox) {
                checkbox.addEventListener('change', (e) => this.handleCheckboxChange(e));
            }
        });

        if (speakerSelect) {
            speakerSelect.addEventListener('change', (e) => {
                const turnCard = e.target.closest('.turn-card');
                this.updateTurnCardBackground(turnCard, e.target.value);
            });
            this.updateTurnCardBackground(element, speakerSelect.value);
        }
    }

    updateTurnNumbersAndNames() {
        const turns = this.turnsContainer.querySelectorAll('.turn-card');
        turns.forEach((turn, index) => {
            const displayTurnNumber = index + 1;
            const numberDisplay = turn.querySelector('.turn-number');
            const titleDisplay = turn.querySelector('.turn-title');

            if (numberDisplay) numberDisplay.textContent = displayTurnNumber;
            if (titleDisplay) titleDisplay.textContent = `${this.config.translations.part} ${displayTurnNumber}`;

            turn.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/turns\[\d+\]/, `turns[${index}]`);
                    input.setAttribute('name', newName);
                }
            });

            const speakerSelect = turn.querySelector('select[name*="[speaker]"]');
            if (speakerSelect) {
                this.updateTurnCardBackground(turn, speakerSelect.value);
            }
        });
        this.turnCount = turns.length;
    }

    updateTurnCardBackground(turnCard, speakerValue) {
        turnCard.classList.remove('bg-green-50', 'bg-red-50', 'bg-white');

        if (speakerValue === 'affirmative') {
            turnCard.classList.add('bg-green-50');
        } else if (speakerValue === 'negative') {
            turnCard.classList.add('bg-red-50');
        } else {
            turnCard.classList.add('bg-white');
        }
    }

    handlePartNameInput(event) {
        const turnCard = event.target.closest('.turn-card');
        if (!turnCard) return;

        const partNameInput = event.target;
        const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
        const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

        if (!prepTimeCheckbox || !questionTimeCheckbox) return;

        if (partNameInput.value.trim() === this.config.translations.prepTimeSuggestion) {
            prepTimeCheckbox.checked = true;
            questionTimeCheckbox.checked = false;
        } else if (partNameInput.value.trim() === this.config.translations.questionTimeSuggestion) {
            questionTimeCheckbox.checked = true;
            prepTimeCheckbox.checked = false;
        }
    }

    handleCheckboxChange(event) {
        const turnCard = event.target.closest('.turn-card');
        if (!turnCard) return;

        const partNameInput = turnCard.querySelector('.part-name');
        const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
        const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

        if (!partNameInput || !prepTimeCheckbox || !questionTimeCheckbox) return;

        const isPrepTime = event.target === prepTimeCheckbox;
        const isQuestionTime = event.target === questionTimeCheckbox;

        if (isPrepTime && event.target.checked) {
            partNameInput.value = this.config.translations.prepTimeSuggestion;
            questionTimeCheckbox.checked = false;
        } else if (isQuestionTime && event.target.checked) {
            partNameInput.value = this.config.translations.questionTimeSuggestion;
            prepTimeCheckbox.checked = false;
        } else if (
            (isPrepTime && !event.target.checked && partNameInput.value === this.config.translations.prepTimeSuggestion) ||
            (isQuestionTime && !event.target.checked && partNameInput.value === this.config.translations.questionTimeSuggestion)
        ) {
            partNameInput.value = '';
        }
    }
}

export default CustomFormatManager;
