<section class="space-y-6">
    <header class="mb-6">
        <h2 class="text-xl font-bold text-danger dark:text-danger">
            アカウント削除
        </h2>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            アカウントを削除すると、すべてのリソースとデータが完全に削除されます。
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="px-5 py-2.5 transition-all bg-danger hover:bg-danger-700 focus:ring-danger"
    >アカウント削除</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-danger dark:text-danger">
                本当にアカウントを削除しますか？
            </h2>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                アカウントを削除すると、すべてのリソースとデータが完全に削除されます。アカウントを完全に削除することを確認するために、パスワードを入力してください。
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="パスワード" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4 rounded-md shadow-sm"
                    placeholder="パスワード"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')" class="px-4 py-2 transition-all">
                    キャンセル
                </x-secondary-button>

                <x-danger-button class="ms-3 px-4 py-2 transition-all bg-danger hover:bg-danger-700 focus:ring-danger">
                    アカウント削除
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
