<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800  leading-tight">
            お問い合わせ詳細 #{{ $contact->id }}
        </h2>
        <a
            href="{{ route('admin.contacts.index') }}"
            class="px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
        >
            一覧に戻る
        </a>
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- お問い合わせ内容 -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-semibold mb-4">お問い合わせ内容</h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">種別</label>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                            {{ $contact->type_emoji }} {{ $contact->type_name }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">名前</label>
                                        <div class="mt-1 text-sm text-gray-900">{{ $contact->name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">メールアドレス</label>
                                        <div class="mt-1 text-sm text-gray-900">
                                            <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $contact->email }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">件名</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $contact->subject }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">メッセージ</label>
                                    <div class="mt-1 p-3 bg-gray-50 rounded-md">
                                        <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $contact->message }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-500">
                                    <div>
                                        <span class="font-medium">言語:</span> {{ $contact->language === 'ja' ? '🇯🇵 日本語' : '🇺🇸 English' }}
                                    </div>
                                    <div>
                                        <span class="font-medium">受信日時:</span> {{ $contact->created_at->format('Y-m-d H:i:s') }}
                                    </div>
                                    @if($contact->user)
                                        <div>
                                            <span class="font-medium">ユーザー:</span>
                                            <span class="text-blue-600">{{ $contact->user->name }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ステータス管理 -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-semibold mb-4">ステータス管理</h3>

                            <form method="POST" action="{{ route('admin.contacts.update-status', $contact) }}">
                                @csrf
                                @method('PATCH')

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">現在のステータス</label>
                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full {{ $contact->status_css_class }}">
                                            {{ $contact->status_name }}
                                        </span>
                                    </div>

                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">ステータス変更</label>
                                        <select
                                            name="status"
                                            id="status"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            @foreach(\App\Models\Contact::getStatuses() as $key => $label)
                                                <option value="{{ $key }}" {{ $contact->status === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">管理者メモ</label>
                                        <textarea
                                            name="admin_notes"
                                            id="admin_notes"
                                            rows="4"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="内部メモを入力..."
                                        >{{ $contact->admin_notes }}</textarea>
                                    </div>

                                    @if($contact->replied_at)
                                        <div class="text-sm text-gray-500">
                                            <span class="font-medium">回答日時:</span> {{ $contact->replied_at->format('Y-m-d H:i:s') }}
                                        </div>
                                    @endif

                                    <button
                                        type="submit"
                                        class="w-full px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                                    >
                                        更新
                                    </button>
                                </div>
                            </form>

                            <!-- 削除ボタン -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" onsubmit="return confirm('本当に削除しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="w-full px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150"
                                    >
                                        削除
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
