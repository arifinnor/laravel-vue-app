<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import ReportController from '@/actions/App/Http/Controllers/Finance/ReportController';
import AccountCombobox from '@/components/Finance/AccountCombobox.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { CalendarIcon, Printer } from 'lucide-vue-next';
import {
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    type CalendarDate,
    type DateValue,
} from '@internationalized/date';
import { computed, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

interface Transaction {
    id: string;
    journal_entry_id: string;
    transaction_date: string;
    reference_number: string;
    description: string | null;
    debit: string | null;
    credit: string | null;
    running_balance: string;
}

interface Account {
    id: string;
    code: string;
    name: string;
    normal_balance?: string;
}

interface Props {
    account: Account | null;
    opening_balance: string | null;
    transactions: Transaction[];
    closing_balance: string | null;
    filters: {
        account_id: string | null;
        start_date: string | null;
        end_date: string | null;
    };
    accounts: Array<{
        id: string;
        code: string;
        name: string;
    }>;
}

const props = defineProps<Props>();

const getCurrentMonthRange = () => {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    return {
        start: start.toISOString().split('T')[0],
        end: end.toISOString().split('T')[0],
    };
};

const selectedAccountId = ref<string | null>(props.filters.account_id);
const dateFrom = ref<string>(props.filters.start_date || getCurrentMonthRange().start);
const dateTo = ref<string>(props.filters.end_date || getCurrentMonthRange().end);

const isDatePickerOpen = ref(false);
const dateFromValue = ref<DateValue | undefined>(undefined);

const isDateToPickerOpen = ref(false);
const dateToValue = ref<DateValue | undefined>(undefined);

const dateFormatter = new DateFormatter('en-US', { dateStyle: 'short' });

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

const dateValueToString = (dateValue: DateValue | undefined): string => {
    if (!dateValue) {
        return '';
    }
    const year = dateValue.year.toString().padStart(4, '0');
    const month = dateValue.month.toString().padStart(2, '0');
    const day = dateValue.day.toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const initialDateFrom = stringToDateValue(dateFrom.value);
dateFromValue.value = initialDateFrom ? (initialDateFrom as DateValue) : undefined;

watch(dateFromValue, (newValue) => {
    dateFrom.value = dateValueToString(newValue as DateValue | undefined);
});

watch(dateFrom, (newValue) => {
    const newDateValue = stringToDateValue(newValue);
    const currentValueStr = dateFromValue.value?.toString();
    const newValueStr = newDateValue?.toString();
    if (newValueStr !== currentValueStr) {
        dateFromValue.value = newDateValue ? (newDateValue as DateValue) : undefined;
    }
});

const initialDateTo = stringToDateValue(dateTo.value);
dateToValue.value = initialDateTo ? (initialDateTo as DateValue) : undefined;

watch(dateToValue, (newValue) => {
    dateTo.value = dateValueToString(newValue as DateValue | undefined);
});

watch(dateTo, (newValue) => {
    const newDateValue = stringToDateValue(newValue);
    const currentValueStr = dateToValue.value?.toString();
    const newValueStr = newDateValue?.toString();
    if (newValueStr !== currentValueStr) {
        dateToValue.value = newDateValue ? (newDateValue as DateValue) : undefined;
    }
});

const formatCurrency = (value: string | null): string => {
    if (!value || parseFloat(value) === 0) {
        return 'Rp 0';
    }
    const num = parseFloat(value);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (value: string): string => {
    return new Date(value).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const accountsForCombobox = computed(() => {
    return props.accounts.map((account) => ({
        ...account,
        account_type: '',
        display: `${account.code} - ${account.name}`,
    }));
});

const handleFilter = () => {
    if (!selectedAccountId.value || !dateFrom.value || !dateTo.value) {
        return;
    }

    router.visit(
        ReportController.generalLedger.url({
            query: {
                account_id: selectedAccountId.value,
                start_date: dateFrom.value,
                end_date: dateTo.value,
            },
        }),
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
};

const handlePrint = () => {
    window.print();
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'General Ledger',
        href: ReportController.generalLedgerIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="General Ledger" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="General Ledger"
                        description="General Ledger Report"
                    />
                </div>
                <div class="flex gap-2 no-print">
                    <Button
                        v-if="account"
                        variant="outline"
                        @click="handlePrint"
                    >
                        <Printer class="mr-2 h-4 w-4" />
                        Print
                    </Button>
                </div>
            </div>

            <div class="space-y-6">
                <div class="no-print rounded-lg border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label class="mb-2 block text-sm font-medium">
                                Account
                            </label>
                            <AccountCombobox
                                v-model="selectedAccountId"
                                :accounts="accountsForCombobox"
                                placeholder="Select account..."
                                class="w-full"
                            />
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div>
                                <label class="mb-2 block text-sm font-medium">
                                    Start Date
                                </label>
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
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">
                                    End Date
                                </label>
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
                            <Button
                                :disabled="!selectedAccountId || !dateFrom || !dateTo"
                                @click="handleFilter"
                            >
                                Filter
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="!account" class="flex min-h-[400px] items-center justify-center rounded-lg border border-sidebar-border/60 bg-card p-12 text-center dark:border-sidebar-border">
                    <div class="space-y-2">
                        <p class="text-lg font-medium text-muted-foreground">
                            Please select an account to view details
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Choose an account from the dropdown above and click Filter to generate the report
                        </p>
                    </div>
                </div>

                <div v-else class="overflow-hidden rounded-lg border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                    <div class="p-4 print:border-b print:border-sidebar-border/60 print:pb-2">
                        <div class="space-y-1">
                            <h3 class="text-lg font-semibold">
                                {{ account.code }} - {{ account.name }}
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Period: {{ formatDate(dateFrom) }} - {{ formatDate(dateTo) }}
                            </p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-sidebar-border/60 bg-muted/20">
                                    <th class="px-4 py-3 text-left font-semibold text-foreground">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-foreground">
                                        Reference #
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-foreground">
                                        Description
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-foreground">
                                        Debit
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-foreground">
                                        Credit
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-foreground">
                                        Balance
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-sidebar-border/60 bg-muted/10">
                                    <td
                                        colspan="3"
                                        class="px-4 py-3 font-medium text-foreground"
                                    >
                                        Saldo Awal / Opening Balance
                                    </td>
                                    <td class="px-4 py-3 text-right text-muted-foreground">
                                        —
                                    </td>
                                    <td class="px-4 py-3 text-right text-muted-foreground">
                                        —
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-foreground">
                                        {{ formatCurrency(opening_balance) }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="transaction in transactions"
                                    :key="transaction.id"
                                    class="border-b border-sidebar-border/60 transition-colors hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ formatDate(transaction.transaction_date) }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm text-foreground">
                                        {{ transaction.reference_number }}
                                    </td>
                                    <td class="px-4 py-3 text-foreground">
                                        {{ transaction.description || '—' }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right"
                                        :class="transaction.debit ? 'text-foreground' : 'text-muted-foreground'"
                                    >
                                        {{ transaction.debit ? formatCurrency(transaction.debit) : '—' }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right"
                                        :class="transaction.credit ? 'text-foreground' : 'text-muted-foreground'"
                                    >
                                        {{ transaction.credit ? formatCurrency(transaction.credit) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-foreground">
                                        {{ formatCurrency(transaction.running_balance) }}
                                    </td>
                                </tr>
                                <tr class="border-t-2 border-sidebar-border/60 bg-muted/10 font-semibold">
                                    <td
                                        colspan="3"
                                        class="px-4 py-3 font-medium text-foreground"
                                    >
                                        Saldo Akhir / Closing Balance
                                    </td>
                                    <td class="px-4 py-3 text-right text-muted-foreground">
                                        —
                                    </td>
                                    <td class="px-4 py-3 text-right text-muted-foreground">
                                        —
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-foreground">
                                        {{ formatCurrency(closing_balance) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        margin: 0;
        padding: 0;
    }

    @page {
        margin: 1cm;
    }

    table {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }
}
</style>

