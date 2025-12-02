<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import ChartOfAccountController from '@/actions/App/Http/Controllers/Finance/ChartOfAccountController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

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
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { ref, watch } from 'vue';

interface Props {
    parentOptions: Array<{ id: string; code: string; name: string }>;
    categories: Array<{ id: string; name: string }>;
    accountTypes: string[];
    normalBalances: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Chart of Accounts',
        href: ChartOfAccountController.index().url,
    },
    {
        title: 'Create Account',
        href: ChartOfAccountController.create().url,
    },
];

const accountType = ref<string>('');
const normalBalance = ref<string>('');
const parentId = ref<string | undefined>(undefined);
const code = ref<string>('');
const codeManuallyEdited = ref<boolean>(false);
const loadingCode = ref<boolean>(false);

watch(accountType, (newType) => {
    // Auto-set normal balance based on account type
    if (newType === 'ASSET' || newType === 'EXPENSE') {
        normalBalance.value = 'DEBIT';
    } else if (newType === 'LIABILITY' || newType === 'EQUITY' || newType === 'REVENUE') {
        normalBalance.value = 'CREDIT';
    }
});

watch(parentId, async (newParentId) => {
    // Only auto-suggest code if it hasn't been manually edited
    if (codeManuallyEdited.value) {
        return;
    }

    // If parent is cleared, clear code too
    if (!newParentId || newParentId === '') {
        code.value = '';
        return;
    }

    // Set loading state
    loadingCode.value = true;

    try {
        const url = new URL('/finance/chart-of-accounts/next-code', window.location.origin);
        url.searchParams.set('parent_id', newParentId);

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Failed to fetch next code');
        }

        const data = await response.json();
        code.value = data.code;
    } catch (error) {
        console.error('Error fetching next code:', error);
        // Keep existing code on error
    } finally {
        loadingCode.value = false;
    }
}, { immediate: false });

// Track manual edits to code field
const onCodeInput = (): void => {
    codeManuallyEdited.value = true;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Account" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Create Account"
                        description="Add a new account to the chart of accounts"
                    />
                </div>
                <Button as-child variant="secondary" class="w-full md:w-auto">
                    <Link :href="ChartOfAccountController.index().url"> Cancel </Link>
                </Button>
            </CardHeader>
            <Form
                v-bind="ChartOfAccountController.store.form()"
                class="contents"
                :reset-on-success="[
                    'code',
                    'name',
                    'description',
                    'parent_id',
                    'account_type',
                    'normal_balance',
                    'is_posting',
                    'is_cash',
                    'is_active',
                ]"
                v-slot="{ errors, processing }"
            >
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="account_type">Account Type <span class="text-destructive">*</span></Label>
                        <Select v-model="accountType" name="account_type" required>
                            <SelectTrigger id="account_type">
                                <SelectValue placeholder="Select account type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in props.accountTypes"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ type }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.account_type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category_id">Category <span class="text-destructive">*</span></Label>
                        <Select name="category_id" required>
                            <SelectTrigger id="category_id">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in props.categories"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.category_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="parent_id">Parent Account</Label>
                        <Select v-model="parentId" name="parent_id">
                            <SelectTrigger id="parent_id">
                                <SelectValue placeholder="Select a parent account (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="parent in props.parentOptions"
                                    :key="parent.id"
                                    :value="String(parent.id)"
                                >
                                    {{ parent.code }} - {{ parent.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.parent_id" />
                        <p class="text-xs text-muted-foreground">
                            Select a parent account to create a hierarchical structure. Code will be auto-suggested. Leave empty for root account.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="code">Account Code <span class="text-destructive">*</span></Label>
                        <div class="relative">
                            <Input
                                id="code"
                                v-model="code"
                                type="text"
                                name="code"
                                required
                                placeholder="e.g., 1-1001"
                                autofocus
                                @input="onCodeInput"
                            />
                            <Spinner
                                v-if="loadingCode"
                                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2"
                            />
                        </div>
                        <InputError :message="errors.code" />
                        <p class="text-xs text-muted-foreground">
                            Unique identifier for this account (e.g., 1-1001, 2-2000). Code will be auto-suggested when a parent is selected.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Account Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            type="text"
                            name="name"
                            required
                            placeholder="Account name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="normal_balance">Normal Balance <span class="text-destructive">*</span></Label>
                        <Select v-model="normalBalance" name="normal_balance" required>
                            <SelectTrigger id="normal_balance">
                                <SelectValue placeholder="Select normal balance" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="balance in props.normalBalances"
                                    :key="balance"
                                    :value="balance"
                                >
                                    {{ balance }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.normal_balance" />
                        <p class="text-xs text-muted-foreground">
                            Assets and Expenses: DEBIT | Liabilities, Equity, and Revenue: CREDIT
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            name="description"
                            placeholder="Optional description"
                            :rows="3"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_posting"
                                name="is_posting"
                                value="1"
                                :checked="true"
                            />
                            <Label
                                for="is_posting"
                                class="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                Posting Account
                            </Label>
                        </div>
                        <InputError :message="errors.is_posting" />
                        <p class="text-xs text-muted-foreground -mt-2">
                            Uncheck to create a header/folder account (non-posting)
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_cash"
                                name="is_cash"
                                value="1"
                            />
                            <Label
                                for="is_cash"
                                class="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                Cash Account
                            </Label>
                        </div>
                        <InputError :message="errors.is_cash" />
                        <p class="text-xs text-muted-foreground -mt-2">
                            Check if this account should appear in payment/receipt dropdowns
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_active"
                                name="is_active"
                                value="1"
                                :checked="true"
                            />
                            <Label
                                for="is_active"
                                class="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >
                                Active
                            </Label>
                        </div>
                        <InputError :message="errors.is_active" />
                        <p class="text-xs text-muted-foreground -mt-2">
                            Uncheck to deactivate this account
                        </p>
                    </div>
                </CardContent>

                <CardFooter class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end">
                    <Button
                        type="submit"
                        :disabled="processing"
                        class="w-full md:w-auto"
                    >
                        <Spinner
                            v-if="processing"
                            class="mr-2 h-4 w-4"
                        />
                        {{ processing ? 'Creating...' : 'Create Account' }}
                    </Button>
                </CardFooter>
            </Form>
        </Card>
    </AppLayout>
</template>

