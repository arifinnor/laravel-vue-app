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
import { ArrowDownLeft, ArrowUpRight, Banknote, Building, CalendarIcon, HandCoins, Printer, TrendingDown, TrendingUp, Wallet } from 'lucide-vue-next';
import {
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    type CalendarDate,
    type DateValue,
} from '@internationalized/date';
import { computed, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

interface Activity {
    transaction_type_id: string;
    transaction_type_name: string;
    transaction_type_code: string;
    category: string;
    amount: string;
    transaction_count: number;
}

interface Props {
    operating_activities: Activity[];
    operating_total: string;
    investing_activities: Activity[];
    investing_total: string;
    financing_activities: Activity[];
    financing_total: string;
    net_change_in_cash: string;
    beginning_cash_balance: string;
    ending_cash_balance: string;
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
        ReportController.cashFlow.url({
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

const hasData = computed(() =>
    props.operating_activities.length > 0 ||
    props.investing_activities.length > 0 ||
    props.financing_activities.length > 0 ||
    parseFloat(props.beginning_cash_balance) !== 0
);

const netChangeValue = computed(() => parseFloat(props.net_change_in_cash));
const endingCashValue = computed(() => parseFloat(props.ending_cash_balance));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Cash Flow',
        href: ReportController.cashFlowIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Laporan Arus Kas" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Laporan Arus Kas"
                        description="Statement of Cash Flows (Direct Method)"
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
                                    Tanggal Mulai (Start Date)
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
                                                    : 'Pilih tanggal'
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
                                    Tanggal Akhir (End Date)
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
                                                    : 'Pilih tanggal'
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
                                Generate Report
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!hasData" class="flex min-h-[400px] items-center justify-center rounded-lg border border-sidebar-border/60 bg-card p-12 text-center dark:border-sidebar-border">
                    <div class="space-y-2">
                        <Banknote class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <p class="text-lg font-medium text-muted-foreground">
                            Tidak ada data
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Pilih rentang tanggal dan klik Generate Report untuk melihat laporan arus kas
                        </p>
                    </div>
                </div>

                <!-- Report Content -->
                <div v-else class="overflow-hidden rounded-lg border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                    <!-- Report Header -->
                    <div class="border-b border-sidebar-border/60 bg-gradient-to-r from-cyan-50/80 to-teal-50/80 p-4 dark:from-cyan-950/30 dark:to-teal-950/30">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-500/20 text-cyan-600 dark:text-cyan-400">
                                <Banknote class="h-5 w-5" />
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-lg font-semibold">
                                    Laporan Arus Kas (Metode Langsung)
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    Periode: {{ formatDate(dateFrom) }} - {{ formatDate(dateTo) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-sidebar-border/60 bg-muted/20">
                                    <th class="px-4 py-3 text-left font-semibold text-foreground">
                                        Keterangan
                                    </th>
                                    <th class="w-48 px-4 py-3 text-right font-semibold text-foreground">
                                        Jumlah
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- SECTION 1: AKTIVITAS OPERASIONAL -->
                                <tr class="border-b-2 border-sky-500/30 bg-sky-50/50 dark:bg-sky-950/20">
                                    <td
                                        colspan="2"
                                        class="px-4 py-3 text-lg font-bold text-sky-700 dark:text-sky-400"
                                    >
                                        <div class="flex items-center gap-2">
                                            <HandCoins class="h-5 w-5" />
                                            AKTIVITAS OPERASIONAL
                                        </div>
                                        <div class="mt-1 text-xs font-normal text-sky-600/70 dark:text-sky-400/70">
                                            Operating Activities (SPP, Gaji, Listrik, dll.)
                                        </div>
                                    </td>
                                </tr>

                                <template v-if="operating_activities.length > 0">
                                    <tr
                                        v-for="activity in operating_activities"
                                        :key="activity.transaction_type_id"
                                        class="border-b border-sidebar-border/60 transition-colors hover:bg-muted/20"
                                    >
                                        <td class="py-3 pl-8 pr-4 text-foreground">
                                            <div class="flex items-center gap-2">
                                                <ArrowUpRight
                                                    v-if="isPositive(activity.amount)"
                                                    class="h-4 w-4 text-emerald-500"
                                                />
                                                <ArrowDownLeft
                                                    v-else
                                                    class="h-4 w-4 text-rose-500"
                                                />
                                                <span class="mr-2 font-mono text-xs text-muted-foreground">{{ activity.transaction_type_code }}</span>
                                                {{ activity.transaction_type_name }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right"
                                            :class="isNegative(activity.amount) ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                        >
                                            {{ isNegative(activity.amount) ? '(' : '' }}{{ formatCurrency(activity.amount) }}{{ isNegative(activity.amount) ? ')' : '' }}
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else class="border-b border-sidebar-border/60">
                                    <td colspan="2" class="px-4 py-3 text-center text-muted-foreground italic">
                                        Tidak ada transaksi operasional
                                    </td>
                                </tr>

                                <!-- Total Operating Activities -->
                                <tr class="border-b-2 border-sky-500/50 bg-sky-100/80 dark:bg-sky-900/30">
                                    <td class="px-4 py-3 text-right font-bold text-sky-700 dark:text-sky-400">
                                        Arus Kas Bersih dari Aktivitas Operasional
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-bold"
                                        :class="isNegative(operating_total) ? 'text-rose-700 dark:text-rose-400' : 'text-sky-700 dark:text-sky-400'"
                                    >
                                        {{ isNegative(operating_total) ? '(' : '' }}{{ formatCurrency(operating_total) }}{{ isNegative(operating_total) ? ')' : '' }}
                                    </td>
                                </tr>

                                <!-- Spacer -->
                                <tr>
                                    <td colspan="2" class="h-4"></td>
                                </tr>

                                <!-- SECTION 2: AKTIVITAS INVESTASI -->
                                <tr class="border-b-2 border-amber-500/30 bg-amber-50/50 dark:bg-amber-950/20">
                                    <td
                                        colspan="2"
                                        class="px-4 py-3 text-lg font-bold text-amber-700 dark:text-amber-400"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Building class="h-5 w-5" />
                                            AKTIVITAS INVESTASI
                                        </div>
                                        <div class="mt-1 text-xs font-normal text-amber-600/70 dark:text-amber-400/70">
                                            Investing Activities (Pembelian Aset Tetap, dll.)
                                        </div>
                                    </td>
                                </tr>

                                <template v-if="investing_activities.length > 0">
                                    <tr
                                        v-for="activity in investing_activities"
                                        :key="activity.transaction_type_id"
                                        class="border-b border-sidebar-border/60 transition-colors hover:bg-muted/20"
                                    >
                                        <td class="py-3 pl-8 pr-4 text-foreground">
                                            <div class="flex items-center gap-2">
                                                <ArrowUpRight
                                                    v-if="isPositive(activity.amount)"
                                                    class="h-4 w-4 text-emerald-500"
                                                />
                                                <ArrowDownLeft
                                                    v-else
                                                    class="h-4 w-4 text-rose-500"
                                                />
                                                <span class="mr-2 font-mono text-xs text-muted-foreground">{{ activity.transaction_type_code }}</span>
                                                {{ activity.transaction_type_name }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right"
                                            :class="isNegative(activity.amount) ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                        >
                                            {{ isNegative(activity.amount) ? '(' : '' }}{{ formatCurrency(activity.amount) }}{{ isNegative(activity.amount) ? ')' : '' }}
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else class="border-b border-sidebar-border/60">
                                    <td colspan="2" class="px-4 py-3 text-center text-muted-foreground italic">
                                        Tidak ada transaksi investasi
                                    </td>
                                </tr>

                                <!-- Total Investing Activities -->
                                <tr class="border-b-2 border-amber-500/50 bg-amber-100/80 dark:bg-amber-900/30">
                                    <td class="px-4 py-3 text-right font-bold text-amber-700 dark:text-amber-400">
                                        Arus Kas Bersih dari Aktivitas Investasi
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-bold"
                                        :class="isNegative(investing_total) ? 'text-rose-700 dark:text-rose-400' : 'text-amber-700 dark:text-amber-400'"
                                    >
                                        {{ isNegative(investing_total) ? '(' : '' }}{{ formatCurrency(investing_total) }}{{ isNegative(investing_total) ? ')' : '' }}
                                    </td>
                                </tr>

                                <!-- Spacer -->
                                <tr>
                                    <td colspan="2" class="h-4"></td>
                                </tr>

                                <!-- SECTION 3: AKTIVITAS PENDANAAN -->
                                <tr class="border-b-2 border-violet-500/30 bg-violet-50/50 dark:bg-violet-950/20">
                                    <td
                                        colspan="2"
                                        class="px-4 py-3 text-lg font-bold text-violet-700 dark:text-violet-400"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Wallet class="h-5 w-5" />
                                            AKTIVITAS PENDANAAN
                                        </div>
                                        <div class="mt-1 text-xs font-normal text-violet-600/70 dark:text-violet-400/70">
                                            Financing Activities (Pinjaman Bank, Setoran Modal, dll.)
                                        </div>
                                    </td>
                                </tr>

                                <template v-if="financing_activities.length > 0">
                                    <tr
                                        v-for="activity in financing_activities"
                                        :key="activity.transaction_type_id"
                                        class="border-b border-sidebar-border/60 transition-colors hover:bg-muted/20"
                                    >
                                        <td class="py-3 pl-8 pr-4 text-foreground">
                                            <div class="flex items-center gap-2">
                                                <ArrowUpRight
                                                    v-if="isPositive(activity.amount)"
                                                    class="h-4 w-4 text-emerald-500"
                                                />
                                                <ArrowDownLeft
                                                    v-else
                                                    class="h-4 w-4 text-rose-500"
                                                />
                                                <span class="mr-2 font-mono text-xs text-muted-foreground">{{ activity.transaction_type_code }}</span>
                                                {{ activity.transaction_type_name }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right"
                                            :class="isNegative(activity.amount) ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                                        >
                                            {{ isNegative(activity.amount) ? '(' : '' }}{{ formatCurrency(activity.amount) }}{{ isNegative(activity.amount) ? ')' : '' }}
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else class="border-b border-sidebar-border/60">
                                    <td colspan="2" class="px-4 py-3 text-center text-muted-foreground italic">
                                        Tidak ada transaksi pendanaan
                                    </td>
                                </tr>

                                <!-- Total Financing Activities -->
                                <tr class="border-b-2 border-violet-500/50 bg-violet-100/80 dark:bg-violet-900/30">
                                    <td class="px-4 py-3 text-right font-bold text-violet-700 dark:text-violet-400">
                                        Arus Kas Bersih dari Aktivitas Pendanaan
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-bold"
                                        :class="isNegative(financing_total) ? 'text-rose-700 dark:text-rose-400' : 'text-violet-700 dark:text-violet-400'"
                                    >
                                        {{ isNegative(financing_total) ? '(' : '' }}{{ formatCurrency(financing_total) }}{{ isNegative(financing_total) ? ')' : '' }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <!-- Spacer -->
                                <tr>
                                    <td colspan="2" class="h-4 border-t border-sidebar-border/60"></td>
                                </tr>

                                <!-- Net Change in Cash -->
                                <tr
                                    class="border-b-2"
                                    :class="netChangeValue >= 0
                                        ? 'border-emerald-500/50 bg-emerald-50/80 dark:bg-emerald-950/30'
                                        : 'border-rose-500/50 bg-rose-50/80 dark:bg-rose-950/30'"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <TrendingUp
                                                v-if="netChangeValue >= 0"
                                                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                                            />
                                            <TrendingDown
                                                v-else
                                                class="h-5 w-5 text-rose-600 dark:text-rose-400"
                                            />
                                            <span
                                                class="font-bold"
                                                :class="netChangeValue >= 0
                                                    ? 'text-emerald-700 dark:text-emerald-400'
                                                    : 'text-rose-700 dark:text-rose-400'"
                                            >
                                                {{ netChangeValue >= 0 ? 'Kenaikan' : 'Penurunan' }} Bersih Kas
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-bold"
                                        :class="netChangeValue >= 0
                                            ? 'text-emerald-700 dark:text-emerald-400'
                                            : 'text-rose-700 dark:text-rose-400'"
                                    >
                                        {{ isNegative(net_change_in_cash) ? '(' : '' }}{{ formatCurrency(net_change_in_cash) }}{{ isNegative(net_change_in_cash) ? ')' : '' }}
                                    </td>
                                </tr>

                                <!-- Beginning Cash Balance -->
                                <tr class="border-b border-sidebar-border/60 bg-muted/10">
                                    <td class="px-4 py-3 font-medium text-foreground">
                                        Saldo Kas Awal Periode
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-foreground">
                                        {{ formatCurrency(beginning_cash_balance) }}
                                    </td>
                                </tr>

                                <!-- Ending Cash Balance -->
                                <tr class="border-t-4 border-cyan-600 bg-gradient-to-r from-cyan-100/80 to-teal-100/80 dark:from-cyan-900/40 dark:to-teal-900/40">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <Banknote class="h-6 w-6 text-cyan-700 dark:text-cyan-400" />
                                            <span class="text-lg font-bold text-cyan-800 dark:text-cyan-300">
                                                SALDO KAS AKHIR
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right" style="border-bottom: 4px double currentColor;">
                                        <span class="text-xl font-bold text-cyan-800 dark:text-cyan-300">
                                            {{ formatCurrency(ending_cash_balance) }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div v-if="hasData" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 print:hidden">
                    <div class="rounded-lg border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-800 dark:bg-sky-950/30">
                        <div class="flex items-center gap-2">
                            <HandCoins class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                            <p class="text-sm font-medium text-sky-600 dark:text-sky-400">Operasional</p>
                        </div>
                        <p
                            class="mt-2 text-xl font-bold"
                            :class="isNegative(operating_total) ? 'text-rose-700 dark:text-rose-300' : 'text-sky-700 dark:text-sky-300'"
                        >
                            {{ isNegative(operating_total) ? '(' : '' }}{{ formatCurrency(operating_total) }}{{ isNegative(operating_total) ? ')' : '' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                        <div class="flex items-center gap-2">
                            <Building class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Investasi</p>
                        </div>
                        <p
                            class="mt-2 text-xl font-bold"
                            :class="isNegative(investing_total) ? 'text-rose-700 dark:text-rose-300' : 'text-amber-700 dark:text-amber-300'"
                        >
                            {{ isNegative(investing_total) ? '(' : '' }}{{ formatCurrency(investing_total) }}{{ isNegative(investing_total) ? ')' : '' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-violet-200 bg-violet-50/80 p-4 dark:border-violet-800 dark:bg-violet-950/30">
                        <div class="flex items-center gap-2">
                            <Wallet class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                            <p class="text-sm font-medium text-violet-600 dark:text-violet-400">Pendanaan</p>
                        </div>
                        <p
                            class="mt-2 text-xl font-bold"
                            :class="isNegative(financing_total) ? 'text-rose-700 dark:text-rose-300' : 'text-violet-700 dark:text-violet-300'"
                        >
                            {{ isNegative(financing_total) ? '(' : '' }}{{ formatCurrency(financing_total) }}{{ isNegative(financing_total) ? ')' : '' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-cyan-200 bg-gradient-to-r from-cyan-50/80 to-teal-50/80 p-4 dark:border-cyan-800 dark:from-cyan-950/30 dark:to-teal-950/30">
                        <div class="flex items-center gap-2">
                            <Banknote class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
                            <p class="text-sm font-medium text-cyan-600 dark:text-cyan-400">Saldo Akhir</p>
                        </div>
                        <p class="mt-2 text-xl font-bold text-cyan-700 dark:text-cyan-300">
                            {{ formatCurrency(ending_cash_balance) }}
                        </p>
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

