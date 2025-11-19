<script setup lang="ts">
import TransactionTypeController from '@/actions/App/Http/Controllers/Finance/TransactionTypeController';
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
import { Spinner } from '@/components/ui/spinner';

interface Props {
    transactionCategories: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Journal Configuration',
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
            <Form
                v-bind="TransactionTypeController.store.form()"
                class="contents"
                :reset-on-success="['code', 'name', 'category']"
                v-slot="{ errors, processing }"
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
                            placeholder="e.g., CUST_TRX_001"
                            autofocus
                        />
                        <InputError :message="errors.code" />
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
                            name="name"
                            required
                            placeholder="e.g., Purchase Snacks"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">
                            Category
                            <span class="text-destructive">*</span>
                        </Label>
                        <Select
                            name="category"
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
                        <InputError :message="errors.category" />
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
                        {{ processing ? 'Creating...' : 'Create Transaction Type' }}
                    </Button>
                </CardFooter>
            </Form>
        </Card>
    </AppLayout>
</template>

