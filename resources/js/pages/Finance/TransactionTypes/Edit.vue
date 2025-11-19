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

interface TransactionType {
    id: string;
    is_system: boolean;
    code: string;
    name: string;
    category: string;
    is_active: boolean;
}

interface Props {
    transactionType: TransactionType;
    transactionCategories: string[];
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

            <Form
                v-bind="TransactionTypeController.update.form(props.transactionType.id)"
                class="contents"
                :options="{ preserveScroll: true }"
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
                            :default-value="props.transactionType.code"
                            :disabled="true"
                        />
                        <InputError :message="errors.code" />
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
                            name="name"
                            required
                            :default-value="props.transactionType.name"
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
                            :default-value="props.transactionType.category"
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
                        {{ processing ? 'Updating...' : 'Update Transaction Type' }}
                    </Button>
                </CardFooter>
            </Form>
        </Card>
    </AppLayout>
</template>

