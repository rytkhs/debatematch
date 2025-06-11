/**
 * ディベートフォーム用ユーティリティクラス
 */
class Utils {
    showFieldError(fieldName, message) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.add('border-red-500', 'bg-red-50');

            // 既存のエラーメッセージを削除
            const existingError = field.parentNode.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }

            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-600 text-xs mt-1 error-message animate-pulse';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }
    }

    clearFieldErrors() {
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500', 'bg-red-50');
        });
    }

    animateClass(element, className, duration = 300) {
        element.classList.add(className);
        setTimeout(() => element.classList.remove(className), duration);
    }
}

export default Utils;
