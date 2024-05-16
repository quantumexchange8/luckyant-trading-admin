<script setup>
import {CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";
import {transactionFormat} from "@/Composables/index.js";
import TotalDepositChart from "@/Pages/Dashboard/TotalDepositChart.vue";
import {ref} from "vue";
import BaseListbox from "@/Components/BaseListbox.vue";

const { formatAmount } = transactionFormat();

const selectedMonth = ref(new Date().getMonth() + 1); // Initialize with the current month (1-12)
const selectedYear = ref(new Date().getFullYear());
const totalDeposit = ref(null);
const totalMonthDeposit = ref(null);

const months = Array.from({ length: 12 }, (_, index) => {
    const monthNumber = (index + 1) % 12 || 12; // Adjust the month number to be in the range 1-12
    const monthLabel = new Date(0, monthNumber - 1).toLocaleString('default', { month: 'long' });
    return { value: monthNumber, label: monthLabel };
});

const currentYear = new Date().getFullYear();
const startYear = 2024;
const endYear = Math.max(currentYear, startYear); // Ensures we include the current year if it's greater than startYear

const years = [];
for (let year = startYear; year <= endYear; year++) {
    years.push({ value: year, label: year.toString() });
}
</script>

<template>
    <div class="flex flex-col items-start gap-5 bg-white dark:bg-gray-900 rounded-md shadow-md p-5 w-full">
        <div class="flex gap-5 flex-col sm:flex-row items-start">
            <div class="flex gap-5 items-center">
                <div class="rounded-full flex items-center justify-center w-10 h-10 bg-success-200">
                    <CurrencyDollarCircleIcon class="text-success-500 w-5 h-5" />
                </div>
                <div class="flex flex-col">
                    <div class="text-sm text-gray-400 dark:text-gray-600">
                        Total Deposit
                    </div>
                    <div class="text-lg text-gray-900 dark:text-white">
                    <span v-if="totalDeposit !== null">
                          $ {{ formatAmount(totalDeposit ? totalDeposit : 0) }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex gap-5 items-center">
                <div class="rounded-full flex items-center justify-center w-10 h-10 bg-gray-200">
                    <CurrencyDollarCircleIcon class="text-gray-500 w-5 h-5" />
                </div>
                <div class="flex flex-col">
                    <div class="text-sm text-gray-400 dark:text-gray-600">
                        Total Deposit (Month: {{ selectedMonth }}, Year: {{ selectedYear }})
                    </div>
                    <div class="text-lg text-gray-900 dark:text-white">
                    <span v-if="totalMonthDeposit !== null">
                          $ {{ formatAmount(totalMonthDeposit ? totalMonthDeposit : 0) }}
                        </span>
                        <span v-else>
                          Loading...
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row sm:justify-between w-full items-center">
            <div class="text-xl text-gray-900 dark:text-white font-semibold">
                Monthly Deposit to Live Account
            </div>
            <div class="flex gap-5 w-80">
                <BaseListbox
                    v-model="selectedMonth"
                    :options="months"
                    class="w-40"
                />
                <BaseListbox
                    v-model="selectedYear"
                    :options="years"
                    class="w-40"
                />
            </div>
        </div>

        <TotalDepositChart
            :selectedMonth="selectedMonth"
            :selectedYear="selectedYear"
            @update:totalDeposit="totalDeposit = $event"
            @update:totalMonthDeposit="totalMonthDeposit = $event"
        />
    </div>
</template>
