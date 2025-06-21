/**
 * 画面に通知を表示する
 * @param {object} options - 通知のオプション
 * @param {string} options.title - 通知のタイトル
 * @param {string} options.message - 通知のメッセージ
 * @param {string} [options.type='info'] - 通知の種類 (success, error, warning, info)
 * @param {number} [options.duration=5000] - 表示時間 (ミリ秒)
 */
export function showNotification(options) {
    const notificationElement = document.createElement('div');
    notificationElement.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 border-l-4`;

    let bgColorClass, borderColorClass, textColorClass, iconColorClass, iconName;
    switch (options.type) {
        case 'success':
            bgColorClass = 'bg-green-50';
            borderColorClass = 'border-green-500';
            textColorClass = 'text-green-800';
            iconColorClass = 'text-green-600';
            iconName = 'check_circle';
            break;
        case 'error':
            bgColorClass = 'bg-red-50';
            borderColorClass = 'border-red-500';
            textColorClass = 'text-red-800';
            iconColorClass = 'text-red-600';
            iconName = 'error';
            break;
        case 'warning':
            bgColorClass = 'bg-yellow-50';
            borderColorClass = 'border-yellow-500';
            textColorClass = 'text-yellow-800';
            iconColorClass = 'text-yellow-600';
            iconName = 'warning';
            break;
        default: // info
            bgColorClass = 'bg-blue-50';
            borderColorClass = 'border-blue-500';
            textColorClass = 'text-blue-800';
            iconColorClass = 'text-blue-600';
            iconName = 'info';
            break;
    }

    notificationElement.classList.add(bgColorClass, borderColorClass);

    notificationElement.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                <span class="material-icons ${iconColorClass}">${iconName}</span>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium ${textColorClass}">${options.title}</h3>
                ${options.message ? `<div class="mt-1 text-sm ${textColorClass.replace('-800', '-700')}">${options.message}</div>` : ''}
            </div>
        </div>
    `;

    notificationElement.style.transition = 'all 0.5s ease-in-out';
    notificationElement.style.transform = 'translateX(100%)';
    notificationElement.style.opacity = '0';

    document.body.appendChild(notificationElement);
    notificationElement.getBoundingClientRect();

    setTimeout(() => {
        notificationElement.style.transform = 'translateX(0)';
        notificationElement.style.opacity = '1';
    }, 10);

    const displayDuration = options.duration || 5000;
    const transitionDuration = 500;

    setTimeout(() => {
        notificationElement.style.transform = 'translateX(100%)';
        notificationElement.style.opacity = '0';
        setTimeout(() => {
            notificationElement.remove();
        }, transitionDuration);
    }, displayDuration);
}
