<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import TransactionTypeController, { updateAccount } from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/vue3';

import AccountCombobox from '@/components/Finance/AccountCombobox.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { AlertCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface TransactionAccount {
    id: string;
    transaction_type_id: string;
    role: string;
    label: string;
    direction: string;
    account_type: string;
    chart_of_account_id: string | null;
    chart_of_account?: {
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
    accounts: TransactionAccount[];
}

interface ChartOfAccount {
    id: string;
    code: string;
    name: string;
    account_type: string;
    display: string;
}

interface Props {
    transactionTypes: TransactionType[];
    groupedTransactionTypes: Record<string, TransactionType[]>;
    chartOfAccounts: ChartOfAccount[];
    categories: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Transaction Configurations',
        href: TransactionTypeController.index().url,
    },
];

const selectedCategory = ref<string>('ALL');

const filteredTransactionTypes = computed(() => {
    if (selectedCategory.value === 'ALL') {
        return props.transactionTypes;
    }

    return props.transactionTypes.filter(
        (type) => type.category === selectedCategory.value,
    );
});

const categoriesWithAll = computed(() => {
    return ['ALL', ...props.categories];
});

const updateAccountMapping = (
    transactionTypeId: string,
    accountId: string,
    chartOfAccountId: string | null,
) => {
    router.post(
        updateAccount.url({ transaction_type: transactionTypeId, account: accountId }),
        { chart_of_account_id: chartOfAccountId },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Success handled by flash message
            },
        },
    );
};

const getDirectionBadgeClass = (direction: string): string => {
    if (direction === 'DEBIT') {
        return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100';
    }
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Journal Configuration" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Journal Configuration"
                        description="Map transaction types to chart of accounts"
                    />
                </div>
                <Button as-child class="w-full md:w-auto">
                    <Link :href="TransactionTypeController.create().url">
                        Create Transaction Type
                    </Link>
                </Button>
            </div>

            <div class="flex flex-col gap-6 lg:flex-row">
                <!-- Sidebar Category Filter -->
                <div class="w-full lg:w-64 lg:flex-shrink-0">
                    <Card>
                        <CardHeader>
                            <h3 class="text-sm font-semibold">Categories</h3>
                        </CardHeader>
                        <CardContent>
                            <nav class="space-y-1">
                                <button
                                    v-for="category in categoriesWithAll"
                                    :key="category"
                                    type="button"
                                    :class="[
                                        'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                                        selectedCategory === category
                                            ? 'bg-primary text-primary-foreground'
                                            : 'hover:bg-accent hover:text-accent-foreground',
                                    ]"
                                    @click="selectedCategory = category"
                                >
                                    {{ category === 'ALL' ? 'All Categories' : category }}
                                </button>
                            </nav>
                        </CardContent>
                    </Card>
                </div>

                <!-- Main Content -->
                <div class="flex-1 space-y-4">
                    <div v-if="filteredTransactionTypes.length === 0" class="text-center py-12">
                        <p class="text-muted-foreground">
                            No transaction types found for this category.
                        </p>
                    </div>

                    <Card
                        v-for="transactionType in filteredTransactionTypes"
                        :key="transactionType.id"
                        class="transition-all"
                    >
                        <CardHeader>
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-semibold">
                                            {{ transactionType.name }}
                                        </h3>
                                        <Badge
                                            :class="[
                                                transactionType.is_system
                                                    ? 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-100'
                                                    : 'bg-green-500/10 text-green-700 dark:bg-green-500/20 dark:text-green-100',
                                            ]"
                                        >
                                            {{ transactionType.is_system ? 'SYSTEM' : 'CUSTOM' }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground font-mono">
                                        {{ transactionType.code }}
                                    </p>
                                </div>
                                <Button
                                    as-child
                                    variant="outline"
                                    size="sm"
                                    class="shrink-0"
                                >
                                    <Link
                                        :href="TransactionTypeController.show.url(transactionType.id)"
                                    >
                                        View Details
                                    </Link>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div
                                    v-for="account in transactionType.accounts || []"
                                    :key="account.id"
                                    class="rounded-lg border border-border p-4"
                                >
                                    <div class="space-y-3">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <label
                                                    :for="`account-${account.id}`"
                                                    class="text-sm font-medium"
                                                >
                                                    {{ account.label }}
                                                </label>
                                                <Badge :class="getDirectionBadgeClass(account.direction)">
                                                    {{ account.direction }}
                                                </Badge>
                                            </div>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                Type: {{ account.account_type }}
                                            </p>
                                        </div>

                                        <div>
                                            <AccountCombobox
                                                :id="`account-${account.id}`"
                                                :model-value="account.chart_of_account_id"
                                                :accounts="chartOfAccounts"
                                                :account-type-filter="account.account_type"
                                                placeholder="Select account..."
                                                @update:model-value="
                                                    (value) =>
                                                        updateAccountMapping(
                                                            transactionType.id,
                                                            account.id,
                                                            value,
                                                        )
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

