<script setup lang="ts">
import TransactionTypeController from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

interface TransactionEntryConfig {
    id: string;
    transaction_type_id: string;
    config_key: string;
    ui_label: string;
    position: string;
    account_type_filter: string | null;
    account_id: string | null;
    is_required: boolean;
    account?: {
        id: string;
        code: string;
        name: string;
        account_type: string;
    } | null;
}

interface TransactionType {
    id: string;
    is_system: boolean;
    code: string;
    name: string;
    category: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    configs: TransactionEntryConfig[];
}

interface Props {
    transactionType: TransactionType;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Journal Configuration',
        href: TransactionTypeController.index().url,
    },
    {
        title: props.transactionType.name,
        href: TransactionTypeController.show.url(props.transactionType.id),
    },
];

const formatDateTime = (value: string) =>
    new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const getCategoryBadgeClass = (category: string): string => {
    const classes: Record<string, string> = {
        BILLING: 'bg-purple-500/10 text-purple-700 dark:bg-purple-500/20 dark:text-purple-100',
        INCOME: 'bg-green-500/10 text-green-700 dark:bg-green-500/20 dark:text-green-100',
        EXPENSE: 'bg-orange-500/10 text-orange-700 dark:bg-orange-500/20 dark:text-orange-100',
        PAYROLL: 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-100',
        TRANSFER: 'bg-gray-500/10 text-gray-700 dark:bg-gray-500/20 dark:text-gray-100',
        ASSET: 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-100',
        LIABILITY: 'bg-red-500/10 text-red-700 dark:bg-red-500/20 dark:text-red-100',
    };
    return classes[category] || 'bg-gray-500/10 text-gray-700 dark:bg-gray-500/20 dark:text-gray-100';
};

const getPositionBadgeClass = (position: string): string => {
    return position === 'DEBIT'
        ? 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-100'
        : 'bg-green-500/10 text-green-700 dark:bg-green-500/20 dark:text-green-100';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="props.transactionType.name" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <div class="flex items-center gap-2 mb-2">
                        <Heading
                            :title="props.transactionType.name"
                            description="Transaction type details and account mappings"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <Badge
                            :class="`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${props.transactionType.is_system
                                ? 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-100'
                                : 'bg-green-500/10 text-green-700 dark:bg-green-500/20 dark:text-green-100'
                            }`"
                        >
                            {{ props.transactionType.is_system ? 'SYSTEM' : 'CUSTOM' }}
                        </Badge>
                        <Badge
                            :class="`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${getCategoryBadgeClass(props.transactionType.category)}`"
                        >
                            {{ props.transactionType.category }}
                        </Badge>
                        <Badge
                            v-if="!props.transactionType.is_active"
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-500/10 text-red-700 dark:bg-red-500/20 dark:text-red-100"
                        >
                            Inactive
                        </Badge>
                    </div>
                </div>
                <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                    <Button
                        v-if="!props.transactionType.is_system"
                        as-child
                        class="w-full md:w-auto"
                    >
                        <Link
                            :href="TransactionTypeController.edit.url(props.transactionType.id)"
                        >
                            Edit Transaction Type
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="secondary"
                        class="w-full md:w-auto"
                    >
                        <Link :href="TransactionTypeController.index().url">
                            Back to Configuration
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="space-y-6">
                    <dl class="grid gap-6 md:grid-cols-2">
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Transaction Code
                            </dt>
                            <dd class="mt-2 text-base font-mono font-semibold text-foreground">
                                {{ props.transactionType.code }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Transaction Name
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                {{ props.transactionType.name }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Category
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                {{ props.transactionType.category }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Type
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                {{ props.transactionType.is_system ? 'System' : 'Custom' }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Created
                            </dt>
                            <dd class="mt-2 text-base text-foreground">
                                {{ formatDateTime(props.transactionType.created_at) }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Updated
                            </dt>
                            <dd class="mt-2 text-base text-foreground">
                                {{ formatDateTime(props.transactionType.updated_at) }}
                            </dd>
                        </div>
                    </dl>

                    <div
                        v-if="props.transactionType.configs.length > 0"
                        class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border"
                    >
                        <dt class="text-sm font-medium text-muted-foreground mb-4">
                            Account Mappings ({{ props.transactionType.configs.length }})
                        </dt>
                        <dd class="space-y-3">
                            <div
                                v-for="config in props.transactionType.configs"
                                :key="config.id"
                                class="rounded-md border border-sidebar-border/60 p-4 dark:border-sidebar-border"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 space-y-2">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-semibold">
                                                {{ config.ui_label }}
                                            </h4>
                                            <Badge
                                                v-if="config.is_required"
                                                class="bg-orange-500/10 text-orange-700 dark:bg-orange-500/20 dark:text-orange-100"
                                            >
                                                Required
                                            </Badge>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Badge
                                                :class="`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${getPositionBadgeClass(config.position)}`"
                                            >
                                                {{ config.position }}
                                            </Badge>
                                            <span
                                                v-if="config.account_type_filter"
                                                class="text-xs text-muted-foreground"
                                            >
                                                Filter: {{ config.account_type_filter }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="config.account"
                                            class="rounded-md bg-muted p-3"
                                        >
                                            <div class="font-mono text-sm font-semibold">
                                                {{ config.account.code }}
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ config.account.name }}
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="rounded-md border border-dashed border-muted-foreground/30 p-3 text-center text-sm text-muted-foreground"
                                        >
                                            No account mapped
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </dd>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

