<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import TransactionTypeController from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed } from 'vue';

import AccountCombobox from '@/components/Finance/AccountCombobox.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

interface ChartOfAccount {
    id: string;
    code: string;
    name: string;
    account_type: string;
    display: string;
}

interface TransactionAccount {
    id?: string;
    role: string;
    label: string;
    direction: string;
    account_type: string | null;
    chart_of_account_id: string | null;
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

interface Props {
    transactionType: TransactionType;
    transactionCategories: string[];
    chartOfAccounts: ChartOfAccount[];
}

const props = defineProps<Props>();

const form = useForm({
    name: props.transactionType.name,
    category: props.transactionType.category,
    is_active: props.transactionType.is_active,
    accounts: props.transactionType.accounts.map((account) => ({
        id: account.id,
        role: account.role,
        label: account.label,
        direction: account.direction,
        account_type: account.account_type,
        chart_of_account_id: account.chart_of_account_id,
    })),
});

const isSystemType = computed(() => props.transactionType.is_system);

const onAccountSelect = (index: number, accountId: string | null) => {
    form.accounts[index].chart_of_account_id = accountId;

    if (accountId) {
        const selectedAccount = props.chartOfAccounts.find((coa) => coa.id === accountId);
        if (selectedAccount) {
            // Auto-fill label with COA name for convenience (only for custom types or empty labels)
            if (!isSystemType.value || !form.accounts[index].label) {
                form.accounts[index].label = selectedAccount.name;
            }
            form.accounts[index].account_type = selectedAccount.account_type;
        }
    }
};

const generateRandomRole = () => {
    return `custom_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
};

const addAccountLine = () => {
    form.accounts.push({
        id: undefined,
        role: generateRandomRole(),
        label: '',
        direction: 'DEBIT',
        account_type: null,
        chart_of_account_id: null,
    });
};

const removeAccountLine = (index: number) => {
    if (form.accounts.length > 1) {
        form.accounts.splice(index, 1);
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Transaction Configurations',
        href: TransactionTypeController.index().url,
    },
    {
        title: props.transactionType.name,
        href: TransactionTypeController.show.url(props.transactionType.id),
    },
    {
        title: 'Edit',
        href: TransactionTypeController.edit.url(props.transactionType.id),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${props.transactionType.name}`" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        :title="`Edit ${props.transactionType.name}`"
                        description="Update the transaction type details"
                    />
                </div>
                <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                    <Button
                        as-child
                        variant="secondary"
                        class="w-full md:w-auto"
                    >
                        <Link
                            :href="TransactionTypeController.show.url(props.transactionType.id)"
                        >
                            View Details
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        class="w-full md:w-auto"
                    >
                        <Link :href="TransactionTypeController.index().url">
                            Back to Configuration
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <form
                @submit.prevent="form.put(TransactionTypeController.update.url(props.transactionType.id), { preserveScroll: true })"
                class="contents"
            >
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="code">
                            Transaction Code
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="code"
                            type="text"
                            name="code"
                            required
                            :default-value="props.transactionType.code"
                            :disabled="true"
                        />
                        <InputError :message="form.errors.code" />
                        <p class="text-xs text-muted-foreground">
                            Code cannot be changed after creation
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">
                            Transaction Name
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            v-model="form.name"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">
                            Category
                            <span class="text-destructive">*</span>
                        </Label>
                        <Select
                            v-model="form.category"
                            required
                        >
                            <SelectTrigger id="category">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in props.transactionCategories"
                                    :key="category"
                                    :value="category"
                                >
                                    {{ category }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.category" />
                    </div>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label>
                                Journal Mapping Configuration
                            </Label>
                            <p class="text-xs text-muted-foreground">
                                Define which Chart of Accounts are mapped to this transaction type
                            </p>
                        </div>

                        <div class="space-y-4 rounded-lg border p-4">
                            <div
                                v-for="(account, index) in form.accounts"
                                :key="account.role"
                                class="grid gap-4 md:grid-cols-[1fr_120px_1fr_auto]"
                            >
                                <div class="grid gap-2">
                                    <Label :for="`account-coa-${index}`">
                                        Chart of Account
                                    </Label>
                                    <AccountCombobox
                                        :id="`account-coa-${index}`"
                                        :model-value="form.accounts[index].chart_of_account_id"
                                        :accounts="props.chartOfAccounts"
                                        placeholder="Select account..."
                                        @update:model-value="(value) => onAccountSelect(index, value)"
                                    />
                                    <InputError :message="form.errors[`accounts.${index}.chart_of_account_id`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`account-direction-${index}`">
                                        Direction
                                    </Label>
                                    <Select
                                        v-if="!isSystemType"
                                        v-model="form.accounts[index].direction"
                                    >
                                        <SelectTrigger :id="`account-direction-${index}`">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="DEBIT">DEBIT</SelectItem>
                                            <SelectItem value="CREDIT">CREDIT</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-else class="flex h-10 items-center">
                                        <Badge
                                            :variant="account.direction === 'DEBIT' ? 'default' : 'secondary'"
                                            :class="
                                                account.direction === 'DEBIT'
                                                    ? 'bg-blue-500 hover:bg-blue-600'
                                                    : 'bg-orange-500 hover:bg-orange-600'
                                            "
                                        >
                                            {{ account.direction }}
                                        </Badge>
                                    </div>
                                    <InputError :message="form.errors[`accounts.${index}.direction`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`account-label-${index}`">
                                        Label
                                    </Label>
                                    <Input
                                        v-if="!isSystemType"
                                        :id="`account-label-${index}`"
                                        v-model="form.accounts[index].label"
                                        placeholder="Custom label (optional)"
                                    />
                                    <div
                                        v-else
                                        class="flex h-10 w-full items-center rounded-md border border-input bg-muted px-3 py-2 text-sm"
                                    >
                                        {{ account.label }}
                                    </div>
                                    <InputError :message="form.errors[`accounts.${index}.label`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label class="invisible">Action</Label>
                                    <Button
                                        v-if="!isSystemType && form.accounts.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-10 w-10 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        @click="removeAccountLine(index)"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                    <div v-else class="h-10 w-10" />
                                </div>
                            </div>

                            <div v-if="!isSystemType" class="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addAccountLine"
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Add Line
                                </Button>
                            </div>
                        </div>
                        <InputError :message="form.errors.accounts" />
                    </div>
                </CardContent>

                <CardFooter class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full md:w-auto"
                    >
                        <Spinner
                            v-if="form.processing"
                            class="mr-2 h-4 w-4"
                        />
                        {{ form.processing ? 'Updating...' : 'Update Transaction Type' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </AppLayout>
</template>

