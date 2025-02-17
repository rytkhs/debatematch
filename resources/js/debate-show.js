document.addEventListener('DOMContentLoaded', function() {

    window.Livewire.on('scroll-to-bottom', () => {
        const chatContainer = document.getElementById('chat-container');

        setTimeout(() => {
            if (chatContainer) {
                // スクロールを最下部に移動
                chatContainer.scrollTo({
                    top: chatContainer.scrollHeight,
                    behavior: 'smooth'
                });
            }
        }, 100);
    });
});
