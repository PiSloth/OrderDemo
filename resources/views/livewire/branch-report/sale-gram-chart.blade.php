<div class="max-w-4xl p-6 mx-auto my-4 bg-white rounded-lg shadow-xl dark:bg-gray-800">
    <h2 class="mb-4 text-lg font-bold text-gray-700 dark:text-gray-200">Sale Gram (Daily, Last 1 Month)</h2>
    <div id="saleGramChart"></div>
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

            var options = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: {
                        show: false
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
                    },
                },
                colors: ['#3b82f6'],
                stroke: {
                    curve: 'smooth'
                },
                grid: {
                    borderColor: '#e5e7eb'
                },
            };
            var chart = new ApexCharts(document.querySelector("#saleGramChart"), options);
            chart.render();
        });
    </script>
@endpush
