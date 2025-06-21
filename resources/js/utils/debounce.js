/**
 * 指定された時間、関数の実行を遅延させるデバウンス関数
 * @param {Function} func 実行する関数
 * @param {number} wait 待機時間 (ミリ秒)
 * @returns {Function} デバウンス化された関数
 */
export default function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
