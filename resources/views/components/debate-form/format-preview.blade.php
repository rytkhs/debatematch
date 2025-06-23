<!-- フォーマットプレビュー -->
<div id="format-preview" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-200 hidden">
    <button type="button" class="w-full text-left focus:outline-none group transition-all"
        onclick="toggleFormatPreview()">
        <h3 class="text-sm sm:text-md font-semibold text-gray-700 flex items-center justify-between">
            <span class="flex items-center">
                <span class="material-icons-outlined text-indigo-500 mr-2">preview</span>
                <span id="format-preview-title">{{ __('debates_format.format_preview') }}</span>
            </span>
            <span class="material-icons-outlined text-gray-400 group-hover:text-indigo-500 transition-colors format-preview-icon">expand_less</span>
        </h3>
    </button>

    <div id="format-preview-content" class="mt-3 sm:mt-4 transition-all duration-300 transform">
        <div class="pt-2 border-t border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-100 rounded-lg">
                    <tbody id="format-preview-body" class="bg-white divide-y divide-gray-200">
                        <!-- JavaScriptで動的に生成 -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
