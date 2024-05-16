<script setup>
import Loading from "@/Components/Loading.vue";
import {onMounted, ref, watch} from "vue";
import Chart from 'chart.js/auto'

const props = defineProps({
    selectedMonth: Number,
    selectedYear: Number,
})

const chartData = ref({
    labels: [],
    datasets: [],
});
const isLoading = ref(false)
const month = ref(props.selectedMonth)
const year = ref(props.selectedYear)
const emit = defineEmits(['update:totalDeposit', 'update:totalMonthDeposit'])
let chartInstance = null;

const fetchData = async () => {
    try {
        if (chartInstance) {
            chartInstance.destroy();
        }

        const ctx = document.getElementById('dailyTotalDeposit');

        isLoading.value = true;

        const response = await axios.get('/getTotalDepositByDays', { params: { month: month.value, year: year.value } });
        const { labels, datasets } = response.data.chartData;
        emit('update:totalMonthDeposit', response.data.totalMonthDeposit);
        emit('update:totalDeposit', response.data.totalDeposit);

        chartData.value.labels = labels;
        chartData.value.datasets = datasets;

        if(datasets.length > 0) {
            if(datasets[0]) {
                datasets[0].backgroundColor = (context) => {
                    const bgColor = [
                        'rgba(18, 183, 106, 0.4)',
                        'rgba(18, 183, 106, 0)'
                    ];

                    if (!context.chart.chartArea) {
                        return;
                    }

                    const { ctx, data, chartArea: {top, bottom} } = context.chart;
                    const gradientBg = ctx.createLinearGradient(0, top, 0, bottom);
                    gradientBg.addColorStop(0, bgColor[0]);
                    gradientBg.addColorStop(1, bgColor[1]);
                    return gradientBg;
                };
            }
            if(datasets[1]) {
                datasets[1].backgroundColor = (context) => {
                    const bgColor = [
                        'rgba(255, 45, 85, 0.40)',
                        'rgba(255, 45, 85, 0.00)'
                    ];

                    if (!context.chart.chartArea) {
                        return;
                    }

                    const { ctx, data, chartArea: {top, bottom} } = context.chart;
                    const gradientBg = ctx.createLinearGradient(0, top, 0, bottom);
                    gradientBg.addColorStop(0, bgColor[0]);
                    gradientBg.addColorStop(1, bgColor[1]);
                    return gradientBg;
                };
            }
        }

        isLoading.value = false

        // Create the chart after updating chartData
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: chartData.value,
            options: {
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                maintainAspectRatio: false,
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                var ranges = [
                                    { divider: 1e6, suffix: 'M' },
                                    { divider: 1e3, suffix: 'k' }
                                ];
                                function formatNumber(n) {
                                    for (var i = 0; i < ranges.length; i++) {
                                        if (n >= ranges[i].divider) {
                                            return (n / ranges[i].divider).toString() + ranges[i].suffix;
                                        }
                                    }
                                    return n;
                                }
                                return formatNumber(value);
                            },
                            color: '#9DA4AE',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 14,
                                weight: 400,
                            },
                        },
                        grace: '10%',
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            drawTicks: false,
                            color: (ctx) => {
                                return '#D0D5DD'
                            }
                        },
                    },
                    x: {
                        ticks: {
                            color: '#9DA4AE',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 14,
                                weight: 400,
                            },
                        },
                        grid: {
                            drawTicks: false,
                            color: (ctx) => {
                                return 'transparent'
                            }
                        },
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                }
            }
        });
    } catch (error) {
        const ctx = document.getElementById('dailyTotalDeposit');

        isLoading.value = false
        console.error('Error fetching chart data:', error);
    }
}

onMounted(async () => {
    await fetchData(); // Fetch data on mount

    // Watch for changes in the date and fetch data when it changes

    watch(
        [() => props.selectedMonth, () => props.selectedYear], // Array of expressions to watch
        ([newMonth, newYear]) => {
            // This callback will be called when selectedMonth or selectedYear changes.
            month.value = newMonth;
            year.value = newYear;
            fetchData();
        }
    );

});
</script>

<template>
    <div v-if="isLoading" class="flex justify-center">
        <Loading />
    </div>
    <div class="w-full">
        <canvas id="dailyTotalDeposit" height="350"></canvas>
    </div>
</template>
