<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('ユーザー接続詳細分析') }}: {{ $user->name }}
            </h2>
            <a href="{{ route('admin.connection.analytics') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                戻る
            </a>
        </div>
        <form method="GET" action="{{ route('admin.connection.user-detail', $user->id) }}" class="flex items-center space-x-2">
            {{-- ... 期間選択 ... --}}
        </form>
        <p class="text-sm text-gray-500 mt-1">分析期間: {{ $startDate->format('Y/m/d H:i') }} 〜 {{ $endDate->format('Y/m/d H:i') }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- 接続問題概要 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">接続問題概要 ({{ $startDate->diffForHumans($endDate, true) }})</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <div class="text-sm text-indigo-600 font-medium">総切断回数</div>
                            <div class="text-3xl font-bold text-indigo-800">{{ $connectionIssues['total_disconnections'] }}</div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-sm text-green-600 font-medium">再接続成功</div>
                            <div class="text-3xl font-bold text-green-800">{{ $connectionIssues['successful_reconnections'] }}</div>
                        </div>

                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="text-sm text-red-600 font-medium">切断失敗率</div>
                            <div class="text-3xl font-bold text-red-800">{{ number_format($connectionIssues['failure_rate'], 1) }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 接続セッション履歴 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">接続セッション履歴</h3>

                    @if(count($connectionSessions) > 0)
                        <div class="space-y-4">
                            @foreach($connectionSessions as $index => $session)
                                <div x-data="{ open: false }" class="border rounded-lg overflow-hidden">
                                    <!-- セッション概要 -->
                                    <div @click="open = !open" class="flex justify-between items-center p-4 cursor-pointer {{ $session['status'] === 'connected' ? 'bg-green-50' : ($session['status'] === 'temporarily_disconnected' ? 'bg-yellow-50' : 'bg-red-50') }}">
                                        <div>
                                            <span class="font-semibold">セッション #{{ $index + 1 }}</span>
                                            <span class="text-sm text-gray-600 ml-2">
                                                @if($session['start'])
                                                    {{ $session['start']->format('Y/m/d H:i:s') }} 〜
                                                @else
                                                    開始不明 〜
                                                @endif
                                                @if($session['end'])
                                                    {{ $session['end']->format('Y/m/d H:i:s') }}
                                                @elseif($session['status'] === 'connected')
                                                    現在
                                                @elseif($session['status'] === 'temporarily_disconnected')
                                                    一時切断中
                                                @else
                                                    終了不明
                                                @endif
                                            </span>
                                            @if($session['duration'] !== null)
                                                <span class="text-sm text-gray-500 ml-2">({{ \Carbon\CarbonInterval::seconds($session['duration'])->cascade()->forHumans() }})</span>
                                            @endif
                                        </div>
                                        <div>
                                            @if($session['status'] === 'connected')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">接続中</span>
                                                @if($session['reconnected_at'])
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 ml-1">再接続</span>
                                                    @if($session['disconnection_duration'])
                                                        <span class="text-xs text-gray-500"> ({{ $session['disconnection_duration'] }}秒)</span>
                                                    @endif
                                                @endif
                                            @elseif($session['status'] === 'temporarily_disconnected')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">一時切断</span>
                                                @if($session['disconnected_at'])
                                                    <span class="text-xs text-gray-500"> ({{ $session['disconnected_at']->format('H:i:s') }})</span>
                                                @endif
                                            @elseif($session['status'] === 'disconnected')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">切断</span>
                                            @endif
                                            <svg class="w-5 h-5 inline-block transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                        </div>
                                    </div>
                                    <!-- セッション詳細 (ログ一覧とメタデータ) -->
                                    <div x-show="open" x-transition class="p-4 border-t">
                                        <h5 class="text-md font-semibold mb-2">関連ログ:</h5>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日時</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">コンテキスト</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">接続時間</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">切断時間</th>
                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">再接続</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach($session['logs'] as $log)
                                                        <tr>
                                                            <td class="px-4 py-2 whitespace-nowrap">{{ $log->created_at->format('Y/m/d H:i:s') }}</td>
                                                            <td class="px-4 py-2 whitespace-nowrap">{{ $log->context_type }} #{{ $log->context_id }}</td>
                                                            <td class="px-4 py-2 whitespace-nowrap">
                                                                @if($log->status === 'connected')
                                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                        接続中
                                                                    </span>
                                                                @elseif($log->status === 'temporarily_disconnected')
                                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                                        一時切断
                                                                    </span>
                                                                @elseif($log->status === 'disconnected')
                                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                        切断
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap">{{ $log->connected_at ? $log->connected_at->format('H:i:s') : '-' }}</td>
                                                            <td class="px-4 py-2 whitespace-nowrap">{{ $log->disconnected_at ? $log->disconnected_at->format('H:i:s') : '-' }}</td>
                                                            <td class="px-4 py-2 whitespace-nowrap">{{ $log->reconnected_at ? $log->reconnected_at->format('H:i:s') : '-' }}</td>
                                                        </tr>
                                                        <!-- メタデータ表示行 -->
                                                        <tr>
                                                            <td colspan="6" class="px-4 py-2 bg-gray-50 text-xs text-gray-600">
                                                                <pre class="whitespace-pre-wrap break-all">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">指定期間の接続セッションがありません</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script src="//unpkg.com/alpinejs" defer></script>
</x-app-layout>
