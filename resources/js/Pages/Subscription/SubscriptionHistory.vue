<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import {ref} from "vue";
import {CloudDownloadIcon} from "@/Components/Icons/outline.jsx";
import {SearchIcon, RefreshIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import Button from "@/Components/Button.vue";
// import PendingSubscribeTable from "@/Pages/Subscription/Partials/PendingSubscribeTable.vue";

const refresh = ref(false);
const isLoading = ref(false);
const search = ref('');
const date = ref('');
const filter = ref('');
const exportStatus = ref(false);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

function refreshTable() {
    search.value = '';
    date.value = '';
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

const exportTransaction = () => {
    exportStatus.value = true;
}
</script>

<template>
<AuthenticatedLayout title="Subscription History">
    <template #header>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold leading-tight">
                    Subscribers Request
                </h2>
               
            </div>

            <div>
                <Button
                    type="button"
                    class="justify-center w-full gap-2 border border-gray-600 text-white text-sm dark:hover:bg-gray-600"
                    variant="transparent"
                    v-slot="{ iconSizeClasses }"
                    @click="exportTransaction"
                >
                    <div class="inline-flex items-center">
                        <CloudDownloadIcon
                            aria-hidden="true"
                            class="mr-2 w-5 h-5"
                        />
                        <span class="text-black dark:text-gray-300">Export as Excel</span>
                    </div>
                </Button>
            </div>

        </div>
    </template>
</AuthenticatedLayout>

</template>