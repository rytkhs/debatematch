<x-app-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-white min-h-screen">
        <x-slot name="header">
            <x-header></x-header>
        </x-slot>

        <div class="max-w-6xl mx-auto py-6 sm:py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="px-3 py-4 sm:px-4 sm:py-5">
                    <div class="flex items-center mb-6 sm:mb-8 border-b pb-3 sm:pb-4">
                        <span class="material-icons-outlined text-indigo-600 text-xl sm:text-2xl mr-2 sm:mr-3">add_circle</span>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-700">新しいディベートルームを作成</h1>
                    </div>

                    <form action="{{ route('rooms.store') }}" method="POST" class="space-y-6 sm:space-y-8">
                        @csrf

                        <!-- セクション1: 基本情報 -->
                        <div class="bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h2 class="text-base sm:text-lg font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
                                <span class="material-icons-outlined text-indigo-500 mr-2">info</span>基本情報
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- 論題 -->
                                <div class="md:col-span-2">
                                    <label for="topic" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">論題 <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">subject</span>
                                        </div>
                                        <input type="text" id="topic" name="topic" value="{{ old('topic') }}"
                                            placeholder="論題を入力"
                                            required
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md">
                                        <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">明確な是非を問う論題を設定してください</p>
                                </div>

                                <!-- ルーム名 -->
                                <div>
                                    <label for="name" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">ルーム名 <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">meeting_room</span>
                                        </div>
                                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                                            placeholder="ルーム名を入力"
                                            required
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md">
                                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                    </div>
                                </div>

                                <!-- 言語設定 -->
                                <div>
                                    <label for="language" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">言語 <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">language</span>
                                        </div>
                                        <select id="language" name="language"
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md">
                                            <option value="japanese">日本語</option>
                                            <option value="english">英語</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('language')" class="mt-2" />
                                    </div>
                                </div>

                                <!-- 備考 -->
                                <div class="md:col-span-2">
                                    <label for="remarks" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                                        備考 <span class="text-gray-500 text-xs">（任意）</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute top-2 sm:top-3 left-3 flex items-start pointer-events-none">
                                            <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">description</span>
                                        </div>
                                        <textarea id="remarks" name="remarks" rows="3"
                                            placeholder="特別なルールや注意事項があれば入力してください"
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md">{{ old('remarks') }}</textarea>
                                        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- セクション2: ディベート設定 -->
                        <div class="bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h2 class="text-base sm:text-lg font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
                                <span class="material-icons-outlined text-indigo-500 mr-2">settings</span>ディベート設定
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- サイドの選択 -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">あなたのサイド <span
                                            class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                        <label
                                            class="relative flex bg-green-50 p-3 sm:p-4 rounded-lg border border-green-200 cursor-pointer hover:bg-green-100 transition">
                                            <input type="radio" name="side" value="affirmative" checked
                                                class="form-radio absolute opacity-0">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-green-500 flex items-center justify-center mr-2 sm:mr-3">
                                                    <div
                                                        class="side-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-green-500 opacity-100">
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="block text-xs sm:text-sm font-medium text-green-800">肯定側</span>
                                                    <span class="text-xs text-green-600">論題に賛成</span>
                                                </div>
                                            </div>
                                        </label>

                                        <label
                                            class="relative flex bg-red-50 p-3 sm:p-4 rounded-lg border border-red-200 cursor-pointer hover:bg-red-100 transition">
                                            <input type="radio" name="side" value="negative"
                                                class="form-radio absolute opacity-0">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-red-500 flex items-center justify-center mr-2 sm:mr-3">
                                                    <div class="side-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-red-500 opacity-0">
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="block text-xs sm:text-sm font-medium text-red-800">否定側</span>
                                                    <span class="text-xs text-red-600">論題に反対</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <x-input-error :messages="$errors->get('side')" class="mt-2" />
                                </div>

                                <!-- フォーマット選択 -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">フォーマット <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span
                                                class="material-icons-outlined text-gray-400 text-xs sm:text-sm">format_list_numbered</span>
                                        </div>
                                        <select name="format_type" id="format_type"
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md"
                                            onchange="toggleCustomFormat(this.value === 'custom'); updateFormatPreview(this.value);">
                                            @foreach ($formats as $format => $turns)
                                                <option value="{{ $format }}">{{ $format }}フォーマット</option>
                                            @endforeach
                                            <option value="custom">カスタムフォーマット</option>
                                        </select>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">一般的なフォーマットを選択するか、カスタムで設定できます</p>
                                </div>
                            </div>
                        </div>

                        <!-- フォーマットプレビュー -->
                        <div id="format-preview" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-200">
                            <button type="button" class="w-full text-left focus:outline-none group transition-all" onclick="toggleFormatPreview()">
                                <h3 class="text-sm sm:text-md font-semibold text-gray-700 flex items-center justify-between">
                                    <span class="flex items-center">
                                        <span class="material-icons-outlined text-indigo-500 mr-2">preview</span>
                                        <span id="format-preview-title">フォーマットプレビュー</span>
                                    </span>
                                    <span class="material-icons-outlined text-gray-400 group-hover:text-indigo-500 transition-colors format-preview-icon">expand_more</span>
                                </h3>
                            </button>

                            <div id="format-preview-content" class="hidden mt-3 sm:mt-4 transition-all duration-300 transform">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border border-gray-100 rounded-lg">
                                            <tbody id="format-preview-body" class="bg-white divide-y divide-gray-200">
                                                <!-- JavaScriptで動的に生成 -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- カスタムフォーマット設定 -->
                        <div id="custom-format-settings" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-200 hidden">
                            <h3 class="text-sm sm:text-md font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
                                <span class="material-icons-outlined text-indigo-500 mr-2">edit</span>
                                カスタムフォーマットを設定
                            </h3>
                            <div class="mb-3 sm:mb-4 p-3 sm:p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <span class="material-icons-outlined text-yellow-600 text-sm sm:text-base">info</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-xs sm:text-sm text-yellow-700">
                                            パートを追加して独自のディベート形式を作成できます。最低でも1つのパートが必要です。
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div id="turns-container" class="space-y-3 sm:space-y-4">
                                <!-- ターン設定テンプレート -->
                                <div class="turn-card border rounded-lg p-3 sm:p-4 bg-white shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-center mb-2 sm:mb-3">
                                        <div class="flex items-center">
                                            <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-medium text-xs sm:text-sm">1</span>
                                            <h4 class="text-xs sm:text-sm font-medium ml-2 text-gray-700">パート 1</h4>
                                        </div>
                                        <button type="button" class="delete-turn text-gray-400 hover:text-red-500 transition-colors">
                                            <span class="material-icons-outlined text-sm sm:text-base">delete</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs text-gray-500">サイド</label>
                                            <select name="turns[0][speaker]"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                                <option value="affirmative">肯定側</option>
                                                <option value="negative">否定側</option>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-5">
                                            <label class="block text-xs text-gray-500">パート名</label>
                                            <input type="text" name="turns[0][name]" placeholder="立論、反駁など"
                                                list="part-suggestions"
                                                class="part-name mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                            <datalist id="part-suggestions">
                                                <option value="立論">
                                                <option value="第一立論">
                                                <option value="第二立論">
                                                <option value="反駁">
                                                <option value="第一反駁">
                                                <option value="第二反駁">
                                                <option value="質疑">
                                                <option value="準備時間">
                                            </datalist>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs text-gray-500">時間（分）</label>
                                            <input type="number" name="turns[0][duration]" value="5" min="1"
                                                max="14"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                        </div>
                                        <div class="sm:col-span-2 flex flex-col justify-end">
                                            <div class="flex items-center space-x-2">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="turns[0][is_questions]"
                                                        class="question-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                                                    <span class="ml-1 text-xs text-gray-500">質疑</span>
                                                </label>
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="turns[0][is_prep_time]"
                                                        class="prep-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                                                    <span class="ml-1 text-xs text-gray-500">準備時間</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 sm:mt-6">
                                <button type="button" id="add-turn"
                                    class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors">
                                    <span class="material-icons-outlined text-xs sm:text-sm mr-1">add</span>
                                    パートを追加
                                </button>
                            </div>
                        </div>

                        <!-- 送信ボタンエリア -->
                        <div class="flex justify-between items-center pt-4 sm:pt-6 border-t">
                            <a href="{{ route('welcome') }}"
                                class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 border border-gray-300 rounded-md shadow-sm text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <span class="material-icons-outlined text-gray-500 mr-1 text-xs sm:text-sm">arrow_back</span>
                                キャンセル
                            </a>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 sm:px-6 sm:py-3 border border-transparent text-xs sm:text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <span class="material-icons-outlined mr-1 text-sm">check_circle</span>
                                ルームを作成
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // フォーマットデータ
        const formats = @json($formats);

        // サイド選択のラジオボタン動作
        document.addEventListener('DOMContentLoaded', function() {
            const sideRadios = document.querySelectorAll('input[name="side"]');

            sideRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.side-indicator').forEach(indicator => {
                        indicator.style.opacity = '0';
                    });
                    this.closest('label').querySelector('.side-indicator').style.opacity = '1';
                });
            });

            // 初期状態で選択されているラジオボタンのインジケーターを表示
            const checkedRadio = document.querySelector('input[name="side"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('label').querySelector('.side-indicator').style.opacity = '1';
            }
        });

        // カスタムフォーマット表示切替
        function toggleCustomFormat(show) {
            const customSettings = document.getElementById('custom-format-settings');
            customSettings.classList.toggle('hidden', !show);
            const formatPreview = document.getElementById('format-preview');
            formatPreview.classList.toggle('hidden', show);
        }

        // フォーマットプレビュー更新関数
        function updateFormatPreview(format) {
            if (format === 'custom') return;

            const previewBody = document.getElementById('format-preview-body');
            previewBody.innerHTML = '';
            const previewTitle = document.getElementById('format-preview-title');
            previewTitle.textContent = format + "フォーマット";

            if (!formats[format]) {
                previewBody.innerHTML =
                    '<tr><td colspan="4" class="px-3 py-2 text-sm text-gray-500">フォーマット情報がありません</td></tr>';
                return;
            }

            Object.entries(formats[format]).forEach(([index, turn]) => {
                const row = document.createElement('tr');
                let speakerText = turn.speaker === 'affirmative' ? '肯定側' : '否定側';
                let bgClass = turn.speaker === 'affirmative' ? 'bg-green-50' : 'bg-red-50';
                let textClass = turn.speaker === 'affirmative' ? 'text-green-800' : 'text-red-800';
                let badgeClass = turn.speaker === 'affirmative' ? 'bg-green-100' : 'bg-red-100';
                let typeIcon = '';

                if (turn.is_prep_time) {
                    typeIcon =
                        '<span class="material-icons-outlined text-xs mr-1 text-gray-500">timer</span>';
                } else if (turn.is_questions) {
                    typeIcon =
                        '<span class="material-icons-outlined text-xs mr-1 text-gray-500">help</span>';
                }

                row.className = bgClass;
                row.innerHTML = `<td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700">${index}</td>
<td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm">
    <span class="px-2 py-0.5 inline-flex items-center rounded-full ${badgeClass} ${textClass} text-xs font-medium">
        ${speakerText}
    </span>
</td>
<td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700 flex items-center">
    ${typeIcon}${turn.name}
</td>
<td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700">
    ${Math.floor(turn.duration / 60)}分
</td>`;
                previewBody.appendChild(row);
            });
        }

        // フォーマットプレビューの開閉
        function toggleFormatPreview() {
            const content = document.getElementById('format-preview-content');
            const icon = document.querySelector('.format-preview-icon');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.add('opacity-100');
                }, 10);
                icon.textContent = 'expand_less';
            } else {
                content.classList.remove('opacity-100');
                content.classList.add('opacity-0');
                setTimeout(() => {
                    content.classList.add('hidden');
                    icon.textContent = 'expand_more';
                }, 200);
            }
        }

        // ページ読み込み時に実行
        document.addEventListener('DOMContentLoaded', function() {
            const formatSelect = document.getElementById('format_type');
            toggleCustomFormat(formatSelect.value === 'custom');
            updateFormatPreview(formatSelect.value);

            formatSelect.addEventListener('change', function() {
                toggleCustomFormat(this.value === 'custom');
                updateFormatPreview(this.value);
            });

            // ターン追加ボタン
            const addTurnButton = document.getElementById('add-turn');
            const turnsContainer = document.getElementById('turns-container');
            let turnCount = 1;

            // ターン追加処理
            addTurnButton.addEventListener('click', function() {
                const newTurn = document.createElement('div');
                newTurn.className =
                    'turn-card border rounded-lg p-3 sm:p-4 bg-white shadow-sm hover:shadow-md transition-shadow';
                newTurn.innerHTML = `<div class="flex justify-between items-center mb-2 sm:mb-3">
    <div class="flex items-center">
        <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-medium text-xs sm:text-sm">${turnCount + 1}</span>
        <h4 class="text-xs sm:text-sm font-medium ml-2 text-gray-700">パート ${turnCount + 1}</h4>
    </div>
    <button type="button" class="delete-turn text-gray-400 hover:text-red-500 transition-colors">
        <span class="material-icons-outlined text-sm sm:text-base">delete</span>
    </button>
</div>
<div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
    <div class="sm:col-span-3">
        <label class="block text-xs text-gray-500">サイド</label>
        <select name="turns[${turnCount}][speaker]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
            <option value="affirmative">肯定側</option>
            <option value="negative">否定側</option>
        </select>
    </div>
    <div class="sm:col-span-5">
        <label class="block text-xs text-gray-500">パート名</label>
        <input type="text" name="turns[${turnCount}][name]" placeholder="立論、反駁など" list="part-suggestions" class="part-name mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-xs text-gray-500">時間（分）</label>
        <input type="number" name="turns[${turnCount}][duration]" value="3" min="1" max="14" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
    </div>
    <div class="sm:col-span-2 flex flex-col justify-end">
        <div class="flex items-center space-x-2">
            <label class="inline-flex items-center">
                <input type="checkbox" name="turns[${turnCount}][is_questions]" class="question-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                <span class="ml-1 text-xs text-gray-500">質疑</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="turns[${turnCount}][is_prep_time]" class="prep-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                <span class="ml-1 text-xs text-gray-500">準備時間</span>
            </label>
        </div>
    </div>
</div>`;
                turnsContainer.appendChild(newTurn);
                turnCount++;

                // 削除ボタンにイベントリスナーを追加
                attachDeleteListeners();

                // 準備時間、質疑時間、パート名変更のイベントリスナー
                attachInputListeners();
            });

            // 削除ボタンのイベントリスナー設定
            function attachDeleteListeners() {
                document.querySelectorAll('.delete-turn').forEach(button => {
                    button.addEventListener('click', function() {
                        if (turnsContainer.children.length > 1) {
                            this.closest('.turn-card').remove();
                            // ターン番号を振り直す
                            updateTurnNumbers();
                        } else {
                            // アニメーションで警告効果
                            const turnCard = this.closest('.turn-card');
                            turnCard.classList.add('border-red-500');
                            setTimeout(() => {
                                turnCard.classList.remove('border-red-500');
                            }, 800);
                        }
                    });
                });
            }

            // ターン番号の更新
            function updateTurnNumbers() {
                const turns = turnsContainer.querySelectorAll('.turn-card');
                turns.forEach((turn, index) => {
                    // ターン番号表示の更新
                    const numberDisplay = turn.querySelector('.w-6.h-6');
                    numberDisplay.textContent = `${index + 1}`;
                    const titleDisplay = turn.querySelector('h4');
                    titleDisplay.textContent = `パート ${index + 1}`;

                    // 入力フィールドの名前属性も更新
                    turn.querySelectorAll('input, select').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/turns\[\d+\]/, `turns[${index}]`));
                        }
                    });
                });
                turnCount = turns.length;
            }

            // イベントリスナー
            function attachInputListeners() {
                document.querySelectorAll('.part-name').forEach(input => {
                    input.removeEventListener('input', partNameInputListener);
                    input.addEventListener('input', partNameInputListener);
                });

                document.querySelectorAll('.prep-time-checkbox, .question-time-checkbox').forEach(checkbox => {
                    checkbox.removeEventListener('change', checkboxChangeListener);
                    checkbox.addEventListener('change', checkboxChangeListener);
                });
            }

            function partNameInputListener() {
                const turnCard = this.closest('.turn-card');
                const partNameInput = turnCard.querySelector('.part-name');
                const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
                const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

                if (partNameInput.value.trim() === '準備時間') {
                    prepTimeCheckbox.checked = true;
                    questionTimeCheckbox.checked = false;
                } else if (partNameInput.value.trim() === '質疑') {
                    questionTimeCheckbox.checked = true;
                    prepTimeCheckbox.checked = false;
                } else {
                    prepTimeCheckbox.checked = false;
                    questionTimeCheckbox.checked = false;
                }
            }

            function checkboxChangeListener() {
                const turnCard = this.closest('.turn-card');
                const partNameInput = turnCard.querySelector('.part-name');
                const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
                const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

                if (this.classList.contains('prep-time-checkbox') && this.checked) {
                    partNameInput.value = '準備時間';
                    if (questionTimeCheckbox) questionTimeCheckbox.checked = false;
                } else if (this.classList.contains('question-time-checkbox') && this.checked) {
                    partNameInput.value = '質疑';
                    if (prepTimeCheckbox) prepTimeCheckbox.checked = false;
                } else if (!prepTimeCheckbox.checked && !questionTimeCheckbox.checked) {
                    // どちらもチェックが外れた場合は空にしない
                }
            }

            // 初期化
            attachDeleteListeners();
            attachInputListeners();
        });
    </script>
</x-app-layout>
