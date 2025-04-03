<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="min-h-screen py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- ヘッダーセクション -->
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold text-gray-700 my-10">ルーム一覧</h1>
            </div>

            <!-- ルーム一覧 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @if($rooms->isEmpty())
                    <div class="col-span-full py-12 flex flex-col items-center justify-center bg-white rounded-xl shadow-sm border border-dashed border-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                        <p class="text-xl font-medium text-gray-500 mb-2">現在参加可能なルームがありません</p>
                        <p class="text-gray-400 mb-6">新しいディベートルームを作成してみましょう</p>
                        <a href="{{ route('rooms.create') }}" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            ルームを作成する
                        </a>
                    </div>
                @else
                    @foreach ($rooms as $room)
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-all duration-300 flex flex-col">
                        <!-- ルームヘッダー -->
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-primary bg-primary-light py-1 px-3 rounded-full text-sm flex items-center">
                                        <span class="material-icons-outlined text-primary mr-1">meeting_room</span>
                                        <span>{{ $room->name }}</span>
                                    </span>
                                    <livewire:rooms.status :room="$room" />
                                </div>
                                <div class="flex items-center">
                                    <span class="bg-gray-50 px-2 py-1 rounded text-xs text-gray-500 mr-2">
                                        {{ $room->language === 'japanese' ? '日本語' : '英語' }}
                                    </span>
                                    <div class="bg-gray-50 px-2 py-1 rounded text-xs text-gray-500">
                                        {{ $room->created_at->format('Y/m/d') }}
                                    </div>
                                </div>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2 hover:line-clamp-none transition-all">
                                {{ $room->topic }}
                            </h2>

                            <!-- ホスト情報 -->
                            <div class="flex items-center mt-3">
                                <div>
                                    <p class="text-sm text-gray-600">Host：</p>
                                </div>
                                <div class="bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center text-gray-500 mr-2">
                                    {{ substr($room->creator->name, 0, 1) }}
                                </div>
                                <span class="font-medium">{{ $room->creator->name }}</span>
                            </div>
                        </div>

                        <!-- アクションフッター -->
                        <div class="mt-auto p-4 bg-gray-50 flex justify-between items-center">
                            <div class="flex items-center space-x-2">
                                <div class="flex -space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs">1</div>
                                    <div class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs">2</div>
                                </div>
                                <span class="text-sm text-gray-500">1/2 参加者</span>
                            </div>
                            <a href="{{ route('rooms.preview', ['room' => $room->id]) }}" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 text-sm flex items-center">
                                詳細を見る
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <!-- 新規ルーム作成ボタン (ルームがある場合) -->
            @if(!$rooms->isEmpty())
            <div class="mt-12 text-center">
                <a href="{{ route('rooms.create') }}" class="inline-flex items-center px-6 py-3 bg-primary hover:bg-primary-dark text-white font-bold rounded-full transition-all duration-300 shadow-lg transform hover:-translate-y-1 my-32">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    新しいルームを作成
                </a>
            </div>
            @endif

            <!-- ヘルプセクション -->
            {{-- <div class="mt-16 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-xl font-bold text-gray-800 mb-4">DebateMatchの使い方</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex flex-col items-center text-center p-4">
                        <div class="bg-primary-light rounded-full w-16 h-16 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <h4 class="font-medium text-lg mb-2">1. ルームを作成する</h4>
                        <p class="text-gray-600 text-sm">論題を設定し、肯定側か否定側のどちらかを選択してルームを作成します。</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-4">
                        <div class="bg-primary-light rounded-full w-16 h-16 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h4 class="font-medium text-lg mb-2">2. 対戦相手を待つ</h4>
                        <p class="text-gray-600 text-sm">ルームに対戦相手が参加するのを待ちます。または他の人が作成したルームに参加しましょう。</p>
                    </div>
                    <div class="flex flex-col items-center text-center p-4">
                        <div class="bg-primary-light rounded-full w-16 h-16 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h4 class="font-medium text-lg mb-2">3. ディベート開始</h4>
                        <p class="text-gray-600 text-sm">参加者が揃うとディベートを開始できます。</p>
                    </div>
                </div>
            </div> --}}
        </div>
    </div>
    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
