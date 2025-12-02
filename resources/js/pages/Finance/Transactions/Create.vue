<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import TransactionController from '@/actions/App/Http/Controllers/Finance/TransactionController';
import TransactionTypeCombobox from '@/components/Finance/TransactionTypeCombobox.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

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
import { Textarea } from '@/components/ui/textarea';
import { Lock } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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
        is_cash?: boolean;
    } | null;
}

interface TransactionType {
    id: string;
    code: string;
    name: string;
    category: string;
    accounts: TransactionAccount[];
}

interface CashAccount {
    id: string;
    code: string;
    name: string;
    display: string;
}

interface Student {
    id: string;
    name: string;
    student_number: string;
}

interface Props {
    transactionTypes: TransactionType[];
    cashAccounts: CashAccount[];
    students: Student[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Transactions',
        href: TransactionController.index().url,
    },
    {
        title: 'Create Transaction',
        href: TransactionController.create().url,
    },
];

const selectedTransactionTypeId = ref<string | null>(null);
const cashAccountLocked = ref(false);
const showStudentField = ref(false);
const cashAccountLabel = ref('Cash Account');
const autoDetectedCashAccountId = ref<string | null>(null);

const selectedTransactionType = computed(() => {
    if (!selectedTransactionTypeId.value) {
        return null;
    }
    return props.transactionTypes.find((type) => type.id === selectedTransactionTypeId.value) || null;
});

const formatAmountInput = (value: string): string => {
    // Remove all non-digit characters
    const digits = value.replace(/\D/g, '');
    if (!digits) {
        return '';
    }
    // Add thousand separators (dots for IDR)
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

const parseAmountInput = (value: string): number => {
    // Remove all non-digit characters and parse as float
    const digits = value.replace(/\D/g, '');
    return digits ? parseFloat(digits) : 0;
};

const amountInput = ref<HTMLInputElement | null>(null);
const amountFormatted = ref('');
const amountRaw = ref<number>(0);

const handleAmountInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const oldCursor = input.selectionStart || 0;
    const oldValue = input.value;

    // Count digits before cursor
    let digitsBeforeCursor = 0;
    for (let i = 0; i < oldCursor; i++) {
        if (/\d/.test(oldValue[i])) digitsBeforeCursor++;
    }

    // Get raw digits
    const digits = oldValue.replace(/\D/g, '');
    
    // Parse to get raw numeric value
    amountRaw.value = digits ? parseFloat(digits) : 0;

    // Format the input value with thousand separators
    const formatted = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    
    // Update reactive state
    amountFormatted.value = formatted;
    
    // Manually update input value to ensure sync
    input.value = formatted;

    // Calculate new cursor position
    let newCursor = 0;
    if (digitsBeforeCursor > 0) {
        let digitsSeen = 0;
        for (let i = 0; i < formatted.length; i++) {
            if (/\d/.test(formatted[i])) digitsSeen++;
            newCursor = i + 1;
            if (digitsSeen === digitsBeforeCursor) break;
        }
    }
    
    // Restore cursor
    input.setSelectionRange(newCursor, newCursor);
};

watch(selectedTransactionTypeId, (newTypeId) => {
    if (!newTypeId) {
        cashAccountLocked.value = false;
        showStudentField.value = false;
        cashAccountLabel.value = 'Cash Account';
        autoDetectedCashAccountId.value = null;
        return;
    }

    const type = selectedTransactionType.value;
    if (!type) {
        return;
    }

    // Auto-detect cash account
    const cashAccount = type.accounts.find(
        (account) => account.chart_of_account?.is_cash === true,
    );

    if (cashAccount && cashAccount.chart_of_account_id) {
        // Auto-map found
        cashAccountLocked.value = true;
        autoDetectedCashAccountId.value = cashAccount.chart_of_account_id;
    } else {
        // Not found, enable manual selection
        cashAccountLocked.value = false;
        autoDetectedCashAccountId.value = null;
    }

    // Auto-detect student field visibility
    const codeUpper = type.code.toUpperCase();
    showStudentField.value = codeUpper.includes('TUITION') || codeUpper.includes('STUDENT');

    // Dynamic labels based on category
    if (type.category === 'INCOME') {
        cashAccountLabel.value = 'Deposit To';
    } else if (type.category === 'EXPENSE') {
        cashAccountLabel.value = 'Pay From';
    } else {
        cashAccountLabel.value = 'Cash Account';
    }
});

