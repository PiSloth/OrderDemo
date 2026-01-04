<div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-bold text-gray-700 dark:text-gray-200">Sale Gram (Daily, Last 1 Month)</h2>
    <div id="saleGramChart" class="w-full"></div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:load', function() {
            function formatTwoDecimals(val) {
                if (val === null || typeof val === 'undefined') {
                    return '';
                }

                const num = Number(val);
                if (Number.isNaN(num)) {
                    return '';
                }

                return num.toFixed(2);
            }

            function isDarkMode() {
                return document.documentElement.classList.contains('dark');
            }

            function resolveTailwindColor(className, mode) {
                try {
                    const el = document.createElement('span');
                    el.className = className;
                    el.style.position = 'absolute';
                    el.style.left = '-9999px';
                    el.style.top = '-9999px';
                    el.style.pointerEvents = 'none';
                    document.body.appendChild(el);
                    const color = window.getComputedStyle(el).color;
                    document.body.removeChild(el);
                    return color;
                } catch (e) {
                    return mode === 'dark' ? '#e5e7eb' : '#374151';
                }
            }

            function resolveTailwindBorderColor(className, mode) {
                try {
                    const el = document.createElement('span');
                    el.className = className;
                    el.style.position = 'absolute';
                    el.style.left = '-9999px';
                    el.style.top = '-9999px';
                    el.style.pointerEvents = 'none';
                    el.style.borderTopWidth = '1px';
                    el.style.borderStyle = 'solid';
                    document.body.appendChild(el);
                    const color = window.getComputedStyle(el).borderTopColor;
                    document.body.removeChild(el);
                    return color;
                } catch (e) {
                    return mode === 'dark' ? '#374151' : '#e5e7eb';
                }
            }

            function getChartThemeOptions() {
                const mode = isDarkMode() ? 'dark' : 'light';
                return {
                    mode,
                    foreColor: resolveTailwindColor('text-gray-700 dark:text-gray-200', mode),
                    gridBorderColor: resolveTailwindBorderColor('border border-gray-200 dark:border-gray-700', mode),
                    tooltipTheme: mode,
                };
            }

            const theme = getChartThemeOptions();


            var options = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: {
                    },
                    foreColor: theme.foreColor,
                    }
                },
                series: [{
                    name: 'Sale Gram',
                    data: @json($saleGramData)
                }],
                xaxis: {
                    categories: @json($dates),
                    title: {
                        text: 'Date'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Sale Gram'
                    },
                    labels: {
                        formatter: formatTwoDecimals,
                    },
                },
                tooltip: {
                    y: {
                        formatter: formatTwoDecimals,
                    theme: theme.tooltipTheme,
                    },
                colors: ['#3b82f6'],
                stroke: {
                    curve: 'smooth'
                },
                    borderColor: theme.gridBorderColor,
                    borderColor: '#e5e7eb'
                theme: { mode: theme.mode },
                },
            };
            var chart = new ApexCharts(document.querySelector("#saleGramChart"), options);
            chart.render();
        });
    </script>
@endpush
