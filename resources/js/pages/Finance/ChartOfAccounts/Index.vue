<script setup lang="ts">
import FinanceController from '@/actions/App/Http/Controllers/Finance/FinanceController';
import ChartOfAccountController from '@/actions/App/Http/Controllers/Finance/ChartOfAccountController';
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
import TreeView from './TreeView.vue';
import { createColumns, type ChartOfAccount } from './columns';
import { LayoutList, TreePine } from 'lucide-vue-next';

interface CursorPaginated<T> {
    data: T[];
    next_cursor: string | null;
    prev_cursor: string | null;
    path: string;
    per_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

type TreeNode = ChartOfAccount & {
    children?: TreeNode[];
};

interface Props {
    accounts: CursorPaginated<ChartOfAccount>;
    accountsTree: TreeNode[];
    filters: {
        search?: string | null;
        account_type?: string | null;
        category_id?: string | null;
        is_posting?: boolean | null;
        is_cash?: boolean | null;
        is_active?: boolean | null;
        with_trashed?: string;
        view?: string;
        per_page?: number;
    };
    perPageOptions: number[];
    accountTypes: string[];
    normalBalances: string[];
    categories: Array<{ id: string; name: string }>;
}

const props = defineProps<Props>();

const ALL_FILTER = '__all__';

const searchQuery = ref<string>(props.filters.search || '');
const accountTypeFilter = ref<string>(props.filters.account_type || ALL_FILTER);
const categoryFilter = ref<string>(props.filters.category_id ? String(props.filters.category_id) : ALL_FILTER);
const isPostingFilter = ref<string>(props.filters.is_posting !== null ? String(props.filters.is_posting) : ALL_FILTER);
const isCashFilter = ref<string>(props.filters.is_cash !== null ? String(props.filters.is_cash) : ALL_FILTER);
const isActiveFilter = ref<string>(props.filters.is_active !== null ? String(props.filters.is_active) : ALL_FILTER);
const withTrashedFilter = ref<string>(props.filters.with_trashed || 'none');
const viewMode = ref<string>(props.filters.view || 'tree');
const isInitialMount = ref(true);

const updateFilters = useDebounceFn(() => {
    const url = new URL(ChartOfAccountController.index().url, window.location.origin);
    
    if (searchQuery.value) {
        url.searchParams.set('search', searchQuery.value);
    } else {
        url.searchParams.delete('search');
    }
    
    if (accountTypeFilter.value && accountTypeFilter.value !== ALL_FILTER) {
        url.searchParams.set('account_type', accountTypeFilter.value);
    } else {
        url.searchParams.delete('account_type');
    }
    
    if (categoryFilter.value && categoryFilter.value !== ALL_FILTER) {
        url.searchParams.set('category_id', categoryFilter.value);
    } else {
        url.searchParams.delete('category_id');
    }
    
    if (isPostingFilter.value !== ALL_FILTER && isPostingFilter.value !== '') {
        url.searchParams.set('is_posting', isPostingFilter.value);
    } else {
        url.searchParams.delete('is_posting');
    }
    
    if (isCashFilter.value !== ALL_FILTER && isCashFilter.value !== '') {
        url.searchParams.set('is_cash', isCashFilter.value);
    } else {
        url.searchParams.delete('is_cash');
    }
    
    if (isActiveFilter.value !== ALL_FILTER && isActiveFilter.value !== '') {
        url.searchParams.set('is_active', isActiveFilter.value);
    } else {
        url.searchParams.delete('is_active');
    }
    
    if (withTrashedFilter.value !== 'none') {
        url.searchParams.set('with_trashed', withTrashedFilter.value);
    } else {
        url.searchParams.delete('with_trashed');
    }
    
    if (viewMode.value) {
        url.searchParams.set('view', viewMode.value);
    } else {
        url.searchParams.delete('view');
    }
    
    url.searchParams.delete('cursor');

    router.visit(url.pathname + url.search, {
        only: ['accounts', 'accountsTree', 'filters'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch([searchQuery, accountTypeFilter, categoryFilter, isPostingFilter, isCashFilter, isActiveFilter, withTrashedFilter, viewMode], () => {
    if (!isInitialMount.value) {
        updateFilters();
    }
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';
    accountTypeFilter.value = urlParams.get('account_type') || ALL_FILTER;
    categoryFilter.value = urlParams.get('category_id') || ALL_FILTER;
    isPostingFilter.value = urlParams.get('is_posting') || ALL_FILTER;
    isCashFilter.value = urlParams.get('is_cash') || ALL_FILTER;
    isActiveFilter.value = urlParams.get('is_active') || ALL_FILTER;
    withTrashedFilter.value = urlParams.get('with_trashed') || 'none';
    viewMode.value = urlParams.get('view') || 'tree';
    isInitialMount.value = false;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance',
        href: FinanceController.index().url,
    },
    {
        title: 'Chart of Accounts',
        href: ChartOfAccountController.index().url,
    },
];

const isDeleteDialogOpen = ref(false);
const isRestoreDialogOpen = ref(false);
const isForceDeleteDialogOpen = ref(false);
const pendingDeleteAccount = ref<ChartOfAccount | null>(null);
const pendingDeleteAction = ref<{
    submit: () => void;
    processing: () => boolean;
} | null>(null);
const pendingRestoreAction = ref<{
    submit: () => void;
    processing: () => boolean;
} | null>(null);
const pendingForceDeleteAction = ref<{
    submit: () => void;
    processing: () => boolean;
} | null>(null);

const openDeleteDialog = (
    account: ChartOfAccount,
    submit: () => void,
    processing: () => boolean,
) => {
    pendingDeleteAccount.value = account;
    pendingDeleteAction.value = {
        submit,
        processing,
    };
    isDeleteDialogOpen.value = true;
};

const openRestoreDialog = (
    account: ChartOfAccount,
    submit: () => void,
    processing: () => boolean,
) => {
    pendingDeleteAccount.value = account;
    pendingRestoreAction.value = {
        submit,
        processing,
    };
    isRestoreDialogOpen.value = true;
};

const openForceDeleteDialog = (
    account: ChartOfAccount,
    submit: () => void,
    processing: () => boolean,
) => {
    pendingDeleteAccount.value = account;
    pendingForceDeleteAction.value = {
        submit,
        processing,
    };
    isForceDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!pendingDeleteAction.value) {
        return;
    }

    pendingDeleteAction.value.submit();
    isDeleteDialogOpen.value = false;
};

const confirmRestore = () => {
    if (!pendingRestoreAction.value) {
        return;
    }

    pendingRestoreAction.value.submit();
    isRestoreDialogOpen.value = false;
};

const confirmForceDelete = () => {
    if (!pendingForceDeleteAction.value) {
        return;
    }

    pendingForceDeleteAction.value.submit();
    isForceDeleteDialogOpen.value = false;
};

const cancelDelete = () => {
    isDeleteDialogOpen.value = false;
};

const cancelRestore = () => {
    isRestoreDialogOpen.value = false;
};

const cancelForceDelete = () => {
    isForceDeleteDialogOpen.value = false;
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        pendingDeleteAccount.value = null;
        pendingDeleteAction.value = null;
    }
});

watch(isRestoreDialogOpen, (open) => {
    if (!open) {
        pendingDeleteAccount.value = null;
        pendingRestoreAction.value = null;
    }
});

watch(isForceDeleteDialogOpen, (open) => {
    if (!open) {
        pendingDeleteAccount.value = null;
        pendingForceDeleteAction.value = null;
    }
});

const columns = computed(() => createColumns(openDeleteDialog, openRestoreDialog, openForceDeleteDialog));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Chart of Accounts" />

        <div>
            <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Chart of Accounts"
                        description="Manage your accounting structure and hierarchy"
                    />
                </div>
                <Button as-child class="w-full md:w-auto">
                    <Link :href="ChartOfAccountController.create().url">
                        Create Account
                    </Link>
                </Button>
            </div>
            <div class="space-y-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by code, name, or description..."
                                class="w-full"
                            />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Select v-model="viewMode">
                                <SelectTrigger class="w-full sm:w-[140px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tree">
                                        <div class="flex items-center gap-2">
                                            <TreePine class="h-4 w-4" />
                                            Tree View
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="list">
                                        <div class="flex items-center gap-2">
                                            <LayoutList class="h-4 w-4" />
                                            List View
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="accountTypeFilter">
                                <SelectTrigger class="w-full sm:w-[160px]">
                                    <SelectValue placeholder="Account Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_FILTER">All Types</SelectItem>
                                    <SelectItem
                                        v-for="type in accountTypes"
                                        :key="type"
                                        :value="type"
                                    >
                                        {{ type }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="categoryFilter">
                                <SelectTrigger class="w-full sm:w-[180px]">
                                    <SelectValue placeholder="Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_FILTER">All Categories</SelectItem>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="String(category.id)"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="isPostingFilter">
                                <SelectTrigger class="w-full sm:w-[140px]">
                                    <SelectValue placeholder="Posting" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_FILTER">All</SelectItem>
                                    <SelectItem value="true">Posting Only</SelectItem>
                                    <SelectItem value="false">Headers Only</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="isCashFilter">
                                <SelectTrigger class="w-full sm:w-[120px]">
                                    <SelectValue placeholder="Cash" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_FILTER">All</SelectItem>
                                    <SelectItem value="true">Cash Only</SelectItem>
                                    <SelectItem value="false">Non-Cash</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="isActiveFilter">
                                <SelectTrigger class="w-full sm:w-[120px]">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_FILTER">All</SelectItem>
                                    <SelectItem value="true">Active</SelectItem>
                                    <SelectItem value="false">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="withTrashedFilter">
                                <SelectTrigger class="w-full sm:w-[140px]">
                                    <SelectValue placeholder="Deleted" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Active</SelectItem>
                                    <SelectItem value="only">Deleted Only</SelectItem>
                                    <SelectItem value="all">All</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <TreeView
                    v-if="viewMode === 'tree'"
                    :nodes="accountsTree"
                />

                <template v-else>
                    <DataTable
                        :data="props.accounts.data"
                        :columns="columns"
                    />

                    <DataTablePagination
                        :next-cursor="props.accounts.next_cursor"
                        :prev-cursor="props.accounts.prev_cursor"
                        :path="props.accounts.path"
                        :per-page="props.accounts.per_page"
                        :data-count="props.accounts.data.length"
                        :per-page-options="props.perPageOptions"
                    />
                </template>
            </div>
        </div>

        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete Account</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete
                        <span class="font-medium text-foreground">
                            {{ pendingDeleteAccount?.name ?? 'this account' }}
                        </span>
                        ? This action can be undone by restoring the account.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:space-x-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelDelete"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="pendingDeleteAction?.processing()"
                        @click="confirmDelete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isRestoreDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Restore Account</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to restore
                        <span class="font-medium text-foreground">
                            {{ pendingDeleteAccount?.name ?? 'this account' }}
                        </span>
                        ? The account will be available again.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:space-x-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelRestore"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="default"
                        :disabled="pendingRestoreAction?.processing()"
                        @click="confirmRestore"
                    >
                        Restore
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isForceDeleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Permanently Delete Account</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to permanently delete
                        <span class="font-medium text-foreground">
                            {{ pendingDeleteAccount?.name ?? 'this account' }}
                        </span>
                        ? This action cannot be undone and all data associated with this account will be permanently removed.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:space-x-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelForceDelete"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="pendingForceDeleteAction?.processing()"
                        @click="confirmForceDelete"
                    >
                        Permanently Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

