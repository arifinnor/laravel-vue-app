<script setup lang="ts">
import ReportController from '@/actions/App/Http/Controllers/Finance/ReportController';
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

interface Account {
    id: string;
    code: string;
    name: string;
    normal_balance: string;
    category_id: string | null;
    category_name: string;
    category_sequence: number;
    opening_balance: string;
    debit_mutation: string;
    credit_mutation: string;
    closing_balance: string;
}

interface Props {
    accounts: Account[];
    total_debit_mutation: string;
    total_credit_mutation: string;
    filters: {
        start_date: string | null;
        end_date: string | null;
    };
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

const isNegative = (value: string): boolean => {
    return parseFloat(value) < 0;
};

const formatAmount = (value: string): string => {
    const num = parseFloat(value);
    if (num === 0) {
        return 'Rp 0';
    }
    return formatCurrency(value);
};

const handleFilter = () => {
    if (!dateFrom.value || !dateTo.value) {
        return;
    }

    router.visit(
        ReportController.trialBalance.url({
            query: {
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

// Group accounts by category
const groupedAccounts = computed(() => {
    const groups = new Map<string, Account[]>();

    props.accounts.forEach((account) => {
        const categoryName = account.category_name || 'Uncategorized';
        if (!groups.has(categoryName)) {
            groups.set(categoryName, []);
        }
        groups.get(categoryName)!.push(account);
    });

    // Sort categories by sequence, then by name
    const sortedGroups = Array.from(groups.entries()).sort((a, b) => {
        const accountA = a[1][0];
        const accountB = b[1][0];
        if (accountA.category_sequence !== accountB.category_sequence) {
            return accountA.category_sequence - accountB.category_sequence;
        }
        return a[0].localeCompare(b[0]);
    });

    return sortedGroups;
});

const hasData = computed(() => props.accounts.length > 0);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
        href: ReportController.trialBalanceIndex().url,
    },
    {
        title: 'Trial Balance',
        href: ReportController.trialBalanceIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Trial Balance" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Trial Balance"
                        description="Trial Balance Report"
                    />
                </div>
                <div class="flex gap-2 no-print">
                    <Button
                        v-if="hasData"
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
                                :disabled="!dateFrom || !dateTo"
                                @click="handleFilter"
                            >
                                Filter
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="!hasData" class="flex min-h-[400px] items-center justify-center rounded-lg border border-sidebar-border/60 bg-card p-12 text-center dark:border-sidebar-border">
                    <div class="space-y-2">
                        <p class="text-lg font-medium text-muted-foreground">
                            No accounts found
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Select a date range and click Filter to generate the report
                        </p>
                    </div>
                </div>

                <div v-else class="overflow-hidden rounded-lg border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                    <div class="p-4 print:border-b print:border-sidebar-border/60 print:pb-2">
                        <div class="space-y-1">
                            <h3 class="text-lg font-semibold">
                                Trial Balance
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
                                        Account Code
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-foreground">
                                        Account Name
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-foreground">
                                        Opening Balance
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-foreground">
                                        Debit
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-foreground">
                                        Credit
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-foreground">
                                        Closing Balance
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template
                                    v-for="[categoryName, categoryAccounts] in groupedAccounts"
                                    :key="categoryName"
                                >
                                    <tr class="border-b-2 border-sidebar-border/60 bg-muted/30">
                                        <td
                                            colspan="6"
                                            class="px-4 py-2 font-bold text-foreground"
                                        >
                                            {{ categoryName }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="account in categoryAccounts"
                                        :key="account.id"
                                        class="border-b border-sidebar-border/60 transition-colors hover:bg-muted/30"
                                    >
                                        <td class="px-4 py-3 font-mono text-sm text-foreground">
                                            {{ account.code }}
                                        </td>
                                        <td class="px-4 py-3 text-foreground">
                                            {{ account.name }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right"
                                            :class="isNegative(account.opening_balance) ? 'text-destructive' : 'text-foreground'"
                                        >
                                            {{ formatAmount(account.opening_balance) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-foreground">
                                            {{ formatAmount(account.debit_mutation) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-foreground">
                                            {{ formatAmount(account.credit_mutation) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-bold"
                                            :class="isNegative(account.closing_balance) ? 'text-destructive' : 'text-foreground'"
                                        >
                                            {{ formatAmount(account.closing_balance) }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-sidebar-border/60 bg-muted/20 font-semibold">
                                    <td
                                        colspan="2"
                                        class="px-4 py-3 font-bold text-foreground"
                                    >
                                        Grand Total
                                    </td>
                                    <td class="px-4 py-3 text-right text-muted-foreground">
                                        —
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-foreground">
                                        {{ formatAmount(total_debit_mutation) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-foreground">
                                        {{ formatAmount(total_credit_mutation) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-muted-foreground">
                                        —
                                    </td>
                                </tr>
                            </tfoot>
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