const today = new Date().toISOString().split('T')[0];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Transaction" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Create Transaction"
                        description="Record a new financial transaction"
                    />
                </div>
                <Button
                    as-child
                    variant="secondary"
                    class="w-full md:w-auto"
                >
                    <Link :href="TransactionController.index().url">
                        Cancel
                    </Link>
                </Button>
            </CardHeader>
            <Form
                v-bind="TransactionController.store.form()"
                class="contents"
                v-slot="{ errors, processing }"
            >
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="transaction_date">
                            Transaction Date
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="transaction_date"
                            type="date"
                            name="date"
                            :value="today"
                            required
                        />
                        <InputError :message="errors.date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="transaction_type_id">
                            Transaction Type
                            <span class="text-destructive">*</span>
                        </Label>
                        <TransactionTypeCombobox
                            :model-value="selectedTransactionTypeId"
                            :transaction-types="props.transactionTypes"
                            placeholder="Select transaction type..."
                            @update:model-value="selectedTransactionTypeId = $event"
                        />
                        <input
                            type="hidden"
                            name="type_code"
                            :value="selectedTransactionType?.code || ''"
                        />
                        <InputError :message="errors.type_code" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="cash_account_id">
                            {{ cashAccountLabel }}
                            <span class="text-destructive">*</span>
                            <Badge
                                v-if="cashAccountLocked"
                                variant="secondary"
                                class="ml-2"
                            >
                                <Lock class="mr-1 h-3 w-3" />
                                Auto-mapped
                            </Badge>
                        </Label>
                        <input
                            v-if="cashAccountLocked && autoDetectedCashAccountId"
                            type="hidden"
                            name="cash_account_id"
                            :value="autoDetectedCashAccountId"
                        />
                        <Select
                            v-if="!cashAccountLocked"
                            name="cash_account_id"
                            required
                        >
                            <SelectTrigger
                                id="cash_account_id"
                            >
                                <SelectValue placeholder="Select cash account..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="account in props.cashAccounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.display }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div
                            v-else
                            class="flex h-10 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm text-muted-foreground"
                        >
                            {{ props.cashAccounts.find(a => a.id === autoDetectedCashAccountId)?.display || 'Auto-mapped account' }}
                        </div>
                        <InputError :message="errors.cash_account_id" />
                        <p
                            v-if="cashAccountLocked"
                            class="text-xs text-muted-foreground"
                        >
                            Cash account is automatically mapped from the selected transaction type.
                        </p>
                    </div>

                    <div
                        v-if="showStudentField"
                        class="grid gap-2"
                    >
                        <Label for="student_id">
                            Student
                        </Label>
                        <Select
                            name="student_id"
                        >
                            <SelectTrigger id="student_id">
                                <SelectValue placeholder="Select student..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="student in props.students"
                                    :key="student.id"
                                    :value="student.id"
                                >
                                    {{ student.name }} ({{ student.student_number }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.student_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="amount">
                            Amount
                            <span class="text-destructive">*</span>
                        </Label>
                        <div class="relative">
                            <input
                                type="hidden"
                                name="amount"
                                :value="amountRaw || ''"
                            />
                            <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-semibold text-muted-foreground">
                                Rp
                            </div>
                            <input
                                id="amount"
                                ref="amountInput"
                                type="text"
                                :value="amountFormatted"
                                required
                                placeholder="0"
                                inputmode="numeric"
                                :class="cn(
                                    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 shadow-xs transition-[color,box-shadow] outline-none',
                                    'placeholder:text-muted-foreground',
                                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                                    'pl-10 text-lg font-semibold',
                                    errors.amount && 'border-destructive focus-visible:ring-destructive/20'
                                )"
                                @input="handleAmountInput"
                            />
                        </div>
                        <InputError :message="errors.amount" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">
                            Description
                        </Label>
                        <Textarea
                            id="description"
                            name="description"
                            placeholder="Transaction description..."
                            :rows="3"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="external_ref">
                            External Reference
                        </Label>
                        <Input
                            id="external_ref"
                            type="text"
                            name="external_ref"
                            placeholder="Optional external reference number"
                            maxlength="255"
                        />
                        <InputError :message="errors.external_ref" />
                        <p class="text-xs text-muted-foreground">
                            Optional. If not provided, a reference number will be auto-generated.
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
                        {{ processing ? 'Creating...' : 'Create Transaction' }}
                    </Button>
                </CardFooter>
            </Form>
        </Card>
    </AppLayout>
</template>

