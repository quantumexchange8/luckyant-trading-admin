<script setup>
import {onMounted, ref, watchEffect} from 'vue';
import Chart from 'chart.js/auto';

const chartData = ref({
    labels: [],
    datasets: [],
});
let chartInstance = null;

const fetchData = async () => {
    try {
        const ctx = document.getElementById('masterTradeChart');

        const response = await axios.get('/master/getMasterAnalyticChartData');
        const {labels, datasets} = response.data;

        chartData.value.labels = labels;
        chartData.value.datasets = datasets;

        if (chartInstance) {
            // Update chart data instead of destroying and recreating
            chartInstance.data = chartData.value;
            chartInstance.update();
        } else {
            chartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: chartData.value,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            display: true,
                            labels: {
                                font: {
                                    family: 'Inter, sans',
                                    size: 11,
                                    weight: 'normal',
                                },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxHeight: 6,
                                color: '#8695aa'
                            },
                        },
                    },
                },
            });
        }
    } catch (error) {
        console.error('Error fetching data:', error);
    }
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <div class="h-52 w-full">
        <canvas id="masterTradeChart"></canvas>
    </div>
</template>
