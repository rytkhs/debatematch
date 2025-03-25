<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if($rooms->isEmpty())
                <p class="text-center text-gray-600 mt-10 col-span-full">現在参加可能なルームはありません</p>
            @else
                @foreach ($rooms as $room)
                <a href="{{ route('rooms.preview', ['room' => $room->id]) }}"
                    class="bg-white shadow-lg rounded-xl px-6 py-4 transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1 h-full flex flex-col justify-between border border-gray-100 hover:border-primary/20 cursor-pointer">
                    <div>
                        <div class="text-gray-700 mb-0 font-medium text-md flex items-center justify-between">
                            <div class="flex items-center">
                                {{-- <span class="material-icons-outlined font-normal text-[1.2rem] mr-2 text-primary">
                                    chair
                                </span> --}}
                                <span class="text-primary">Room</span><span class="text-lg ml-1">{{ $room->name }}</span>
                            </div>
                            @livewire('room-status', ['room' => $room])
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mt-1 mb-4">
                            {{ $room->topic }}
                        </h2>
                    </div>
                    <p class="flex items-center text-sm text-gray-700">
                        {{-- <span class="material-icons text-[1.2rem] mr-1 text-gray-500">
                            person_outline
                        </span> --}}
                        Host: {{ $room->creator->name }}
                    </p>
                </a>
                @endforeach
            @endif
        </div>
        <div class="mt-20 text-center">
            <a href="{{ route('rooms.create') }}"
                class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-8 rounded-full transition-colors duration-300 shadow-lg hover:shadow-lg transform hover:-translate-y-1">
                新しいルームを作成
            </a>
        </div>
    </div>
    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
