<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
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
import { createColumns, type User } from './columns';

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
    users: CursorPaginated<User>;
    perPageOptions: number[];
}

const props = defineProps<Props>();

const searchQuery = ref<string>('');
const withTrashedFilter = ref<string>('none');
const isInitialMount = ref(true);

const updateFilters = useDebounceFn(() => {
    const url = new URL(UserController.index().url, window.location.origin);
    if (searchQuery.value) {
        url.searchParams.set('search', searchQuery.value);
    } else {
        url.searchParams.delete('search');
    }
    if (withTrashedFilter.value !== 'none') {
        url.searchParams.set('with_trashed', withTrashedFilter.value);
    } else {
        url.searchParams.delete('with_trashed');
    }
    url.searchParams.delete('cursor');

    router.visit(url.pathname + url.search, {
        only: ['users'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch([searchQuery, withTrashedFilter], () => {
    if (!isInitialMount.value) {
        updateFilters();
    }
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || '';
    withTrashedFilter.value = urlParams.get('with_trashed') || 'none';
    isInitialMount.value = false;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: UserController.index().url,
    },
];

const isDeleteDialogOpen = ref(false);
const isRestoreDialogOpen = ref(false);
const isForceDeleteDialogOpen = ref(false);
const pendingDeleteUser = ref<User | null>(null);
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
    user: User,
    submit: () => void,
    processing: () => boolean,
) => {
    pendingDeleteUser.value = user;
    pendingDeleteAction.value = {
        submit,
        processing,
    };
    isDeleteDialogOpen.value = true;
};

const openRestoreDialog = (
    user: User,
    submit: () => void,
    processing: () => boolean,
) => {
    pendingDeleteUser.value = user;
    pendingRestoreAction.value = {
        submit,
        processing,
    };
    isRestoreDialogOpen.value = true;
};

const openForceDeleteDialog = (
    user: User,
    submit: () => void,
    processing: () => boolean,
) => {
    pendingDeleteUser.value = user;
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
        pendingDeleteUser.value = null;
        pendingDeleteAction.value = null;
    }
});

watch(isRestoreDialogOpen, (open) => {
    if (!open) {
        pendingDeleteUser.value = null;
        pendingRestoreAction.value = null;
    }
});

watch(isForceDeleteDialogOpen, (open) => {
    if (!open) {
        pendingDeleteUser.value = null;
        pendingForceDeleteAction.value = null;
    }
});

const columns = computed(() => createColumns(openDeleteDialog, openRestoreDialog, openForceDeleteDialog));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <div>
                <div class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                    <div class="w-full">
                        <Heading
                            title="Users"
                            description="Manage users across the platform"
                        />
                    </div>
                    <Button as-child class="w-full md:w-auto">
                        <Link :href="UserController.create().url">
                            Create user
                        </Link>
                    </Button>
                </div>
                <div class="space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by name or email..."
                                class="w-full"
                            />
                        </div>
                        <div class="w-full sm:w-auto">
                            <Select v-model="withTrashedFilter">
                                <SelectTrigger class="w-full sm:w-[180px]">
                                    <SelectValue placeholder="Filter users" />
                                </SelectTrigger>
                                <SelectContent class="min-w-[180px]">
                                    <SelectItem value="none" class="whitespace-nowrap">Active Users</SelectItem>
                                    <SelectItem value="only" class="whitespace-nowrap">Deleted Users</SelectItem>
                                    <SelectItem value="all" class="whitespace-nowrap">All Users</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DataTable
                        :data="props.users.data"
                        :columns="columns"
                    />

                    <DataTablePagination
                        :next-cursor="props.users.next_cursor"
                        :prev-cursor="props.users.prev_cursor"
                        :path="props.users.path"
                        :per-page="props.users.per_page"
                        :data-count="props.users.data.length"
                        :per-page-options="props.perPageOptions"
                    />
            </div>
        </div>

        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete user</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete
                        <span class="font-medium text-foreground">
                            {{ pendingDeleteUser?.name ?? 'this user' }}
                        </span>
                        ? This action can be undone by restoring the user.
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
                    <DialogTitle>Restore user</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to restore
                        <span class="font-medium text-foreground">
                            {{ pendingDeleteUser?.name ?? 'this user' }}
                        </span>
                        ? The user will be able to access the system again.
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
                    <DialogTitle>Permanently delete user</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to permanently delete
                        <span class="font-medium text-foreground">
                            {{ pendingDeleteUser?.name ?? 'this user' }}
                        </span>
                        ? This action cannot be undone and all data associated with this user will be permanently removed.
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

