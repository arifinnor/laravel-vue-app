<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import TransactionController from '@/actions/App/Http/Controllers/Finance/TransactionController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CalendarIcon } from 'lucide-vue-next';
import {
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    type CalendarDate,
    type DateValue,
} from '@internationalized/date';
import { ref, watch, computed, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { cn } from '@/lib/utils';
import DataTable from './DataTable.vue';
import DataTablePagination from './DataTablePagination.vue';
import { createColumns, type Transaction } from './columns';

interface CursorPaginated<T> {
    data: T[];
    next_cursor: string | null;
    prev_cursor: string | null;
    path: string;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface TransactionType {
    id: string;
    code: string;
    name: string;
}

interface Props {
    journalEntries: CursorPaginated<Transaction>;
    transactionTypes: TransactionType[];
    filters: {
        search?: string | null;
        date_from?: string | null;
        date_to?: string | null;
        transaction_type_id?: string | null;
        status?: string | null;
    };
    perPageOptions: number[];
}

const props = defineProps<Props>();

const searchQuery = ref<string>(props.filters.search || '');
const dateFrom = ref<string>(props.filters.date_from || '');
const dateTo = ref<string>(props.filters.date_to || '');
const transactionTypeFilter = ref<string>(props.filters.transaction_type_id || 'all');
const statusFilter = ref<string>(props.filters.status || 'all');
const isInitialMount = ref(true);

// DatePicker state for dateFrom
const isDatePickerOpen = ref(false);
const dateFromValue = ref<DateValue | undefined>(undefined);

// DatePicker state for dateTo
const isDateToPickerOpen = ref(false);
const dateToValue = ref<DateValue | undefined>(undefined);

const dateFormatter = new DateFormatter('en-US', { dateStyle: 'short' });

// Convert string (YYYY-MM-DD) to CalendarDate
const stringToDateValue = (dateString: string | null): CalendarDate | undefined => {
    if (!dateString) {
        return undefined;
    }
    try {
        return parseDate(dateString);
    } catch {
        return undefined;
    }
};

// Convert DateValue to string (YYYY-MM-DD)
const dateValueToString = (dateValue: DateValue | undefined): string => {
    if (!dateValue) {
        return '';
    }
    const year = dateValue.year.toString().padStart(4, '0');
    const month = dateValue.month.toString().padStart(2, '0');
    const day = dateValue.day.toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Initialize dateFromValue from dateFrom string
const initialDate = stringToDateValue(dateFrom.value);
dateFromValue.value = initialDate ? (initialDate as DateValue) : undefined;

// Watch dateFromValue and update dateFrom string
watch(dateFromValue, (newValue) => {
    dateFrom.value = dateValueToString(newValue as DateValue | undefined);
});

// Watch dateFrom string and update dateFromValue (for external changes)
watch(dateFrom, (newValue) => {
    const newDateValue = stringToDateValue(newValue);
    const currentValueStr = dateFromValue.value?.toString();
    const newValueStr = newDateValue?.toString();
    if (newValueStr !== currentValueStr) {
        dateFromValue.value = newDateValue ? (newDateValue as DateValue) : undefined;
    }
});

// Initialize dateToValue from dateTo string
const initialDateTo = stringToDateValue(dateTo.value);
dateToValue.value = initialDateTo ? (initialDateTo as DateValue) : undefined;

// Watch dateToValue and update dateTo string
watch(dateToValue, (newValue) => {
    dateTo.value = dateValueToString(newValue as DateValue | undefined);
});

// Watch dateTo string and update dateToValue (for external changes)
watch(dateTo, (newValue) => {
    const newDateValue = stringToDateValue(newValue);
    const currentValueStr = dateToValue.value?.toString();
    const newValueStr = newDateValue?.toString();
    if (newValueStr !== currentValueStr) {
        dateToValue.value = newDateValue ? (newDateValue as DateValue) : undefined;
    }
});

const updateFilters = useDebounceFn(() => {
    const url = new URL(TransactionController.index().url, window.location.origin);

    if (searchQuery.value) {
        url.searchParams.set('search', searchQuery.value);
    } else {
        url.searchParams.delete('search');
    }

    if (dateFrom.value) {
        url.searchParams.set('date_from', dateFrom.value);
    } else {
        url.searchParams.delete('date_from');
    }

    if (dateTo.value) {
        url.searchParams.set('date_to', dateTo.value);
    } else {
        url.searchParams.delete('date_to');
    }

    if (transactionTypeFilter.value && transactionTypeFilter.value !== 'all') {
        url.searchParams.set('transaction_type_id', transactionTypeFilter.value);
    } else {
        url.searchParams.delete('transaction_type_id');
    }

    if (statusFilter.value && statusFilter.value !== 'all') {
        url.searchParams.set('status', statusFilter.value);
    } else {
        url.searchParams.delete('status');
    }

    url.searchParams.delete('cursor');

    router.visit(url.pathname + url.search, {
        only: ['journalEntries'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch([searchQuery, dateFrom, dateTo, transactionTypeFilter, statusFilter], () => {
    if (!isInitialMount.value) {
        updateFilters();
    }
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';
    dateFrom.value = urlParams.get('date_from') || '';
    dateTo.value = urlParams.get('date_to') || '';
    transactionTypeFilter.value = urlParams.get('transaction_type_id') || 'all';
    statusFilter.value = urlParams.get('status') || 'all';
    isInitialMount.value = false;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Transactions',
        href: TransactionController.index().url,
    },
];

const isVoidDialogOpen = ref(false);
const pendingVoidEntry = ref<Transaction | null>(null);
const isVoiding = ref(false);

const openVoidDialog = (entry: Transaction) => {
    pendingVoidEntry.value = entry;
    isVoidDialogOpen.value = true;
};

const confirmVoid = () => {
    if (!pendingVoidEntry.value) {
        return;
    }

    isVoiding.value = true;
    router.post(
        TransactionController.voidMethod.url(pendingVoidEntry.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isVoidDialogOpen.value = false;
                pendingVoidEntry.value = null;
                isVoiding.value = false;
            },
            onError: () => {
                isVoiding.value = false;
            },
        },
    );
};

const cancelVoid = () => {
    isVoidDialogOpen.value = false;
    pendingVoidEntry.value = null;
};

watch(isVoidDialogOpen, (open) => {
    if (!open) {
        pendingVoidEntry.value = null;
    }
});

const columns = computed(() => createColumns(openVoidDialog));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Transactions" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Transactions"
                        description="Manage financial transactions and journal entries"
                    />
                </div>
                <Button as-child class="w-full md:w-auto">
                    <Link :href="TransactionController.create().url">
                        Create Transaction
                    </Link>
                </Button>
            </div>
            <div class="space-y-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by reference or description..."
                                class="w-full"
                            />
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <Popover v-model:open="isDatePickerOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="cn(
                                            'w-full sm:w-auto justify-start text-left font-normal',
                                            !dateFromValue && 'text-muted-foreground',
                                        )"
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{
                                            dateFromValue
                                                ? dateFormatter.format(
                                                      dateFromValue.toDate(getLocalTimeZone()),
                                                  )
                                                : 'From date'
                                        }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-auto p-0"
                                    align="start"
                                >
                                    <Calendar
                                        :model-value="dateFromValue as any"
                                        layout="month-and-year"
                                        initial-focus
                                        @update:model-value="(value: any) => { dateFromValue = (value as DateValue | undefined); isDatePickerOpen = false; }"
                                    />
                                </PopoverContent>
                            </Popover>
                            <Popover v-model:open="isDateToPickerOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="cn(
                                            'w-full sm:w-auto justify-start text-left font-normal',
                                            !dateToValue && 'text-muted-foreground',
                                        )"
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{
                                            dateToValue
                                                ? dateFormatter.format(
                                                      dateToValue.toDate(getLocalTimeZone()),
                                                  )
                                                : 'To date'
                                        }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-auto p-0"
                                    align="start"
                                >
                                    <Calendar
                                        :model-value="dateToValue as any"
                                        layout="month-and-year"
                                        initial-focus
                                        @update:model-value="(value: any) => { dateToValue = (value as DateValue | undefined); isDateToPickerOpen = false; }"
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="w-full sm:w-auto">
                            <Select v-model="transactionTypeFilter">
                                <SelectTrigger class="w-full sm:w-[200px]">
                                    <SelectValue placeholder="Filter by type" />
                                </SelectTrigger>
                                <SelectContent class="min-w-[200px]">
                                    <SelectItem value="all" class="whitespace-nowrap">All Types</SelectItem>
                                    <SelectItem
                                        v-for="type in props.transactionTypes"
                                        :key="type.id"
                                        :value="type.id"
                                        class="whitespace-nowrap"
                                    >
                                        {{ type.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-full sm:w-auto">
                            <Select v-model="statusFilter">
                                <SelectTrigger class="w-full sm:w-[180px]">
                                    <SelectValue placeholder="Filter by status" />
                                </SelectTrigger>
                                <SelectContent class="min-w-[180px]">
                                    <SelectItem value="all" class="whitespace-nowrap">All Status</SelectItem>
                                    <SelectItem value="DRAFT" class="whitespace-nowrap">Draft</SelectItem>
                                    <SelectItem value="POSTED" class="whitespace-nowrap">Posted</SelectItem>
                                    <SelectItem value="VOID" class="whitespace-nowrap">Void</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <DataTable
                    :data="props.journalEntries.data"
                    :columns="columns"
                />

                <DataTablePagination
                    :next-cursor="props.journalEntries.next_cursor"
                    :prev-cursor="props.journalEntries.prev_cursor"
                    :path="props.journalEntries.path"
                    :per-page="props.journalEntries.per_page"
                    :data-count="props.journalEntries.data.length"
                    :per-page-options="props.perPageOptions"
                />
            </div>
        </div>

        <Dialog v-model:open="isVoidDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Void transaction</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to void
                        <span class="font-medium text-foreground">
                            {{ pendingVoidEntry?.referenceNumber ?? 'this transaction' }}
                        </span>
                        ? This action cannot be undone. The transaction will be marked as voided and cannot be modified.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:space-x-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelVoid"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="isVoiding"
                        @click="confirmVoid"
                    >
                        {{ isVoiding ? 'Voiding...' : 'Void Transaction' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>


