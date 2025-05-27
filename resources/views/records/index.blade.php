<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="max-w-7xl min-h-screen mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- デモモード通知 -->
        @isset($isDemo)
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-800">{{ __('messages.demo_mode_notice') }}</p>
                        <p class="text-xs text-blue-600 mt-1">{{ __('messages.demo_mode_description') }}</p>
                    </div>
                </div>
            </div>
        @endisset

        <!-- フィルターとソート -->
        @include('records.partials.filters', compact('side', 'result', 'sort', 'keyword'))

        <!-- 表示件数と検索情報 -->
        @include('records.partials.display-info', compact('debates', 'side', 'result', 'keyword'))

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
        <div id="gridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">
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
        <div id="listView" class="space-y-3 sm:space-y-4 mb-5 sm:mb-6 hidden">
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
        <div class="mt-6">
            {{ $debates->links() }}
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>

    @include('records.partials.scripts')
</x-app-layout>
