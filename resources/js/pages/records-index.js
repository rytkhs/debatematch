import { FilterController } from '../features/records/FilterController.js';
import { ViewSwitcher } from '../features/records/ViewSwitcher.js';
import { initUIEnhancements } from '../features/ui/enhancements.js';

/**
 * Records Index ページのJavaScriptを初期化
 */
document.addEventListener('DOMContentLoaded', () => {
    // フィルター機能の初期化
    const filterForm = document.querySelector('[data-records-filter]');
    if (filterForm) {
        new FilterController(filterForm);
    }

    // ビュー切り替え機能の初期化
    const viewContainer = document.querySelector('[data-view-container]');
    if (viewContainer) {
        new ViewSwitcher(viewContainer);
    }

    // その他のUI改善を初期化
    initUIEnhancements();
});
