<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('接続分析ダッシュボード') }}
            </h2>
            {{-- 期間指定フォーム --}}
            <form method="GET" action="{{ route('admin.connection.analytics') }}" class="flex items-center space-x-2">
                <select name="period" onchange="this.form.submit()" class="block w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm">
                    <option value="24h" @selected($period == '24h')>過去24時間</option>
                    <option value="7d" @selected($period == '7d')>過去7日間</option>
                    <option value="30d" @selected($period == '30d')>過去30日間</option>
                </select>
            </form>
        </div>
        <p class="text-sm text-gray-500 mt-1">分析期間: {{ $startDate->format('Y/m/d H:i') }} 〜 {{ $endDate->format('Y/m/d H:i') }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- リアルタイム接続状況 --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-500">総接続ユーザー数</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $realtimeStats['total_connected'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-500">ルーム接続数</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $realtimeStats['room_connected'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-500">ディベート接続数</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $realtimeStats['debate_connected'] }}</div>
                </div>
                 <div class="bg-yellow-50 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm font-medium text-yellow-700">一時切断中のユーザー数</div>
                    <div class="mt-1 text-3xl font-semibold text-yellow-900">{{ $realtimeStats['temporarily_disconnected'] }}</div>
                </div>
            </div>

            {{-- 異常検知アラート --}}
            @if(count($frequentDisconnectionUsers) > 0)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h3 class="text-lg font-medium text-red-800 mb-2">頻繁な切断が検出されたユーザー ({{ $startDate->diffForHumans($endDate, true) }})</h3>
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach($frequentDisconnectionUsers as $item)
                        <li>
                            <a href="{{ route('admin.connection.user-detail', $item->user_id) }}" class="font-semibold hover:underline">
                                {{ $item->user ? $item->user->name : '削除されたユーザー' }} ({{ $item->user_id }})
                            </a> - {{ $item->frequent_count }} 回の頻繁切断ログ
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
            {{-- 低再接続率ユーザーのアラートも同様に追加 --}}


            {{-- 直近24時間の切断統計 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">直近24時間の切断統計</h3>
                    <div class="h-64">
                        <canvas id="disconnectionChart24h"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- ユーザー別の切断頻度ランキング --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">ユーザー別切断頻度ランキング ({{ $startDate->diffForHumans($endDate, true) }})</h3>
                        @if(count($userDisconnectionRanking) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ランク</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー名</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">切断回数</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">詳細</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($userDisconnectionRanking as $index => $item)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $item->user ? $item->user->name : '削除されたユーザー' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->disconnection_count }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @if($item->user)
                                                        <a href="{{ route('admin.connection.user-detail', $item->user->id) }}" class="text-indigo-600 hover:text-indigo-900">詳細を見る</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500">データがありません</p>
                        @endif
                    </div>
                </div>

                {{-- 再接続率 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">平均再接続率 ({{ $startDate->diffForHumans($endDate, true) }})</h3>
                        <div class="flex items-center justify-center h-64">
                            <div class="text-center">
                                <div class="relative inline-block">
                                    <div class="text-6xl font-bold text-indigo-600">
                                        {{ number_format($reconnectionRate, 1) }}%
                                    </div>
                                    <div class="text-sm text-gray-500 mt-2">
                                        切断後の再接続成功率
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 切断傾向分析 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">切断傾向分析 ({{ $startDate->diffForHumans($endDate, true) }})</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-2">クライアント別切断割合</h4>
                            <div class="h-64"><canvas id="clientTrendChart"></canvas></div>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-2">切断タイプ別割合</h4>
                            <div class="h-64"><canvas id="disconnectTypeChart"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 直近24時間グラフ ---
            const disconnectionData24h = @json($disconnectionStats24h);
            const labels24h = Array.from({length: 24}, (_, i) => `${String(i).padStart(2, '0')}:00`);
            const counts24h = Array(24).fill(0);
            disconnectionData24h.forEach(item => {
                const hour = parseInt(item.hour.split(':')[0]);
                counts24h[hour] = item.count;
            });
            const ctx24h = document.getElementById('disconnectionChart24h').getContext('2d');
            new Chart(ctx24h, {
                type: 'line',
                data: {
                    labels: labels24h,
                    datasets: [{
                        label: '切断件数',
                        data: counts24h,
                        backgroundColor: 'rgba(79, 70, 229, 0.2)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 2,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // --- 切断傾向グラフ ---
            const disconnectionTrends = @json($disconnectionTrends);

            // クライアント別
            const clientLabels = Object.keys(disconnectionTrends.by_client);
            const clientCounts = Object.values(disconnectionTrends.by_client);
            const ctxClient = document.getElementById('clientTrendChart').getContext('2d');
            new Chart(ctxClient, {
                type: 'pie',
                data: {
                    labels: clientLabels,
                    datasets: [{
                        label: 'クライアント別切断数',
                        data: clientCounts,
                        backgroundColor: [ /* 色の配列 */ ],
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // 切断タイプ別
            const typeLabels = Object.keys(disconnectionTrends.by_disconnect_type);
            const typeCounts = Object.values(disconnectionTrends.by_disconnect_type);
            const ctxType = document.getElementById('disconnectTypeChart').getContext('2d');
            new Chart(ctxType, {
                type: 'pie',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        label: '切断タイプ別切断数',
                        data: typeCounts,
                         backgroundColor: [ /* 色の配列 */ ],
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>
    @endpush
</x-app-layout>
