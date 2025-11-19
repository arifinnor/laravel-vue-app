<script setup lang="ts">
import TransactionTypeController, { updateConfig } from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
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
    configs: TransactionEntryConfig[];
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
        title: 'Journal Configuration',
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

const updateConfigMapping = (
    transactionTypeId: string,
    configId: string,
    accountId: string | null,
) => {
    router.post(
        updateConfig.url({ transaction_type: transactionTypeId, config: configId }),
        { account_id: accountId },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Success handled by flash message
            },
        },
    );
};

const hasRequiredMissing = (config: TransactionEntryConfig): boolean => {
    return config.is_required && !config.account_id;
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
                        :class="[
                            'transition-all',
                            transactionType.configs?.some(hasRequiredMissing)
                                ? 'border-destructive'
                                : '',
                        ]"
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
                                <Alert
                                    v-if="transactionType.configs?.some(hasRequiredMissing)"
                                    variant="destructive"
                                >
                                    <AlertCircle class="h-4 w-4" />
                                    <AlertDescription>
                                        Some required account mappings are missing. Please
                                        configure all required accounts.
                                    </AlertDescription>
                                </Alert>

                                <div
                                    v-for="config in transactionType.configs || []"
                                    :key="config.id"
                                    :class="[
                                        'rounded-lg border p-4',
                                        hasRequiredMissing(config)
                                            ? 'border-destructive bg-destructive/5'
                                            : 'border-border',
                                    ]"
                                >
                                    <div class="space-y-3">
                                        <div>
                                            <label
                                                :for="`config-${config.id}`"
                                                class="text-sm font-medium"
                                            >
                                                {{ config.ui_label }}
                                                <span
                                                    v-if="config.is_required"
                                                    class="text-destructive"
                                                >
                                                    *
                                                </span>
                                            </label>
                                            <p
                                                v-if="config.account_type_filter"
                                                class="text-xs text-muted-foreground mt-1"
                                            >
                                                Must be a {{ config.account_type_filter }}
                                                account
                                            </p>
                                        </div>

                                        <div>
                                            <AccountCombobox
                                                :id="`config-${config.id}`"
                                                :model-value="config.account_id"
                                                :accounts="chartOfAccounts"
                                                :account-type-filter="
                                                    config.account_type_filter
                                                "
                                                :is-required="config.is_required"
                                                placeholder="Select account..."
                                                @update:model-value="
                                                    (value) =>
                                                        updateConfigMapping(
                                                            transactionType.id,
                                                            config.id,
                                                            value,
                                                        )
                                                "
                                            />
                                            <InputError
                                                v-if="hasRequiredMissing(config)"
                                                message="This account mapping is required."
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

