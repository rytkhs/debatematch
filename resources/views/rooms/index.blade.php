<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="min-h-screen bg-gray-50 py-6 sm:py-8">
        <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
            <!-- ヘッダーセクション -->
            <div class="mb-6 sm:mb-8 text-center">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-700 my-8 sm:my-10">{{ __('messages.room_list') }}</h1>
            </div>

            <!-- ルーム一覧 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-8">
                @forelse ($rooms as $room)
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-all duration-300 flex flex-col">
                        <!-- ルームヘッダー -->
                        <div class="p-3 sm:p-5 border-b border-gray-100">
                            <div class="flex justify-between items-start mb-2 sm:mb-3">
                                <div class="flex items-center space-x-1 sm:space-x-2">
                                    <span class="font-medium text-primary bg-primary-light py-0.5 sm:py-1 px-2 sm:px-3 rounded-full text-xs sm:text-sm flex items-center">
                                        <span class="material-icons-outlined text-primary mr-1 text-xs sm:text-sm">meeting_room</span>
                                        <span>{{ $room->name }}</span>
                                    </span>
                                    <livewire:rooms.status :room="$room" />
                                </div>
                                <div class="flex items-center">
                                    <span class="bg-gray-50 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded text-xs text-gray-500 mr-1 sm:mr-2">
                                        {{ $room->language === 'ja' ? __('messages.language_ja') : __('messages.language_en') }}
                                    </span>
                                    <span class="bg-gray-50 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded text-xs {{ $room->evidence_allowed ? 'text-blue-500' : 'text-gray-500' }} mr-1 sm:mr-2 flex items-center">
                                        <span class="material-icons-outlined mr-0.5 text-xs">{{ $room->evidence_allowed ? 'fact_check' : 'no_sim' }}</span>
                                        {{ $room->evidence_allowed ? __('messages.evidence_allowed') : __('messages.evidence_not_allowed') }}
                                    </span>
                                    <div class="bg-gray-50 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded text-xs text-gray-500">
                                        {{ App::getLocale() === 'ja' ? $room->created_at->format('Y/m/d') : $room->created_at->format('M d, Y') }}

                                    </div>
                                </div>
                            </div>
                            <h2 class="text-base sm:text-xl font-bold text-gray-800 mb-1.5 sm:mb-2 line-clamp-2 hover:line-clamp-none transition-all">
                                {{ $room->topic }}
                            </h2>

                            <!-- ホスト情報 -->
                            <div class="flex items-center mt-2 sm:mt-3">
                                <div>
                                    <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.host') }}：</p>
                                </div>
                                <div class="bg-gray-100 rounded-full w-6 h-6 sm:w-8 sm:h-8 flex items-center justify-center text-gray-500 mr-1.5 sm:mr-2 text-xs sm:text-sm">
                                    {{ mb_substr($room->creator->name, 0, 1) }}
                                </div>
                                <span class="font-medium text-xs sm:text-sm">{{ $room->creator->name }}</span>
                            </div>
                        </div>

                        <!-- アクションフッター -->
                        <div class="mt-auto p-3 sm:p-4 bg-gray-50 flex justify-between items-center">
                            <div class="flex items-center space-x-1 sm:space-x-2">
                                <div class="flex -space-x-1.5 sm:-space-x-2">
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs">1</div>
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs">2</div>
                                </div>
                                <span class="text-xs sm:text-sm text-gray-500">1/2 {{ __('messages.participants') }}</span>
                            </div>
                            <a href="{{ route('rooms.preview', ['room' => $room->id]) }}" class="bg-primary hover:bg-primary-dark text-white font-medium py-1.5 sm:py-2 px-3 sm:px-4 rounded-lg transition-all duration-200 text-xs sm:text-sm flex items-center">
                                {{ __('messages.view_details') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 sm:py-12 flex flex-col items-center justify-center bg-white rounded-xl shadow-sm border border-dashed border-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 sm:h-16 w-12 sm:w-16 text-gray-300 mb-3 sm:mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                        <p class="text-lg sm:text-xl font-medium text-gray-500 mb-2">{{ __('messages.no_rooms_available') }}</p>
                        <p class="text-sm text-gray-400 mb-5 sm:mb-6">{{ __('messages.lets_create_room') }}</p>
                        <a href="{{ route('rooms.create') }}" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 sm:py-3 px-4 sm:px-6 rounded-lg transition-all duration-300 flex items-center text-sm sm:text-base">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            {{ __('messages.create_room') }}
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- 新規ルーム作成ボタン (ルームがある場合) -->
            @if($rooms->isNotEmpty())
            <div class="mt-8 sm:mt-12 text-center">
                <a href="{{ route('rooms.create') }}" class="inline-flex items-center px-4 sm:px-6 py-2 sm:py-3 bg-primary hover:bg-primary-dark text-white font-bold rounded-full transition-all duration-300 shadow-lg transform hover:-translate-y-1 my-24 sm:my-32 text-sm sm:text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-1.5 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ __('messages.create_new_room') }}
                </a>
            </div>
            @endif
        </div>
    </div>
    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
