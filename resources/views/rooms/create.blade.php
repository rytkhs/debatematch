<x-app-layout>
    <div class="bg-gray-100">
        <x-slot name="header">
            <x-header></x-header>
        </x-slot>
        <main class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-4 py-5 sm:p-6">

            <form action="{{ route('rooms.store') }}" method="POST" class="space-y-6">
                @csrf
                <!-- 論題 -->
                <div>
                    <label for="topic" class="block text-sm font-medium text-gray-700">論題</label>
                    <div class="mt-1">
                        <input type="text" id="topic" name="topic" value="{{ old('topic') }}" placeholder="例：日本は内閣による衆議院の解散権を制限すべきである。是か非か" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                    </div>
                </div>
                <!-- ルーム名 -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">ルーム名</label>
                    <div class="mt-1">
                        <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="例：初心者歓迎ルーム" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                </div>

                <!-- 備考 -->
                <div>
                    <label for="remarks" class="block text-sm font-medium text-gray-700">
                        備考 <span class="text-gray-500 text-sm">（任意）</span>
                    </label>
                    <div class="mt-1">
    <textarea id="remarks" name="remarks" rows="4" placeholder="特別なルールや注意事項があれば入力してください" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('name') }}</textarea>
                        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                    </div>
                </div>
                <!-- サイドの選択 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">サイド</label>
                    <div class="mt-1 flex items-center space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="side" value="affirmative" checked class="form-radio">
                            <span class="ml-2">肯定側</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="side" value="negative" class="form-radio">
                            <span class="ml-2">否定側</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('side')" class="mt-2" />
                </div>
                {{-- <div class="block mt-4">
                    <x-input-label :value="__('ルームの公開設定')" />
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="privacy" value="public" {{ old('privacy') === 'public' ? 'checked' : '' }} class="mr-2">
                            公開
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="privacy" value="private" {{ old('privacy') === 'private' ? 'checked' : '' }} class="mr-2">
                            非公開
                        </label>
                    </div>
                </div> --}}
                <div class="mt-4 space-x-4 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-5 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        ルームを作成
                    </button>
                    <a href="{{route('welcome')}}" class="bg-white border border-gray-300 rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        キャンセル
                    </a>
                </div>
            </form>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
