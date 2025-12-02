<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import ChartOfAccountController from '@/actions/App/Http/Controllers/Finance/ChartOfAccountController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface ChartOfAccount {
    id: string;
    category_id: string | null;
    code: string;
    name: string;
    description: string | null;
    parent_id: string | null;
    account_type: string;
    normal_balance: string;
    is_posting: boolean;
    is_cash: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    category?: {
        id: string;
        name: string;
    } | null;
    parent?: {
        id: string;
        code: string;
        name: string;
    } | null;
    is_header: boolean;
    has_children: boolean;
}

interface Breadcrumb {
    id: string;
    code: string;
    name: string;
}

interface Props {
    account: ChartOfAccount;
    children: ChartOfAccount[];
    breadcrumbs: Breadcrumb[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Chart of Accounts',
        href: ChartOfAccountController.index().url,
    },
    {
        title: props.account.name,
        href: ChartOfAccountController.show.url(props.account.id),
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

const getAccountTypeBadgeClass = (type: string): string => {
    const classes: Record<string, string> = {
        ASSET: 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-100',
        LIABILITY: 'bg-red-500/10 text-red-700 dark:bg-red-500/20 dark:text-red-100',
        EQUITY: 'bg-purple-500/10 text-purple-700 dark:bg-purple-500/20 dark:text-purple-100',
        REVENUE: 'bg-green-500/10 text-green-700 dark:bg-green-500/20 dark:text-green-100',
        EXPENSE: 'bg-orange-500/10 text-orange-700 dark:bg-orange-500/20 dark:text-orange-100',
    };
    return classes[type] || 'bg-gray-500/10 text-gray-700 dark:bg-gray-500/20 dark:text-gray-100';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="props.account.name" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <div class="flex items-center gap-2 mb-2">
                        <Heading
                            :title="props.account.name"
                            description="Account details and hierarchy"
                        />
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <Badge
                            :class="`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${getAccountTypeBadgeClass(props.account.account_type)}`"
                        >
                            {{ props.account.account_type }}
                        </Badge>
                        <Badge
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-500/10 text-gray-700 dark:bg-gray-500/20 dark:text-gray-100"
                        >
                            {{ props.account.normal_balance }} Normal
                        </Badge>
                        <Badge
                            v-if="props.account.is_header"
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-500/10 text-gray-700 dark:bg-gray-500/20 dark:text-gray-100"
                        >
                            Header Account
                        </Badge>
                        <Badge
                            v-else
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-100"
                        >
                            Posting Account
                        </Badge>
                        <Badge
                            v-if="props.account.is_cash"
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-yellow-500/10 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-100"
                        >
                            Cash Account
                        </Badge>
                        <Badge
                            v-if="!props.account.is_active"
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-500/10 text-red-700 dark:bg-red-500/20 dark:text-red-100"
                        >
                            Inactive
                        </Badge>
                    </div>
                </div>
                <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                    <Button
                        as-child
                        class="w-full md:w-auto"
                    >
                        <Link :href="ChartOfAccountController.edit.url(props.account.id)">
                            Edit Account
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="secondary"
                        class="w-full md:w-auto"
                    >
                        <Link :href="ChartOfAccountController.index().url">
                            Back to Accounts
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="space-y-6">
                    <div v-if="props.breadcrumbs.length > 0" class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground mb-2">
                            Parent Hierarchy
                        </dt>
                        <dd class="flex items-center gap-2 flex-wrap">
                            <template v-for="(crumb, index) in props.breadcrumbs" :key="crumb.id">
                                <Link
                                    :href="ChartOfAccountController.show.url(crumb.id)"
                                    class="text-primary underline-offset-4 transition hover:underline"
                                >
                                    {{ crumb.code }} - {{ crumb.name }}
                                </Link>
                                <span v-if="index < props.breadcrumbs.length - 1" class="text-muted-foreground">/</span>
                            </template>
                        </dd>
                    </div>

                    <dl class="grid gap-6 md:grid-cols-2">
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Account Code
                            </dt>
                            <dd class="mt-2 text-base font-mono font-semibold text-foreground">
                                {{ props.account.code }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Account Name
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                {{ props.account.name }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Account Type
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                {{ props.account.account_type }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Normal Balance
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                {{ props.account.normal_balance }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Category
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                {{ props.account.category?.name || '—' }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Parent Account
                            </dt>
                            <dd class="mt-2 text-base font-semibold text-foreground">
                                <Link
                                    v-if="props.account.parent"
                                    :href="ChartOfAccountController.show.url(props.account.parent.id)"
                                    class="text-primary underline-offset-4 transition hover:underline"
                                >
                                    {{ props.account.parent.code }} - {{ props.account.parent.name }}
                                </Link>
                                <span v-else>—</span>
                            </dd>
                        </div>
                        <div
                            v-if="props.account.description"
                            class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border md:col-span-2"
                        >
                            <dt class="text-sm font-medium text-muted-foreground">
                                Description
                            </dt>
                            <dd class="mt-2 text-base text-foreground">
                                {{ props.account.description }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Created
                            </dt>
                            <dd class="mt-2 text-base text-foreground">
                                {{ formatDateTime(props.account.created_at) }}
                            </dd>
                        </div>
                        <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                            <dt class="text-sm font-medium text-muted-foreground">
                                Updated
                            </dt>
                            <dd class="mt-2 text-base text-foreground">
                                {{ formatDateTime(props.account.updated_at) }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="props.children.length > 0" class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground mb-4">
                            Child Accounts ({{ props.children.length }})
                        </dt>
                        <dd class="space-y-2">
                            <div
                                v-for="child in props.children"
                                :key="child.id"
                                class="flex items-center justify-between rounded-md border border-sidebar-border/60 p-3 dark:border-sidebar-border"
                            >
                                <div class="flex items-center gap-3">
                                    <Link
                                        :href="ChartOfAccountController.show.url(child.id)"
                                        class="font-mono text-sm text-primary underline-offset-4 transition hover:underline"
                                    >
                                        {{ child.code }}
                                    </Link>
                                    <Link
                                        :href="ChartOfAccountController.show.url(child.id)"
                                        class="font-medium text-sm text-foreground underline-offset-4 transition hover:underline"
                                    >
                                        {{ child.name }}
                                    </Link>
                                </div>
                                <Badge
                                    :class="`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${getAccountTypeBadgeClass(child.account_type)}`"
                                >
                                    {{ child.account_type }}
                                </Badge>
                            </div>
                        </dd>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>

