<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-teal-600 dark:bg-teal-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-500 dark:hover:bg-teal-400 active:bg-green-700 dark:active:bg-emerald-600 focus:bg-green-700 dark:focus:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-emerald-400 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

