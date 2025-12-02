<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import JournalEntryController from '@/actions/App/Http/Controllers/Finance/JournalEntryController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ref, watch, computed, onMounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import DataTable from './DataTable.vue';
import DataTablePagination from './DataTablePagination.vue';
import { createColumns, type JournalEntry } from './columns';

interface CursorPaginated<T> {
    data: T[];
    next_cursor: string | null;
    prev_cursor: string | null;
    path: string;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    journalEntries: CursorPaginated<JournalEntry>;
    perPageOptions: number[];
}

const props = defineProps<Props>();

const searchQuery = ref<string>('');
const statusFilter = ref<string>('all');
const isInitialMount = ref(true);

const updateFilters = useDebounceFn(() => {
    const url = new URL(JournalEntryController.index().url, window.location.origin);
    if (searchQuery.value) {
        url.searchParams.set('search', searchQuery.value);
    } else {
        url.searchParams.delete('search');
    }
    if (statusFilter.value !== 'all') {
        url.searchParams.set('status', statusFilter.value);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('cursor');

    router.visit(url.pathname + url.search, {
        only: ['journalEntries'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch([searchQuery, statusFilter], () => {
    if (!isInitialMount.value) {
        updateFilters();
    }
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';
    statusFilter.value = urlParams.get('status') || 'all';
    isInitialMount.value = false;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Journal Entries',
        href: JournalEntryController.index().url,
    },
];

const isVoidDialogOpen = ref(false);
const pendingVoidEntry = ref<JournalEntry | null>(null);
const isVoiding = ref(false);

const openVoidDialog = (entry: JournalEntry) => {
    pendingVoidEntry.value = entry;
    isVoidDialogOpen.value = true;
};

const confirmVoid = () => {
    if (!pendingVoidEntry.value) {
        return;
    }

    isVoiding.value = true;
    router.post(
        JournalEntryController.void.url(pendingVoidEntry.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isVoidDialogOpen.value = false;
                pendingVoidEntry.value = null;
                isVoiding.value = false;
            },
            onError: () => {
                isVoiding.value = false;
            },
        },
    );
};

const cancelVoid = () => {
    isVoidDialogOpen.value = false;
    pendingVoidEntry.value = null;
};

watch(isVoidDialogOpen, (open) => {
    if (!open) {
        pendingVoidEntry.value = null;
    }
});

const columns = computed(() => createColumns(openVoidDialog));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Journal Entries" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Journal Entries"
                        description="Manage general ledger journal entries"
                    />
                </div>
            </div>
            <div class="space-y-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="w-full max-w-sm">
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search by reference or description..."
                            class="w-full"
                        />
                    </div>
                    <div class="w-full sm:w-auto">
                        <Select v-model="statusFilter">
                            <SelectTrigger class="w-full sm:w-[180px]">
                                <SelectValue placeholder="Filter by status" />
                            </SelectTrigger>
                            <SelectContent class="min-w-[180px]">
                                <SelectItem value="all" class="whitespace-nowrap">All Status</SelectItem>
                                <SelectItem value="DRAFT" class="whitespace-nowrap">Draft</SelectItem>
                                <SelectItem value="POSTED" class="whitespace-nowrap">Posted</SelectItem>
                                <SelectItem value="VOID" class="whitespace-nowrap">Void</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DataTable
                    :data="props.journalEntries.data"
                    :columns="columns"
                />

                <DataTablePagination
                    :next-cursor="props.journalEntries.next_cursor"
                    :prev-cursor="props.journalEntries.prev_cursor"
                    :path="props.journalEntries.path"
                    :per-page="props.journalEntries.per_page"
                    :data-count="props.journalEntries.data.length"
                    :per-page-options="props.perPageOptions"
                />
            </div>
        </div>

        <Dialog v-model:open="isVoidDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Void journal entry</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to void
                        <span class="font-medium text-foreground">
                            {{ pendingVoidEntry?.referenceNumber ?? 'this entry' }}
                        </span>
                        ? This action cannot be undone. The entry will be marked as voided and cannot be modified.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:space-x-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelVoid"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="isVoiding"
                        @click="confirmVoid"
                    >
                        {{ isVoiding ? 'Voiding...' : 'Void Entry' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

