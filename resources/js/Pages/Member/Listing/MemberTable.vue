<script setup>
import {onMounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {FilterMatchMode} from "@primevue/core/api";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import dayjs from "dayjs";
import Popover from "primevue/popover";
import {CloudDownloadIcon, SearchLgIcon, SlidersOneIcon, XIcon} from "@/Components/Icons/outline.jsx";
import Loading from "@/Components/Loading.vue";
import Select from "primevue/select";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Button from "primevue/button";
import Column from "primevue/column";
import Card from "primevue/card";
import DataTable from "primevue/datatable";
import DatePicker from "primevue/datepicker";
import Tag from "primevue/tag";
import Input from "@/Components/Input.vue";
import {XCircleIcon} from "@heroicons/vue/outline";
import {useLangObserver} from "@/Composables/localeObserver.js";
import MemberTableAction from "@/Pages/Member/Listing/MemberTableAction.vue";
import {
    IconLoader
} from "@tabler/icons-vue";
import toast from "@/Composables/toast.js";

const props = defineProps({
    kycStatus: String
});

const isLoading = ref(false);
const dt = ref(null);
const users = ref([]);
const exportTable = ref('no');
const {formatAmount, formatType} = transactionFormat();
const {locale} = useLangObserver();
const totalRecords = ref(0);
const first = ref(0);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    kyc_status: { value: props.kycStatus, matchMode: FilterMatchMode.EQUALS },
    leader_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    country: { value: null, matchMode: FilterMatchMode.EQUALS },
    rank: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };

    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('member.getMemberListingData', params);
            const response = await fetch(url);
            const results = await response.json();

            users.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        users.value = [];
        totalRecords.value = 0;
        isLoading.value = false;
    }
};
const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onFilter = (event) => {
    lazyParams.value.filters = filters.value ;
    loadLazyData(event);
};

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
    getLeaders();
    getCountries();
    getRanks();
}

const leaders = ref();
const loadingLeaders = ref(false);

const getLeaders = async () => {
    loadingLeaders.value = true;
    try {
        const response = await axios.get('/getLeaders');
        leaders.value = response.data;
    } catch (error) {
        console.error('Error fetching leaders:', error);
    } finally {
        loadingLeaders.value = false;
    }
};

const selectedDate = ref([]);

const clearJoinDate = () => {
    selectedDate.value = [];
}

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_date'].value = startDate;
        filters.value['end_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

const countries = ref();
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/getCountries');
        countries.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

const ranks = ref();
const loadingRanks = ref(false);

const getRanks = async () => {
    loadingRanks.value = true;
    try {
        const response = await axios.get('/getRanks');
        ranks.value = response.data;
    } catch (error) {
        console.error('Error fetching ranks:', error);
    } finally {
        loadingRanks.value = false;
    }
};

onMounted(() => {
    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };

    loadLazyData();
    checkExportStatus();
});

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 300)
);

watch(() => props.kycStatus, () => {
    filters.value['kyc_status']['value'] = props.kycStatus;
})

