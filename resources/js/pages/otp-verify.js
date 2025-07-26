/**
 * OTP Verification Page JavaScript
 * Handles OTP input formatting, countdown timer, and form enhancements
 */

class OtpVerification {
    constructor() {
        this.otpInput = document.getElementById('otp');
        this.timerElement = document.getElementById('otp-timer');
        this.form = document.querySelector('form[action*="otp.verify"]');
        this.resendForm = document.querySelector('form[action*="otp.resend"]');
        this.resendButton = this.resendForm?.querySelector('button[type="submit"]');

        // OTP expires in 10 minutes (600 seconds)
        this.expirationTime = 10 * 60 * 1000; // 10 minutes in milliseconds
        this.startTime = Date.now();

        this.init();
    }

    init() {
        if (this.otpInput) {
            this.setupOtpInput();
        }

        if (this.timerElement) {
            this.startCountdown();
        }

        if (this.form) {
            this.setupFormHandling();
        }

        if (this.resendButton) {
            this.setupResendButton();
        }
    }

    setupOtpInput() {
        // Auto-focus on the OTP input
        this.otpInput.focus();

        // Format input to only allow numbers
        this.otpInput.addEventListener('input', e => {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits

            if (value.length > 6) {
                value = value.slice(0, 6); // Limit to 6 digits
            }

            e.target.value = value;

            // Auto-submit when 6 digits are entered
            if (value.length === 6) {
                setTimeout(() => {
                    this.form.submit();
                }, 100);
            }
        });

        // Handle paste events
        this.otpInput.addEventListener('paste', e => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const digits = paste.replace(/\D/g, '').slice(0, 6);
            this.otpInput.value = digits;

            if (digits.length === 6) {
                setTimeout(() => {
                    this.form.submit();
                }, 100);
            }
        });

