<script setup lang="tsx">
import {
    FlexRender,
    getCoreRowModel,
    useVueTable,
    createColumnHelper,
    RowSelectionState,
    getSortedRowModel,
} from '@tanstack/vue-table'
import axios from 'axios'
import {h, ref, onMounted, watch, watchEffect} from "vue";
import {alertTriangle,  ArrowsDownIcon,
    ArrowsUpIcon, ChevronLeftDoubleIcon,
    ChevronLeftIcon, ChevronRightDoubleIcon,
    ChevronRightIcon, SwitchVertical01Icon} from "@/Components/Icons/outline";
import IndeterminateCheckbox from "@/Components/Checkbox.vue"
import Button from "@/Components/Button.vue"
import ColumnName from "@/Components/TanstackTable/ColumnName.vue"
import Action from "@/Pages/Transaction/TransactionPending/Partials/Action.vue";
import {transactionFormat} from "@/Composables/index.js";
import BaseListbox from "@/Components/BaseListbox.vue";
import Modal from "@/Components/Modal.vue";
import debounce from "lodash/debounce";
import Input from "@/Components/Input.vue";
import {usePage} from "@inertiajs/vue3";
import NoData from "@/Components/NoData.vue";

const { formatDateTime, formatAmount } = transactionFormat();
const columnHelper = createColumnHelper()
const pageSize = ref(10);
const pageIndex = ref(1);
const action = ref('');
const isChecked = ref([]);
const totalAmountSelected = ref(0);
const props = defineProps({
    search: String,
    leader: Object,
    date: String,
    type: String,
    exportStatus: String,
})
const emit = defineEmits(['update:totalPendingDeposits', 'update:totalPendingWithdrawals', 'update:totalRejectedRequest', 'update:exportStatus'])

const pageSizes = [
    {value: 5, label: 5 },
    {value: 10, label: 10 },
    {value: 20, label: 20 },
    {value: 50, label: 50 },
    {value: 100, label: 100 },
]

