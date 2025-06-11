<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- デモモード通知 -->
            @isset($isDemo)
                <div class="mb-8 p-4 sm:p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl shadow-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-base sm:text-lg font-medium text-blue-900">{{ __('messages.demo_mode_notice') }}</h3>
                            <p class="text-sm sm:text-base text-blue-700 mt-1">{{ __('messages.demo_mode_description') }}</p>
                        </div>
                    </div>
                </div>
            @endisset

            <!-- フィルターとコントロール -->
            @include('records.partials.filters', compact('side', 'result', 'sort', 'keyword'))

            <!-- 統計情報と表示切り替え -->
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center sm:gap-4">
                @include('records.partials.display-info', compact('debates', 'side', 'result', 'keyword'))

                <!-- ビュー切り替えボタン -->
                <div class="flex items-center bg-white rounded-lg shadow-sm border p-1 self-start sm:self-auto">
                    <button id="viewGrid" class="view-toggle-btn px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-all duration-200 flex items-center space-x-1 sm:space-x-2">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('messages.grid_view') }}</span>
                    </button>
                    <button id="viewList" class="view-toggle-btn px-3 py-2 text-xs sm:text-sm font-medium rounded-md transition-all duration-200 flex items-center space-x-1 sm:space-x-2">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('messages.list_view') }}</span>
                    </button>
                </div>
            </div>

            @php
                $currentUser = Auth::user();
                $isGuest = $currentUser->isGuest();
                $demoUserIds = [];

                if ($isGuest) {
                    $demoUsers = App\Models\User::whereIn('email', [
                        'demo1@example.com',
                        'demo2@example.com',
                    ])->get();
                    $demoUserIds = $demoUsers->pluck('id')->toArray();
                }
            @endphp

            <!-- 履歴一覧 (グリッドビュー) -->
            <div id="gridView" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                @forelse($debates as $debate)
                    @include('records.partials.debate-card', [
                        'debate' => $debate,
                        'currentUser' => $currentUser,
                        'isGuest' => $isGuest,
                        'demoUserIds' => $demoUserIds,
                        'viewType' => 'grid'
                    ])
                @empty
                    @include('records.partials.empty-state')
                @endforelse
            </div>

            <!-- 履歴一覧 (リストビュー) -->
            <div id="listView" class="space-y-4 mb-8 hidden">
                @forelse($debates as $debate)
                    @include('records.partials.debate-card', [
                        'debate' => $debate,
                        'currentUser' => $currentUser,
                        'isGuest' => $isGuest,
                        'demoUserIds' => $demoUserIds,
                        'viewType' => 'list'
                    ])
                @empty
                    @include('records.partials.empty-state')
                @endforelse
            </div>

            <!-- ページネーション -->
            @if($debates->hasPages())
                <div class="mt-8 flex justify-center">
                    <div class="bg-white rounded-lg shadow-sm border px-6 py-4">
                        {{ $debates->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>

    @include('records.partials.scripts')
@vite(['resources/js/pages/records-index.js'])
</x-app-layout>
