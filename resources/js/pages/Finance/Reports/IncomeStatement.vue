<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
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
import { CalendarIcon, Printer, TrendingDown, TrendingUp } from 'lucide-vue-next';
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
    account_type: string;
    amount: string;
}

interface Category {
    id: string;
    name: string;
    type: string;
    sequence: number;
    accounts: Account[];
    total: string;
}

interface Props {
    categories: Category[];
    total_revenue: string;
    total_expense: string;
    net_surplus: string;
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
    }).format(Math.abs(num));
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

const isPositive = (value: string): boolean => {
    return parseFloat(value) > 0;
};

const handleFilter = () => {
    if (!dateFrom.value || !dateTo.value) {
        return;
    }

    router.visit(
        ReportController.incomeStatement.url({
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

// Separate categories by type
const revenueCategories = computed(() => {
    return props.categories.filter((category) => category.type === 'REVENUE');
});

const expenseCategories = computed(() => {
    return props.categories.filter((category) => category.type === 'EXPENSE');
});

const hasData = computed(() => props.categories.length > 0);

const netSurplusValue = computed(() => parseFloat(props.net_surplus));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Income Statement',
        href: ReportController.incomeStatementIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Income Statement" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Income Statement"
                        description="Profit and Loss Statement Report"
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
                <!-- Filters -->
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

                <!-- Empty State -->
                <div v-if="!hasData" class="flex min-h-[400px] items-center justify-center rounded-lg border border-sidebar-border/60 bg-card p-12 text-center dark:border-sidebar-border">
                    <div class="space-y-2">
                        <p class="text-lg font-medium text-muted-foreground">
                            No data found
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Select a date range and click Filter to generate the report
                        </p>
                    </div>
                </div>

                <!-- Report Content -->
                <div v-else class="overflow-hidden rounded-lg border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                    <!-- Report Header -->
                    <div class="p-4 print:border-b print:border-sidebar-border/60 print:pb-2">
                        <div class="space-y-1">
                            <h3 class="text-lg font-semibold">
                                Income Statement
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
                                        Account
                                    </th>
                                    <th class="w-48 px-4 py-3 text-right font-semibold text-foreground">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- REVENUE SECTION -->
                                <tr class="border-b-2 border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20">
                                    <td
                                        colspan="2"
                                        class="px-4 py-3 text-lg font-bold text-emerald-700 dark:text-emerald-400"
                                    >
                                        <div class="flex items-center gap-2">
                                            <TrendingUp class="h-5 w-5" />
                                            REVENUE
                                        </div>
                                    </td>
                                </tr>

                                <template
                                    v-for="category in revenueCategories"
                                    :key="category.id"
                                >
                                    <!-- Category Header -->
                                    <tr class="border-b border-sidebar-border/60 bg-muted/30">
                                        <td
                                            colspan="2"
                                            class="px-4 py-2 font-semibold text-foreground"
                                        >
                                            {{ category.name }}
                                        </td>
                                    </tr>
                                    <!-- Category Accounts -->
                                    <tr
                                        v-for="account in category.accounts"
                                        :key="account.id"
                                        class="border-b border-sidebar-border/60 transition-colors hover:bg-muted/20"
                                    >
                                        <td class="py-3 pl-8 pr-4 text-foreground">
                                            <span class="font-mono text-xs text-muted-foreground mr-2">{{ account.code }}</span>
                                            {{ account.name }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-foreground">
                                            {{ formatCurrency(account.amount) }}
                                        </td>
                                    </tr>
                                    <!-- Category Subtotal -->
                                    <tr class="border-b border-sidebar-border/60 bg-muted/10">
                                        <td class="px-4 py-2 text-right font-semibold text-foreground">
                                            Subtotal {{ category.name }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-foreground">
                                            {{ formatCurrency(category.total) }}
                                        </td>
                                    </tr>
                                </template>

                                <!-- Total Revenue Row -->
                                <tr class="border-b-2 border-emerald-500/50 bg-emerald-100/80 dark:bg-emerald-900/30">
                                    <td class="px-4 py-3 text-right text-lg font-bold text-emerald-700 dark:text-emerald-400">
                                        TOTAL REVENUE
                                    </td>
                                    <td class="px-4 py-3 text-right text-lg font-bold text-emerald-700 dark:text-emerald-400">
                                        {{ formatCurrency(total_revenue) }}
                                    </td>
                                </tr>

                                <!-- Spacer -->
                                <tr>
                                    <td colspan="2" class="h-4"></td>
                                </tr>

                                <!-- EXPENSES SECTION -->
                                <tr class="border-b-2 border-rose-500/30 bg-rose-50/50 dark:bg-rose-950/20">
                                    <td
                                        colspan="2"
                                        class="px-4 py-3 text-lg font-bold text-rose-700 dark:text-rose-400"
                                    >
                                        <div class="flex items-center gap-2">
                                            <TrendingDown class="h-5 w-5" />
                                            EXPENSES
                                        </div>
                                    </td>
                                </tr>

                                <template
                                    v-for="category in expenseCategories"
                                    :key="category.id"
                                >
                                    <!-- Category Header -->
                                    <tr class="border-b border-sidebar-border/60 bg-muted/30">
                                        <td
                                            colspan="2"
                                            class="px-4 py-2 font-semibold text-foreground"
                                        >
                                            {{ category.name }}
                                        </td>
                                    </tr>
                                    <!-- Category Accounts -->
                                    <tr
                                        v-for="account in category.accounts"
                                        :key="account.id"
                                        class="border-b border-sidebar-border/60 transition-colors hover:bg-muted/20"
                                    >
                                        <td class="py-3 pl-8 pr-4 text-foreground">
                                            <span class="font-mono text-xs text-muted-foreground mr-2">{{ account.code }}</span>
                                            {{ account.name }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-foreground">
                                            {{ formatCurrency(account.amount) }}
                                        </td>
                                    </tr>
                                    <!-- Category Subtotal -->
                                    <tr class="border-b border-sidebar-border/60 bg-muted/10">
                                        <td class="px-4 py-2 text-right font-semibold text-foreground">
                                            Subtotal {{ category.name }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-foreground">
                                            {{ formatCurrency(category.total) }}
                                        </td>
                                    </tr>
                                </template>

                                <!-- Total Expense Row -->
                                <tr class="border-b-2 border-rose-500/50 bg-rose-100/80 dark:bg-rose-900/30">
                                    <td class="px-4 py-3 text-right text-lg font-bold text-rose-700 dark:text-rose-400">
                                        TOTAL EXPENSES
                                    </td>
                                    <td class="px-4 py-3 text-right text-lg font-bold text-rose-700 dark:text-rose-400">
                                        {{ formatCurrency(total_expense) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <!-- Net Surplus/Deficit Row -->
                                <tr
                                    class="border-t-4"
                                    :class="netSurplusValue >= 0
                                        ? 'border-emerald-600 bg-emerald-200/80 dark:bg-emerald-800/40'
                                        : 'border-rose-600 bg-rose-200/80 dark:bg-rose-800/40'"
                                >
                                    <td class="px-4 py-4 text-right">
                                        <span
                                            class="text-xl font-bold"
                                            :class="netSurplusValue >= 0
                                                ? 'text-emerald-800 dark:text-emerald-300'
                                                : 'text-rose-800 dark:text-rose-300'"
                                        >
                                            {{ netSurplusValue >= 0 ? 'NET SURPLUS' : 'NET DEFICIT' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span
                                            class="text-2xl font-bold"
                                            :class="netSurplusValue >= 0
                                                ? 'text-emerald-800 dark:text-emerald-300'
                                                : 'text-rose-800 dark:text-rose-300'"
                                        >
                                            {{ isNegative(net_surplus) ? '(' : '' }}{{ formatCurrency(net_surplus) }}{{ isNegative(net_surplus) ? ')' : '' }}
                                        </span>
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



