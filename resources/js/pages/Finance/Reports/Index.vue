<script setup lang="ts">
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
import { BookOpen, Landmark, Scale, TrendingUp } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: ReportController.index().url,
    },
    {
        title: 'Reports',
        href: ReportController.index().url,
    },
];

const reports = [
    {
        title: 'General Ledger',
        description: 'View detailed account transactions and running balances',
        icon: BookOpen,
        href: ReportController.generalLedgerIndex().url,
    },
    {
        title: 'Trial Balance',
        description: 'Summary of all account balances for a period',
        icon: Scale,
        href: ReportController.trialBalanceIndex().url,
    },
    {
        title: 'Income Statement',
        description: 'Profit and Loss Statement Report',
        icon: TrendingUp,
        href: ReportController.incomeStatementIndex().url,
    },
    {
        title: 'Balance Sheet',
        description: 'Statement of Financial Position',
        icon: Landmark,
        href: ReportController.balanceSheetIndex().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Finance Reports" />

        <div>
            <div class="pb-6">
                <Heading
                    title="Financial Reports"
                    description="Select a report to view financial data and insights"
                />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="report in reports"
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
    </AppLayout>
</template>