// Updated column definitions based on backend data structure
const columns = [
    {
        id: 'select',
        header: ({ table }: { table: any }) => {
            return (
                <IndeterminateCheckbox
                    checked={table.getIsAllRowsSelected()}
                    indeterminate={table.getIsSomeRowsSelected()}
                    onChange={table.getToggleAllRowsSelectedHandler()}
                ></IndeterminateCheckbox>
            )
        },
        cell: ({ row }: { row: any }) => {
            return (
                <IndeterminateCheckbox
                    checked={row.getIsSelected()}
                    disabled={!row.getCanSelect()}
                    onChange={row.getToggleSelectedHandler()}
                ></IndeterminateCheckbox>
            )
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'user',
        header: 'Name',
        enableSorting: false,
        cell: ({ row }) => h(ColumnName, {
            user: row.original.user,
        }),
    },
    {
        accessorKey: 'user.first_leader',
        header: 'First Leader',
        cell: info => info.getValue(),
        enableSorting: false,
    },
    {
        accessorKey: 'transaction_number',
        header: 'Transaction ID',
    },
    {
        accessorKey: 'payment_method',
        header: 'Payment Method',
        enableSorting: false,
    },
    {
        accessorKey: 'to_wallet_address',
        header: 'USDT Address/Bank Acc',
        enableSorting: false,
    },
    {
        accessorKey: 'amount',
        header: 'Amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'transaction_charges',
        header: 'Payment Charges',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'transaction_amount',
        header: 'Transaction Amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'profit_amount',
        header: 'Profit Amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
        enableSorting: false,
    },
    {
        accessorKey: 'bonus_amount',
        header: 'Bonus Amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
        enableSorting: false,
    },
    {
        accessorKey: 'action',
        header: 'Action',
        enableSorting: false,
        cell: ({ row }) => h(Action, {
            transaction: row.original,
        }),
    },
]

const data = ref({data: []})
const rowSelection = ref<RowSelectionState>({})
const sorting = ref([])

const getResults = async (page = 1, paginate = 10, search = '', leader = null, date = '', columnName = sorting.value) => {
    try {
        let url = `/transaction/getPendingTransaction/Withdrawal?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (search) {
            url += `&search=${search}`;
        }

        if (leader) {
            url += `&leader=${leader.value}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (columnName) {
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        data.value = response.data.Withdrawal;
        emit('update:totalPendingDeposits', response.data.totalPendingDeposits);
        emit('update:totalPendingWithdrawals', response.data.totalPendingWithdrawals);
    } catch (error) {
        console.error(error);
    }
}

// Fetch initial data on component mount
// Fetch initial data on component mount
onMounted(() => {
    getResults();
});

const table = useVueTable({
    get data() {
        return data.value.data
    },
    getRowId(originalRow) {
        return originalRow.id
    },
    columns,
    get rowCount() {
        return data.value.total
    },
    getSortedRowModel: getSortedRowModel(),
    enableRowSelection: true,
    manualPagination: true,
    manualSorting: true,
    state: {
        get rowSelection() {
            return rowSelection.value
        },
        pagination: {
            get pageIndex() {
                return data.value.current_page - 1
            },
            get pageSize() {
                return data.value.per_page
            }
        },
        get sorting() {
            return sorting.value
        }
    },
    onRowSelectionChange: updateOrValue => {
        rowSelection.value =
            typeof updateOrValue === 'function'
                ? updateOrValue(rowSelection.value)
                : updateOrValue
    },
    getCoreRowModel: getCoreRowModel(),
    onSortingChange: updaterOrValue => {
        sorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(sorting.value)
                : updaterOrValue
    },
    autoResetPage: false,
})

watch(
    [sorting, pageSize],
    ([sortingValue, pageSizeValue]) => {
        getResults(1, pageSizeValue, '', '', '', sortingValue);
    }
);

const internalChange = ref(false);
const handlePageChange = (action, index) => {
    internalChange.value = true;
    if (action === 'previous') {
        pageIndex.value = index;
        getResults(pageIndex.value, pageSize.value);
    } else {
        pageIndex.value = index + 2;
        getResults(pageIndex.value, pageSize.value);
    }
    internalChange.value = false;
};

const handleFirstLastPage = (newAction) => {
    internalChange.value = true;
    if (newAction === 'goToFirstPage') {
        action.value = newAction
        pageIndex.value = 1;
        getResults(pageIndex.value, pageSize.value);
    } else {
        action.value = newAction
        pageIndex.value = table.getPageCount();
        getResults(pageIndex.value, pageSize.value);
    }
    internalChange.value = false;
};

watch(pageIndex, (newPageNumber) => {
    if (!internalChange.value) {
        pageIndex.value = newPageNumber
    }
});

watch(
    [() => props.search, () => props.leader, () => props.date],
    debounce(([searchValue, leaderValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, leaderValue, dateValue, sorting.value);
    }, 300)
);

const calculateTotalAmount = () => {
    const selectedRows = table.getSelectedRowModel().rows;
    let totalAmount = 0;

    selectedRows.forEach(row => {
        totalAmount += parseFloat(row.original.transaction_amount);
    });

    totalAmountSelected.value = totalAmount;
};

watch(rowSelection, (newValue) => {
    calculateTotalAmount()
    isChecked.value = Object.keys(newValue)
})

const transactionModal = ref(false);
const modalComponent = ref(null);
const showAlert = ref(false);
const intent = ref(null);
const alertTitle = ref('');
const alertMessage = ref(null);

const openTransactionModal = (componentType: any) => {
    transactionModal.value = true;
    if (componentType === 'approve') {
        modalComponent.value = 'Approve Transaction';
    } else if (componentType === 'reject') {
        modalComponent.value = 'Reject Transaction';
    }
}

const approveTransaction = async () => {
    try {
        await axios.post('/transaction/approveTransaction', {
            id: isChecked.value,
            type: 'approve_selected',
        });

        closeModal()
        showAlert.value = true
        intent.value = 'success'
        alertTitle.value = 'Withdrawal approved'
        alertMessage.value = 'This withdrawal request has been approved successfully.'

        totalAmountSelected.value = 0;
        await getResults(1, pageSize.value, '', '', '', sorting.value);
    } catch (error) {
        console.error('Error approving transaction:', error);
    }
};

const rejectTransaction = async () => {
    try {
        await axios.post('/transaction/rejectTransaction', {
            id: isChecked.value,
            type: 'reject_selected',
        });

        closeModal()
        showAlert.value = true
        intent.value = 'success'
        alertTitle.value = 'Withdrawal reject'
        alertMessage.value = 'This withdrawal request has been rejected successfully.'

        totalAmountSelected.value = 0;
        await getResults(1, pageSize.value, '', '', '', sorting.value);
    } catch (error) {
        console.error('Error approving transaction:', error);
    }
};

const closeModal = () => {
    transactionModal.value = false
    modalComponent.value = null;
}

const exportTransactions = () => {
    let url = `/transaction/getPendingTransaction/Withdrawal?exportStatus=yes`;

    if (props.search) {
        url += `&search=${props.search}`;
    }

    if (props.leader) {
        url += `&leader=${props.leader.value}`;
    }

    if (props.date) {
        url += `&date=${props.date}`;
    }

    window.location.href = url;
}

watch(
    () => props.exportStatus,
    (newStatus) => {
        if (newStatus === 'yes') {
            exportTransactions();
            emit('update:exportStatus', '');
        }
    }
);

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
    <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
        <div class="flex justify-end items-center gap-2">
            <div class="text-sm">
                Size
            </div>
            <div>
                <BaseListbox
                    :options="pageSizes"
                    v-model="pageSize"
                />
            </div>
        </div>
        <div
            v-if="data.data.length === 0"
            class="w-full flex items-center justify-center"
        >
            <NoData />
        </div>
        <div v-else class="relative overflow-x-auto sm:rounded-lg">
            <table class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
                <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                >
                    <th
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :colSpan="header.colSpan"
                        scope="col"
                        class="p-3"
                        :class="{
                        'cursor-pointer select-none': header.column.getCanSort(),
                        'bg-primary-100': header.column.getIsSorted()
                    }"
                    >
                        <div class="flex items-center gap-2">
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                            <div v-if="header.column.getIsSorted()">
                                <ArrowsUpIcon
                                    v-if="header.column.getIsSorted() === 'asc'"
                                    class="w-4 h-4 text-primary-600 dark:text-white"
                                />
                                <ArrowsDownIcon
                                    v-else-if="header.column.getIsSorted() === 'desc'"
                                    class="w-4 h-4 text-primary-600 dark:text-white"
                                />
                            </div>
                            <div v-else>
                                <SwitchVertical01Icon v-if="header.column.getCanSort()" class="w-4 h-4" />
                            </div>
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr
                    v-for="row in table.getRowModel().rows" :key="row.id"
                    class="bg-white dark:bg-transparent text-xs font-normal text-gray-900 dark:text-white border-b dark:border-gray-800"
                >
                    <td v-for="cell in row.getVisibleCells()" :key="cell.id" className="p-3">
                        <FlexRender
                            :render="cell.column.columnDef.cell"
                            :props="cell.getContext()"
                        />
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="py-4 flex flex-col md:flex-row gap-2 md:items-center md:justify-between w-full">
                <div class="text-sm">
                    Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }} - {{ table.getRowCount() }} results
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        variant="gray"
                        type="button"
                        @click="handleFirstLastPage('goToFirstPage')"
                        pill
                        size="sm"
                        v-slot="{iconSizeClasses}"
                        :disabled="!table.getCanPreviousPage()"
                    >
                        <ChevronLeftDoubleIcon class="w-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="gray"
                        @click="handlePageChange('previous', table.getState().pagination.pageIndex)"
                        :disabled="!table.getCanPreviousPage()"
                        pill
                        size="sm"
                        v-slot="{iconSizeClasses}"
                    >
                        <ChevronLeftIcon class="w-4" />
                    </Button>
                    <Input
                        id="page"
                        type="number"
                        min="1"
                        :max="table.getPageCount()"
                        class="block w-20"
                        placeholder="Page"
                        v-model="pageIndex"
                    />
                    <Button
                        type="button"
                        variant="gray"
                        @click="handlePageChange('next', table.getState().pagination.pageIndex)"
                        :disabled="!table.getCanNextPage()"
                        pill
                        size="sm"
                        v-slot="{iconSizeClasses}"
                    >
                        <ChevronRightIcon class="w-4" />
                    </Button>
                    <Button
                        variant="gray"
                        type="button"
                        @click="handleFirstLastPage('goToLastPage')"
                        pill
                        size="sm"
                        v-slot="{iconSizeClasses}"
                        :disabled="!table.getCanNextPage()"
                    >
                        <ChevronRightDoubleIcon class="w-4" />
                    </Button>
                </div>
            </div>
        </div>
        <div v-if="data.data.length !== 0" class="flex flex-col sm:flex-row sm:justify-between items-center">
            <div class="text-xl font-semibold">
                Total Amount: ${{ formatAmount(totalAmountSelected) }}
            </div>
            <div class="pt-3 px-2 grid grid-cols-2 gap-4">
                <Button
                    type="button"
                    variant="success"
                    class="px-6 justify-center"
                    :disabled="totalAmountSelected === 0"
                    @click="openTransactionModal('approve')"
                >
                    Approve all
                </Button>
                <Button
                    type="button"
                    variant="danger"
                    class="px-6 justify-center"
                    :disabled="totalAmountSelected === 0"
                    @click="openTransactionModal('reject')"
                >
                    Reject all
                </Button>
            </div>
        </div>
    </div>

    <Modal :show="transactionModal" :title="modalComponent" @close="closeModal" max-width="lg">

        <div v-if="modalComponent === 'Approve Transaction'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Approve withdrawal</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to approve a total withdrawal of ${{ formatAmount(totalAmountSelected) }}?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="approveTransaction">Confirm</Button>
            </div>
        </div>

        <!-- Reject -->
        <div v-if="modalComponent === 'Reject Transaction'">
            <div class="px-2 space-y-2">
                <alertTriangle />
                <h2 class="text-xl font-semibold dark:text-white pt-5">Reject withdrawal</h2>
                <div class="text-sm font-normal dark:text-gray-400">
                    Do you want to reject a total withdrawal of ${{ formatAmount(totalAmountSelected) }}?
                </div>
            </div>
            <div class="pt-5 px-2 grid grid-cols-2 gap-4">
                <Button type="button" variant="secondary" class="px-6 justify-center" @click="closeModal">
                    Cancel
                </Button>
                <Button class="px-6 justify-center" @click.prevent="rejectTransaction">Confirm</Button>
            </div>
        </div>

    </Modal>
</template>
