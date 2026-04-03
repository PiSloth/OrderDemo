<div data-theme-toggle-container>
    <button type="button"
        class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-2 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-slate-700"
        data-theme-toggle-button title="Toggle color theme">
        <span data-theme-label>Light</span>
        <span class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200"
            data-theme-track>
            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-amber-500 shadow transition-transform duration-200 dark:text-slate-700"
                data-theme-thumb>
                <svg class="hidden h-3.5 w-3.5" data-theme-icon-light viewBox="0 0 20 20" fill="currentColor"
                    aria-hidden="true">
                    <path
                        d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.536 5.95a1 1 0 011.414 0l.708.707a1 1 0 11-1.414 1.415l-.708-.708a1 1 0 010-1.414zM17 9a1 1 0 100 2h1a1 1 0 100-2h-1zM5.122 15.95a1 1 0 010 1.414l-.708.708A1 1 0 113 16.657l.708-.707a1 1 0 011.414 0zM4 9a1 1 0 100 2H3a1 1 0 100-2h1zm1.122-4.95a1 1 0 10-1.414 1.414L4.414 6.17a1 1 0 001.414-1.414l-.707-.707zM10 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1z" />
                </svg>
                <svg class="hidden h-3.5 w-3.5" data-theme-icon-dark viewBox="0 0 20 20" fill="currentColor"
                    aria-hidden="true">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                </svg>
            </span>
        </span>
    </button>
</div>

<script>
    (() => {
        if (window.initializeThemeToggles) {
            window.initializeThemeToggles();
            return;
        }

        window.updateThemeToggleUi = function() {
            const darkMode = document.documentElement.classList.contains('dark');

            document.querySelectorAll('[data-theme-toggle-container]').forEach((container) => {
                const button = container.querySelector('[data-theme-toggle-button]');
                const lightIcon = container.querySelector('[data-theme-icon-light]');
                const darkIcon = container.querySelector('[data-theme-icon-dark]');
                const label = container.querySelector('[data-theme-label]');
                const track = container.querySelector('[data-theme-track]');
                const thumb = container.querySelector('[data-theme-thumb]');

                if (!button || !lightIcon || !darkIcon || !label || !track || !thumb) {
                    return;
                }

                lightIcon.classList.toggle('hidden', darkMode);
                darkIcon.classList.toggle('hidden', !darkMode);
                label.textContent = darkMode ? 'Dark' : 'Light';
                button.setAttribute('aria-pressed', darkMode ? 'true' : 'false');
                button.setAttribute('title', darkMode ? 'Switch to light mode' : 'Switch to dark mode');
                track.classList.toggle('bg-slate-300', !darkMode);
                track.classList.toggle('bg-slate-700', darkMode);
                thumb.classList.toggle('translate-x-1', !darkMode);
                thumb.classList.toggle('translate-x-5', darkMode);
            });
        };

        window.toggleColorTheme = function() {
            const darkMode = !document.documentElement.classList.contains('dark');

            document.documentElement.classList.toggle('dark', darkMode);
            localStorage.setItem('color-theme', darkMode ? 'dark' : 'light');
            window.updateThemeToggleUi();
        };

        window.initializeThemeToggles = function() {
            document.querySelectorAll('[data-theme-toggle-button]').forEach((button) => {
                if (button.dataset.themeBound === 'true') {
                    return;
                }

                button.dataset.themeBound = 'true';
                button.addEventListener('click', window.toggleColorTheme);
            });

            window.updateThemeToggleUi();
        };

        document.addEventListener('DOMContentLoaded', window.initializeThemeToggles);
        document.addEventListener('livewire:navigated', window.initializeThemeToggles);
        window.initializeThemeToggles();
    })();
</script>
