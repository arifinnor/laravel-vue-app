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
import { AlertTriangle, CalendarIcon, CheckCircle2, Landmark, Printer, Scale, Wallet } from 'lucide-vue-next';
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
    balance: string;
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
    asset_categories: Category[];
    liability_categories: Category[];
    equity_categories: Category[];
    total_assets: string;
    total_liabilities: string;
    total_equity: string;
    current_year_earnings: string;
    total_liabilities_equity: string;
    is_balanced: boolean;
    filters: {
        as_of_date: string | null;
    };
}

const props = defineProps<Props>();

const asOfDate = ref<string>(props.filters.as_of_date || new Date().toISOString().split('T')[0]);
const isDatePickerOpen = ref(false);
const dateValue = ref<DateValue | undefined>(undefined);

const dateFormatter = new DateFormatter('en-US', { dateStyle: 'long' });

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

const initialDateValue = stringToDateValue(asOfDate.value);
dateValue.value = initialDateValue ? (initialDateValue as DateValue) : undefined;

watch(dateValue, (newValue) => {
    asOfDate.value = dateValueToString(newValue as DateValue | undefined);
});

watch(asOfDate, (newValue) => {
    const newDateValue = stringToDateValue(newValue);
    const currentValueStr = dateValue.value?.toString();
    const newValueStr = newDateValue?.toString();
    if (newValueStr !== currentValueStr) {
        dateValue.value = newDateValue ? (newDateValue as DateValue) : undefined;
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
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const isNegative = (value: string): boolean => {
    return parseFloat(value) < 0;
};

const handleFilter = () => {
    if (!asOfDate.value) {
        return;
    }

    router.visit(
        ReportController.balanceSheet.url({
            query: {
                as_of_date: asOfDate.value,
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

const hasData = computed(() =>
    props.asset_categories.length > 0 ||
    props.liability_categories.length > 0 ||
    props.equity_categories.length > 0
);

const currentYearEarningsValue = computed(() => parseFloat(props.current_year_earnings));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Balance Sheet',
        href: ReportController.balanceSheetIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Balance Sheet" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Balance Sheet"
                        description="Statement of Financial Position"
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
                                    Per Tanggal (As of Date)
                                </label>
                                <Popover v-model:open="isDatePickerOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            :class="cn(
                                                'w-full sm:w-[280px] justify-start text-left font-normal',
                                                !dateValue && 'text-muted-foreground',
                                            )"
                                        >
                                            <CalendarIcon class="mr-2 h-4 w-4" />
                                            {{
                                                dateValue
                                                    ? dateFormatter.format(
                                                          dateValue.toDate(getLocalTimeZone()),
                                                      )
                                                    : 'Select date'
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        class="w-auto p-0"
                                        align="start"
                                    >
                                        <Calendar
                                            :model-value="dateValue as any"
                                            layout="month-and-year"
                                            initial-focus
                                            @update:model-value="(value: any) => { dateValue = (value as DateValue | undefined); isDatePickerOpen = false; }"
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <Button
                                :disabled="!asOfDate"
                                @click="handleFilter"
                            >
                                Generate Report
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!hasData" class="flex min-h-[400px] items-center justify-center rounded-lg border border-sidebar-border/60 bg-card p-12 text-center dark:border-sidebar-border">
                    <div class="space-y-2">
                        <Scale class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <p class="text-lg font-medium text-muted-foreground">
                            No data found
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Select a date and click Generate Report to view the balance sheet
                        </p>
                    </div>
                </div>

                <!-- Report Content -->
                <div v-else class="space-y-6">
                    <!-- Report Header -->
                    <div class="rounded-lg border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border print:border print:shadow-none">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-1">
                                <h3 class="text-lg font-semibold">
                                    Balance Sheet
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    As of {{ formatDate(asOfDate) }}
                                </p>
                            </div>
                            <!-- Balance Status Indicator -->
                            <div
                                class="flex items-center gap-2 rounded-lg px-4 py-2"
                                :class="is_balanced
                                    ? 'bg-emerald-100/80 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    : 'bg-amber-100/80 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                            >
                                <CheckCircle2 v-if="is_balanced" class="h-5 w-5" />
                                <AlertTriangle v-else class="h-5 w-5" />
                                <span class="font-medium">
                                    {{ is_balanced ? 'Balanced' : 'Not Balanced' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid gap-6 lg:grid-cols-2">
                        <!-- LEFT SIDE: ASSETS -->
                        <div class="overflow-hidden rounded-lg border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                            <div class="border-b-2 border-sky-500/50 bg-sky-50/80 px-4 py-3 dark:bg-sky-950/30">
                                <div class="flex items-center gap-2 text-lg font-bold text-sky-700 dark:text-sky-400">
                                    <Wallet class="h-5 w-5" />
                                    ASSETS / ASET
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-sidebar-border/60 bg-muted/20">
                                            <th class="px-4 py-2 text-left font-semibold text-foreground">
                                                Account
                                            </th>
                                            <th class="w-40 px-4 py-2 text-right font-semibold text-foreground">
                                                Balance
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template
                                            v-for="category in asset_categories"
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
                                                <td class="py-2 pl-8 pr-4 text-foreground">
                                                    <span class="mr-2 font-mono text-xs text-muted-foreground">{{ account.code }}</span>
                                                    {{ account.name }}
                                                </td>
                                                <td class="px-4 py-2 text-right text-foreground">
                                                    {{ formatCurrency(account.balance) }}
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
                                    </tbody>
                                    <tfoot>
                                        <!-- Total Assets Row -->
                                        <tr class="border-t-4 border-sky-600 bg-sky-100/80 dark:bg-sky-900/40">
                                            <td class="px-4 py-3 text-right text-lg font-bold text-sky-800 dark:text-sky-300">
                                                TOTAL ASSETS
                                            </td>
                                            <td class="px-4 py-3 text-right text-lg font-bold text-sky-800 dark:text-sky-300" style="border-bottom: 4px double currentColor;">
                                                {{ formatCurrency(total_assets) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- RIGHT SIDE: LIABILITIES & EQUITY -->
                        <div class="overflow-hidden rounded-lg border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                            <div class="border-b-2 border-violet-500/50 bg-violet-50/80 px-4 py-3 dark:bg-violet-950/30">
                                <div class="flex items-center gap-2 text-lg font-bold text-violet-700 dark:text-violet-400">
                                    <Landmark class="h-5 w-5" />
                                    LIABILITIES & EQUITY / KEWAJIBAN & EKUITAS
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-sidebar-border/60 bg-muted/20">
                                            <th class="px-4 py-2 text-left font-semibold text-foreground">
                                                Account
                                            </th>
                                            <th class="w-40 px-4 py-2 text-right font-semibold text-foreground">
                                                Balance
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- LIABILITIES SECTION -->
                                        <tr
                                            v-if="liability_categories.length > 0"
                                            class="border-b border-rose-500/30 bg-rose-50/50 dark:bg-rose-950/20"
                                        >
                                            <td
                                                colspan="2"
                                                class="px-4 py-2 font-bold text-rose-700 dark:text-rose-400"
                                            >
                                                LIABILITIES / KEWAJIBAN
                                            </td>
                                        </tr>

                                        <template
                                            v-for="category in liability_categories"
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
                                                <td class="py-2 pl-8 pr-4 text-foreground">
                                                    <span class="mr-2 font-mono text-xs text-muted-foreground">{{ account.code }}</span>
                                                    {{ account.name }}
                                                </td>
                                                <td class="px-4 py-2 text-right text-foreground">
                                                    {{ formatCurrency(account.balance) }}
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

                                        <!-- Total Liabilities -->
                                        <tr
                                            v-if="liability_categories.length > 0"
                                            class="border-b-2 border-rose-500/50 bg-rose-100/60 dark:bg-rose-900/30"
                                        >
                                            <td class="px-4 py-2 text-right font-bold text-rose-700 dark:text-rose-400">
                                                Total Liabilities
                                            </td>
                                            <td class="px-4 py-2 text-right font-bold text-rose-700 dark:text-rose-400">
                                                {{ formatCurrency(total_liabilities) }}
                                            </td>
                                        </tr>

                                        <!-- Spacer -->
                                        <tr>
                                            <td colspan="2" class="h-4"></td>
                                        </tr>

                                        <!-- EQUITY SECTION -->
                                        <tr class="border-b border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20">
                                            <td
                                                colspan="2"
                                                class="px-4 py-2 font-bold text-emerald-700 dark:text-emerald-400"
                                            >
                                                EQUITY / EKUITAS
                                            </td>
                                        </tr>

                                        <template
                                            v-for="category in equity_categories"
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
                                                <td class="py-2 pl-8 pr-4 text-foreground">
                                                    <span class="mr-2 font-mono text-xs text-muted-foreground">{{ account.code }}</span>
                                                    {{ account.name }}
                                                </td>
                                                <td class="px-4 py-2 text-right text-foreground">
                                                    {{ formatCurrency(account.balance) }}
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

                                        <!-- Current Year Earnings (Virtual Row) -->
                                        <tr class="border-b-2 border-amber-500/50 bg-amber-50/80 dark:bg-amber-950/30">
                                            <td class="px-4 py-3 pl-8 font-semibold text-amber-800 dark:text-amber-300">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center rounded-full bg-amber-200/80 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-800/50 dark:text-amber-200">
                                                        Auto
                                                    </span>
                                                    Current Year Earnings / Laba Tahun Berjalan
                                                </div>
                                            </td>
                                            <td
                                                class="px-4 py-3 text-right font-bold"
                                                :class="currentYearEarningsValue >= 0
                                                    ? 'text-emerald-700 dark:text-emerald-400'
                                                    : 'text-rose-700 dark:text-rose-400'"
                                            >
                                                {{ isNegative(current_year_earnings) ? '(' : '' }}{{ formatCurrency(current_year_earnings) }}{{ isNegative(current_year_earnings) ? ')' : '' }}
                                            </td>
                                        </tr>

                                        <!-- Total Equity -->
                                        <tr class="border-b border-emerald-500/50 bg-emerald-100/60 dark:bg-emerald-900/30">
                                            <td class="px-4 py-2 text-right font-bold text-emerald-700 dark:text-emerald-400">
                                                Total Equity (incl. Current Year Earnings)
                                            </td>
                                            <td class="px-4 py-2 text-right font-bold text-emerald-700 dark:text-emerald-400">
                                                {{ formatCurrency((parseFloat(total_equity) + parseFloat(current_year_earnings)).toFixed(2)) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <!-- Total Liabilities & Equity Row -->
                                        <tr class="border-t-4 border-violet-600 bg-violet-100/80 dark:bg-violet-900/40">
                                            <td class="px-4 py-3 text-right text-lg font-bold text-violet-800 dark:text-violet-300">
                                                TOTAL LIABILITIES & EQUITY
                                            </td>
                                            <td class="px-4 py-3 text-right text-lg font-bold text-violet-800 dark:text-violet-300" style="border-bottom: 4px double currentColor;">
                                                {{ formatCurrency(total_liabilities_equity) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Footer -->
                    <div class="rounded-lg border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border print:mt-4">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-lg bg-sky-50/80 p-4 dark:bg-sky-950/30">
                                <p class="text-sm font-medium text-sky-600 dark:text-sky-400">Total Assets</p>
                                <p class="mt-1 text-xl font-bold text-sky-700 dark:text-sky-300">
                                    {{ formatCurrency(total_assets) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-rose-50/80 p-4 dark:bg-rose-950/30">
                                <p class="text-sm font-medium text-rose-600 dark:text-rose-400">Total Liabilities</p>
                                <p class="mt-1 text-xl font-bold text-rose-700 dark:text-rose-300">
                                    {{ formatCurrency(total_liabilities) }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-emerald-50/80 p-4 dark:bg-emerald-950/30">
                                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Total Equity</p>
                                <p class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-300">
                                    {{ formatCurrency((parseFloat(total_equity) + parseFloat(current_year_earnings)).toFixed(2)) }}
                                </p>
                            </div>
                            <div
                                class="rounded-lg p-4"
                                :class="is_balanced
                                    ? 'bg-emerald-50/80 dark:bg-emerald-950/30'
                                    : 'bg-amber-50/80 dark:bg-amber-950/30'"
                            >
                                <p
                                    class="text-sm font-medium"
                                    :class="is_balanced
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-amber-600 dark:text-amber-400'"
                                >
                                    Balance Check
                                </p>
                                <p
                                    class="mt-1 flex items-center gap-2 text-xl font-bold"
                                    :class="is_balanced
                                        ? 'text-emerald-700 dark:text-emerald-300'
                                        : 'text-amber-700 dark:text-amber-300'"
                                >
                                    <CheckCircle2 v-if="is_balanced" class="h-5 w-5" />
                                    <AlertTriangle v-else class="h-5 w-5" />
                                    {{ is_balanced ? 'Balanced ✓' : 'Not Balanced' }}
                                </p>
                            </div>
                        </div>
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
        size: landscape;
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

