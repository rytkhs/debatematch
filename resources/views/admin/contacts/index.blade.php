<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- 統計情報 -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-600">総数</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                </div>
                <div class="bg-red-50 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-red-600">新規</div>
                    <div class="text-2xl font-bold text-red-900">{{ $stats['new'] }}</div>
                </div>
                <div class="bg-yellow-50 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-yellow-600">確認中</div>
                    <div class="text-2xl font-bold text-yellow-900">{{ $stats['in_progress'] }}</div>
                </div>
                <div class="bg-green-50 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-green-600">回答済み</div>
                    <div class="text-2xl font-bold text-green-900">{{ $stats['replied'] }}</div>
                </div>
                <div class="bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-blue-600">解決済み</div>
                    <div class="text-2xl font-bold text-blue-900">{{ $stats['resolved'] }}</div>
                </div>
                <div class="bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-600">クローズ</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['closed'] }}</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- フィルタリング -->
                    <div class="mb-6">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div class="flex-1 min-w-64">
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="名前、メール、件名で検索..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>
                            <div>
                                <select
                                    name="status"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">全ステータス</option>
                                    @foreach(\App\Models\Contact::getStatuses() as $key => $label)
                                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select
                                    name="type"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">全種別</option>
                                    @foreach(\App\Models\Contact::getTypes() as $key => $label)
                                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                検索
                            </button>
                            @if(request()->hasAny(['search', 'status', 'type']))
                                <a
                                    href="{{ route('admin.contacts.index') }}"
                                    class="px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    クリア
                                </a>
                            @endif
                        </form>
                    </div>

                    <!-- お問い合わせ一覧 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">種別</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名前</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">件名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">言語</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">受信日時</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($contacts as $contact)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #{{ $contact->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $contact->type_emoji }} {{ $contact->type_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $contact->name }}
                                            @if($contact->user)
                                                <span class="text-xs text-blue-600">(登録ユーザー)</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="max-w-xs truncate">{{ $contact->subject }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $contact->status_css_class }}">
                                                {{ $contact->status_name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $contact->language === 'ja' ? '🇯🇵 日本語' : '🇺🇸 English' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $contact->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a
                                                href="{{ route('admin.contacts.show', $contact) }}"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                詳細
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                            お問い合わせがありません
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- ページネーション -->
                    @if($contacts->hasPages())
                        <div class="mt-6">
                            {{ $contacts->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
