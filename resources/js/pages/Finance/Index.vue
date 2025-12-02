<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import ChartOfAccountController from '@/actions/App/Http/Controllers/Finance/ChartOfAccountController';
import TransactionTypeController from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
import JournalEntryController from '@/actions/App/Http/Controllers/Finance/JournalEntryController';
import TransactionController from '@/actions/App/Http/Controllers/Finance/TransactionController';
import ReportController from '@/actions/App/Http/Controllers/Finance/ReportController';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Wallet, FileText, BookOpen, CreditCard, Banknote, Landmark, Scale, TrendingUp } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
];

const modules = [
    {
        title: 'Chart of Accounts',
        description: 'Manage your chart of accounts and account structure',
        icon: Wallet,
        href: ChartOfAccountController.index().url,
    },
    {
        title: 'Transaction Configurations',
        description: 'Configure transaction types and account mappings',
        icon: FileText,
        href: TransactionTypeController.index().url,
    },
    {
        title: 'Journal Entries',
        description: 'Record and manage journal entries',
        icon: BookOpen,
        href: JournalEntryController.index().url,
    },
    {
        title: 'Transactions',
        description: 'View and manage financial transactions',
        icon: CreditCard,
        href: TransactionController.index().url,
    },
];

const financialStatements = [
    {
        title: 'Income Statement',
        description: 'Profit and Loss Statement Report (Laba Rugi)',
        icon: TrendingUp,
        href: ReportController.incomeStatementIndex().url,
    },
    {
        title: 'Balance Sheet',
        description: 'Statement of Financial Position (Neraca)',
        icon: Landmark,
        href: ReportController.balanceSheetIndex().url,
    },
    {
        title: 'Cash Flow Statement',
        description: 'Laporan Arus Kas (Direct Method)',
        icon: Banknote,
        href: ReportController.cashFlowIndex().url,
    },
];

const ledgersAndControl = [
    {
        title: 'Trial Balance',
        description: 'Summary of all account balances for a period (Neraca Saldo)',
        icon: Scale,
        href: ReportController.trialBalanceIndex().url,
    },
    {
        title: 'General Ledger',
        description: 'View detailed account transactions and running balances (Buku Besar)',
        icon: BookOpen,
        href: ReportController.generalLedgerIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Finance" />

        <div>
            <div class="pb-6">
                <Heading
                    title="Finance"
                    description="Manage your financial operations and view reports"
                />
            </div>

            <div class="space-y-8">
                <!-- Finance Modules Section -->
                <div>
                    <h2 class="mb-4 text-lg font-semibold text-foreground">
                        Modules
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="module in modules"
                            :key="module.title"
                            :href="module.href"
                            class="group"
                        >
                            <Card class="h-full transition-all duration-200 hover:border-primary/50 hover:shadow-md group-hover:bg-muted/30">
                                <CardHeader>
                                    <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary/20">
                                        <component
                                            :is="module.icon"
                                            class="h-5 w-5"
                                        />
                                    </div>
                                    <CardTitle class="text-lg">
                                        {{ module.title }}
                                    </CardTitle>
                                    <CardDescription>
                                        {{ module.description }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <span class="text-sm font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100">
                                        View Module →
                                    </span>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>

                <!-- Reports Section -->
                <div>
                    <h2 class="mb-4 text-lg font-semibold text-foreground">
                        Reports
                    </h2>
                    <div class="space-y-6">
                        <!-- Financial Statements Section -->
                        <div>
                            <h3 class="mb-3 text-sm font-medium text-muted-foreground">
                                Financial Statements
                            </h3>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <Link
                                    v-for="report in financialStatements"
                                    :key="report.title"
                                    :href="report.href"
                                    class="group"
                                >
                                    <Card class="h-full transition-all duration-200 hover:border-primary/50 hover:shadow-md group-hover:bg-muted/30">
                                        <CardHeader>
                                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary/20">
                                                <component
                                                    :is="report.icon"
                                                    class="h-5 w-5"
                                                />
                                            </div>
                                            <CardTitle class="text-lg">
                                                {{ report.title }}
                                            </CardTitle>
                                            <CardDescription>
                                                {{ report.description }}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <span class="text-sm font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100">
                                                View Report →
                                            </span>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </div>
                        </div>

                        <!-- Ledgers & Control Section -->
                        <div>
                            <h3 class="mb-3 text-sm font-medium text-muted-foreground">
                                Ledgers & Control
                            </h3>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <Link
                                    v-for="report in ledgersAndControl"
                                    :key="report.title"
                                    :href="report.href"
                                    class="group"
                                >
                                    <Card class="h-full transition-all duration-200 hover:border-primary/50 hover:shadow-md group-hover:bg-muted/30">
                                        <CardHeader>
                                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary/20">
                                                <component
                                                    :is="report.icon"
                                                    class="h-5 w-5"
                                                />
                                            </div>
                                            <CardTitle class="text-lg">
                                                {{ report.title }}
                                            </CardTitle>
                                            <CardDescription>
                                                {{ report.description }}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <span class="text-sm font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100">
                                                View Report →
                                            </span>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