        // Handle keydown events
        this.otpInput.addEventListener('keydown', e => {
            // Allow: backspace, delete, tab, escape, enter
            if (
                [8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)
            ) {
                return;
            }

            // Ensure that it is a number and stop the keypress
            if (
                (e.shiftKey || e.keyCode < 48 || e.keyCode > 57) &&
                (e.keyCode < 96 || e.keyCode > 105)
            ) {
                e.preventDefault();
            }
        });
    }

    startCountdown() {
        const updateTimer = () => {
            const elapsed = Date.now() - this.startTime;
            const remaining = Math.max(0, this.expirationTime - elapsed);

            if (remaining <= 0) {
                this.handleExpiration();
                return;
            }

            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);

            // Update timer display
            const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            // Get the current language for proper message formatting
            const isJapanese = document.documentElement.lang === 'ja';
            const message = isJapanese ? `有効期限: ${timeString}` : `Expires in: ${timeString}`;

            this.timerElement.textContent = message;

            // Change color as time runs out
            const timerContainer = this.timerElement.closest(
                '.bg-yellow-50, .dark\\:bg-yellow-900\\/20'
            );
            if (remaining < 60000) {
                // Less than 1 minute
                if (timerContainer) {
                    timerContainer.className = timerContainer.className
                        .replace('bg-yellow-50', 'bg-red-50')
                        .replace('dark:bg-yellow-900/20', 'dark:bg-red-900/20')
                        .replace('border-yellow-200', 'border-red-200')
                        .replace('dark:border-yellow-800', 'dark:border-red-800');
                }
                this.timerElement.className = this.timerElement.className
                    .replace('text-yellow-800', 'text-red-800')
                    .replace('dark:text-yellow-200', 'dark:text-red-200');

                const icon = this.timerElement.parentElement.querySelector('svg');
                if (icon) {
                    icon.className = icon.className
                        .replace('text-yellow-600', 'text-red-600')
                        .replace('dark:text-yellow-400', 'dark:text-red-400');
                }
            }
        };

        // Update immediately and then every second
        updateTimer();
        this.countdownInterval = setInterval(updateTimer, 1000);
    }

    handleExpiration() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }

        // Update timer to show expired
        const isJapanese = document.documentElement.lang === 'ja';
        this.timerElement.textContent = isJapanese ? '有効期限切れ' : 'Expired';

        // Disable the OTP input and submit button
        if (this.otpInput) {
            this.otpInput.disabled = true;
            this.otpInput.classList.add('opacity-50', 'cursor-not-allowed');
        }

        const submitButton = this.form?.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        }

        // Show expiration message
        this.showExpirationMessage();
    }

    showExpirationMessage() {
        const isJapanese = document.documentElement.lang === 'ja';
        const message = isJapanese
            ? '認証コードの有効期限が切れました。新しいコードをリクエストしてください。'
            : 'The verification code has expired. Please request a new one.';

        // Create or update expiration message
        let expiredMessage = document.getElementById('otp-expired-message');
        if (!expiredMessage) {
            expiredMessage = document.createElement('div');
            expiredMessage.id = 'otp-expired-message';
            expiredMessage.className =
                'mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md';
            expiredMessage.innerHTML = `
                <div class="flex">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-sm text-red-800 dark:text-red-200">${message}</p>
                </div>
            `;

            // Insert before the form
            this.form.parentNode.insertBefore(expiredMessage, this.form);
        }
    }

    setupFormHandling() {
        this.form.addEventListener('submit', e => {
            const otpValue = this.otpInput.value.trim();

            // Validate OTP format
            if (!/^\d{6}$/.test(otpValue)) {
                e.preventDefault();
                this.showValidationError();
                return;
            }

            // Show loading state
            this.showLoadingState();
        });
    }

    showValidationError() {
        const isJapanese = document.documentElement.lang === 'ja';
        const message = isJapanese
            ? '認証コードは6桁の数字である必要があります。'
            : 'The verification code must be exactly 6 digits.';

        this.showError(message);
        this.otpInput.focus();
        this.otpInput.select();
    }

    showError(message) {
        // Remove existing error message
        const existingError = document.getElementById('otp-validation-error');
        if (existingError) {
            existingError.remove();
        }

        // Create new error message
        const errorDiv = document.createElement('div');
        errorDiv.id = 'otp-validation-error';
        errorDiv.className =
            'mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md';
        errorDiv.innerHTML = `
            <div class="flex">
                <svg class="w-4 h-4 text-red-600 dark:text-red-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-sm text-red-800 dark:text-red-200">${message}</p>
            </div>
        `;

        // Insert before the form
        this.form.parentNode.insertBefore(errorDiv, this.form);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    showLoadingState() {
        const submitButton = this.form.querySelector('button[type="submit"]');
        if (submitButton) {
            const originalText = submitButton.textContent;
            const isJapanese = document.documentElement.lang === 'ja';
            const loadingText = isJapanese ? '確認中...' : 'Verifying...';

            submitButton.textContent = loadingText;
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75');

            // Add spinner
            const spinner = document.createElement('svg');
            spinner.className = 'animate-spin -ml-1 mr-2 h-4 w-4 text-white';
            spinner.innerHTML = `
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            `;
            submitButton.insertBefore(spinner, submitButton.firstChild);
        }
    }

    setupResendButton() {
        let resendCooldown = 0;

        this.resendButton.addEventListener('click', e => {
            if (resendCooldown > 0) {
                e.preventDefault();
                return;
            }

            // Start cooldown (30 seconds)
            resendCooldown = 30;
            this.startResendCooldown();
        });
    }

    startResendCooldown() {
        const originalText = this.resendButton.textContent;
        let countdown = 30;

        const updateButton = () => {
            if (countdown <= 0) {
                this.resendButton.textContent = originalText;
                this.resendButton.disabled = false;
                this.resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                return;
            }

            const isJapanese = document.documentElement.lang === 'ja';
            const text = isJapanese ? `再送信 (${countdown}秒)` : `Resend (${countdown}s)`;

            this.resendButton.textContent = text;
            this.resendButton.disabled = true;
            this.resendButton.classList.add('opacity-50', 'cursor-not-allowed');

            countdown--;
            setTimeout(updateButton, 1000);
        };

        updateButton();
    }

    // Reset timer when new OTP is sent
    resetTimer() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }

        this.startTime = Date.now();

        // Re-enable form elements
        if (this.otpInput) {
            this.otpInput.disabled = false;
            this.otpInput.classList.remove('opacity-50', 'cursor-not-allowed');
            this.otpInput.value = '';
            this.otpInput.focus();
        }

        const submitButton = this.form?.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        // Remove expiration message
        const expiredMessage = document.getElementById('otp-expired-message');
        if (expiredMessage) {
            expiredMessage.remove();
        }

        this.startCountdown();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on OTP verification page
    if (document.getElementById('otp')) {
        window.otpVerification = new OtpVerification();

        // Listen for page refresh after resend
        if (
            window.performance &&
            window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD
        ) {
            // Reset timer if page was refreshed (likely after resend)
            setTimeout(() => {
                if (window.otpVerification) {
                    window.otpVerification.resetTimer();
                }
            }, 100);
        }
    }
});

export default OtpVerification;