watch([filters.value['kyc_status'], filters.value['leader_id'], filters.value['country'], filters.value['rank']], () => {
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['leader_id'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
    filters.value['country'].value = null;
    filters.value['rank'].value = null;

    selectedDate.value = [];
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

const getSeverity = (status) => {
    switch (status) {
        case 'Unverified':
            return 'danger';

        case 'Verified':
            return 'success';

        case 'Pending':
            return 'info';
    }
}

const exportStatus = ref(false);
const exportingFile = ref(false);
const statusMessage = ref('');
const downloadLink = ref(null);
let checkInterval = null;

// Function to start export and get Job ID
const exportReport = () => {
    exportStatus.value = true;
    exportingFile.value = true;

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
    };

    // Construct the URL
    const url = route('member.getMemberListingData'); // Get the base URL

    // Start export and get the Job ID
    axios.get(url, { params })
        .then((response) => {
            if (response.data.status === 'success') {
                statusMessage.value = 'Export started. Please wait...';

                // Start checking export status every 2 seconds
                checkInterval = setInterval(() => checkExportStatus(), 2000);
            } else {
                exportingFile.value = false;
                statusMessage.value = 'Failed to start export.';

                toast.add({
                    title: 'Error',
                    message: 'Export Error. Please try again later.',
                    type: 'error',
                });
            }
        })
        .catch((error) => {
            exportingFile.value = false;
            toast.add({
                title: 'Error',
                message: 'Export Error. Please try again later.',
                type: 'error',
            });
            console.error('Error occurred during export:', error);
        });
};

// Function to check export status
const checkExportStatus = async () => {
    try {
        const response = await axios.get(route('member.checkExportStatus'));

        if (response.data.status === 'completed') {
            clearInterval(checkInterval);
            downloadLink.value = response.data.file_url; // Set the file download URL
            statusMessage.value = 'Export completed. Ready to download.';
            exportingFile.value = false;
            exportStatus.value = false;
        } else if (response.data.status === 'failed') {
            clearInterval(checkInterval);
            exportingFile.value = false;
            exportStatus.value = false;
        } else {
            statusMessage.value = 'Export in progress...';
            exportingFile.value = true;
            exportStatus.value = true;
        }
    } catch (error) {
        console.error('Error checking export status:', error);
        clearInterval(checkInterval);
        statusMessage.value = 'An error occurred while checking export status.';
        exportingFile.value = false;
        exportStatus.value = false;
    }
};

const downloadFile = () => {
    if (downloadLink.value) {
        // Create an anchor tag to trigger download
        const anchor = document.createElement('a');
        anchor.href = downloadLink.value;
        anchor.download = ''; // Optional: Provide a default filename
        anchor.target = '_blank'; // Open in a new tab if needed
        anchor.click();

        axios.delete(route('member.deleteReport'))
            .then(response => {
                downloadLink.value = null;
                statusMessage.value = 'Download started and file deleted successfully.';
            })
            .catch(error => {
                console.error('Error deleting file:', error);
                statusMessage.value = 'Error occurred while deleting the file.';
            });

        statusMessage.value = 'Download started.';
    } else {
        statusMessage.value = 'Download link not available.';
    }
};
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    :value="users"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    lazy
                    paginator
                    removableSort
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :first="first"
                    :rows="10"
                    v-model:filters="filters"
                    ref="dt"
                    dataKey="id"
                    :totalRecords="totalRecords"
                    :loading="isLoading"
                    @page="onPage($event)"
                    @sort="onSort($event)"
                    @filter="onFilter($event)"
                    :globalFilterFields="['name', 'email', 'username']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-start self-stretch pb-3 md:pb-5">
                            <div class="relative w-full md:w-60">
                                <InputIconWrapper class="md:col-span-2">
                                    <template #icon>
                                        <SearchLgIcon aria-hidden="true" class="w-5 h-5" />
                                    </template>
                                    <Input
                                        withIcon
                                        id="search"
                                        type="text"
                                        class="block w-full"
                                        placeholder="Search"
                                        v-model="filters['global'].value"
                                    />
                                </InputIconWrapper>
                                <div
                                    v-if="filters['global'].value !== null"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <XCircleIcon aria-hidden="true" class="w-4 h-4"/>
                                </div>
                            </div>
                            <div class="flex justify-between items-start self-stretch w-full gap-3">
                                <Button
                                    class="w-full md:w-28 flex gap-2"
                                    severity="secondary"
                                    outlined
                                    @click="toggle"
                                >
                                    <SlidersOneIcon class="w-4 h-4" />
                                    Filter
                                </Button>
                                <div class="w-full flex justify-end">
                                    <div class="flex flex-col items-end gap-1 w-full">
                                        <Button
                                            v-if="downloadLink"
                                            class="w-full md:w-fit flex gap-2"
                                            severity="success"
                                            @click="downloadFile"
                                            :disabled="exportingFile"
                                        >
                                            Download
                                        </Button>
                                        <Button
                                            v-else
                                            class="w-full md:w-fit flex gap-2"
                                            severity="secondary"
                                            @click="exportReport"
                                            :disabled="exportingFile"
                                        >
                                            <div v-if="exportingFile" class="animate-spin">
                                                <IconLoader size="20" stroke-width="1.5" />
                                            </div>
                                            <CloudDownloadIcon v-else class="w-4 h-4" />
                                            {{ exportingFile ? 'Exporting' : 'Export' }}
                                        </Button>
                                        <div v-if="statusMessage && exportStatus" class="text-sm">
                                            {{ statusMessage }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="flex flex-col">
                            <span>No users</span>
                        </div>
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">Loading users</span>
                            <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                        </div>
                    </template>
                    <template v-if="users?.length > 0">
                        <Column
                            field="name"
                            sortable
                            frozen
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.name') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data" class="flex flex-col">
                                        <div class="flex gap-1 items-center">
                                            <span class="font-semibold">{{ slotProps.data.name }}</span>
                                            <Tag
                                                v-if="slotProps.data.leader_status"
                                                class="!text-xxs"
                                                severity="info"
                                                :value="$t('public.leader')"
                                            />
                                        </div>
                                        <span class="text-gray-400">{{ slotProps.data.email }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="username"
                            :header="$t('public.username')"
                            class="table-cell"
                        >
                            <template #body="{data}">
                                {{ data.username ?? '-' }}
                            </template>
                        </Column>
                        <Column
                            field="dob"
                            header="DOB"
                            class="table-cell"
                            sortable
                        >
                            <template #body="{data}">
                                <span class="uppercase">{{ dayjs(data.dob).format('YYYY/MM/DD') }}</span>
                            </template>
                        </Column>
                        <Column
                            field="leader_id"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.leader') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data.first_leader_name" class="flex flex-col">
                                        <span class="font-semibold">{{ slotProps.data.first_leader_name }}</span>
                                        <span class="text-gray-400">{{ slotProps.data.first_leader_email }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="created_at"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.date') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="uppercase">{{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}</span>
                            </template>
                        </Column>
                        <Column
                            field="upline_id"
                            class="table-cell min-w-40"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.referrer') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data.upline" class="flex flex-col">
                                        <span class="font-semibold">{{ slotProps.data.upline.name }}</span>
                                        <span class="text-gray-400">{{ slotProps.data.upline.email }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="gender"
                            :header="$t('public.gender')"
                            class="table-cell"
                        >
                            <template #body="{data}">
                                {{ data.gender ? $t(`public.${data.gender}`) : '-' }}
                            </template>
                        </Column>
                        <Column
                            field="active_fund"
                            class="table-cell min-w-32"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.fund') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="font-bold">
                                    $ {{ formatAmount((slotProps.data.active_pamm_sum_subscription_amount ?? 0) + (slotProps.data.active_copy_trade_sum_meta_balance)) }}
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="country"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.country') }}</span>
                            </template>
                            <template #body="slotProps">
                               {{ slotProps.data.of_country?.name }}
                            </template>
                        </Column>
                        <Column
                            field="rank"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.rank') }}</span>
                            </template>
                            <template #body="slotProps">
                               <span class="uppercase text-xs font-semibold">{{ slotProps.data.rank?.name[locale] }}</span>
                            </template>
                        </Column>
                        <Column
                            field="kyc_approval"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.status') }}</span>
                            </template>
                            <template #body="slotProps">
                               <Tag
                                   :severity="getSeverity(slotProps.data.kyc_approval)"
                                   :value="$t(`public.${formatType(slotProps.data.kyc_approval).toLowerCase().replace(/\s+/g, '_')}`)"
                               />
                            </template>
                        </Column>
                        <Column
                            field="action"
                            class="table-cell"
                            frozen
                            align-frozen="right"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.action') }}</span>
                            </template>
                            <template #body="slotProps">
                                <MemberTableAction
                                    :user="slotProps.data"
                                />
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Leader-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_leaders') }}
                </div>
                <Select
                    v-model="filters['leader_id'].value"
                    :options="leaders"
                    optionLabel="name"
                    placeholder="Select a leader"
                    class="w-full"
                    filter
                    :filter-fields="['name', 'email']"
                    :loading="loadingLeaders"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            {{ slotProps.value.name }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex flex-col max-w-[220px] truncate">
                            <div>{{ slotProps.option.name }}</div>
                            <div class="text-gray-400">{{ slotProps.option.email }}</div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Filter Join Date-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_date') }}
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedDate"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-2 text-gray-400 select-none cursor-pointer bg-transparent"
                        @click="clearJoinDate"
                    >
                        <XIcon class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- Filter Country -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_country') }}
                </div>
                <Select
                    v-model="filters['country'].value"
                    :options="countries"
                    optionLabel="name"
                    :placeholder="$t('public.select_country')"
                    class="w-full"
                    filter
                    :filter-fields="['name']"
                    :loading="loadingCountries"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            {{ slotProps.value.name }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div>{{ slotProps.option.name }}</div>
                    </template>
                </Select>
            </div>

            <!-- Filter Status -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_rank') }}
                </div>
                <Select
                    v-model="filters['rank'].value"
                    :options="ranks"
                    optionLabel="name"
                    :placeholder="$t('public.select_rank')"
                    class="w-full"
                    filter
                    :filter-fields="['name']"
                    :loading="loadingRanks"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            {{ slotProps.value.name[locale] }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div>{{ slotProps.option.name[locale] }}</div>
                    </template>
                </Select>
            </div>

            <Button
                type="button"
                severity="info"
                class="w-full"
                outlined
                @click="clearAll"
            >
                Clear All
            </Button>
        </div>
    </Popover>
</template>
