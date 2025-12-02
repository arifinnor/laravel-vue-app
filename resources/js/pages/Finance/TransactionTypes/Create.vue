<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import TransactionTypeController from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';

import AccountCombobox from '@/components/Finance/AccountCombobox.vue';
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
    role: string;
    label: string;
    direction: string;
    account_type: string | null;
    chart_of_account_id: string | null;
}

interface Props {
    transactionCategories: string[];
    chartOfAccounts: ChartOfAccount[];
}

const props = defineProps<Props>();

const generateRandomRole = () => {
    return `custom_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
};

const form = useForm({
    code: '',
    name: '',
    category: '',
    is_active: true,
    accounts: [
        {
            role: 'main_debit',
            label: 'Debit Account',
            direction: 'DEBIT',
            account_type: null,
            chart_of_account_id: null,
        },
        {
            role: 'main_credit',
            label: 'Credit Account',
            direction: 'CREDIT',
            account_type: null,
            chart_of_account_id: null,
        },
    ] as TransactionAccount[],
});

const onAccountSelect = (index: number, accountId: string | null) => {
    form.accounts[index].chart_of_account_id = accountId;

    if (accountId) {
        const selectedAccount = props.chartOfAccounts.find((coa) => coa.id === accountId);
        if (selectedAccount) {
            // Auto-fill label with COA name for convenience
            form.accounts[index].label = selectedAccount.name;
            form.accounts[index].account_type = selectedAccount.account_type;
        }
    }
};

const addAccountLine = () => {
    form.accounts.push({
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
        title: 'Create Transaction Type',
        href: TransactionTypeController.create().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Transaction Type" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Create Transaction Type"
                        description="Create a custom transaction type for your accounting system"
                    />
                </div>
                <Button
                    as-child
                    variant="secondary"
                    class="w-full md:w-auto"
                >
                    <Link :href="TransactionTypeController.index().url">
                        Cancel
                    </Link>
                </Button>
            </CardHeader>
            <form
                @submit.prevent="form.post(TransactionTypeController.store().url)"
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
                            v-model="form.code"
                            required
                            placeholder="e.g., CUST_TRX_001"
                            autofocus
                        />
                        <InputError :message="form.errors.code" />
                        <p class="text-xs text-muted-foreground">
                            Unique identifier for this transaction type (e.g., CUST_TRX_001)
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
                            placeholder="e.g., Purchase Snacks"
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
                                    <Select v-model="form.accounts[index].direction">
                                        <SelectTrigger :id="`account-direction-${index}`">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="DEBIT">DEBIT</SelectItem>
                                            <SelectItem value="CREDIT">CREDIT</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors[`accounts.${index}.direction`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`account-label-${index}`">
                                        Label
                                    </Label>
                                    <Input
                                        :id="`account-label-${index}`"
                                        v-model="form.accounts[index].label"
                                        placeholder="Custom label (optional)"
                                    />
                                    <InputError :message="form.errors[`accounts.${index}.label`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label class="invisible">Action</Label>
                                    <Button
                                        v-if="form.accounts.length > 1"
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

                            <div class="pt-2">
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
                        {{ form.processing ? 'Creating...' : 'Create Transaction Type' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </AppLayout>
</template>

