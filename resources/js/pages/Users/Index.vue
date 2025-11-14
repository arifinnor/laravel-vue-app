<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { ref, watch } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    emailVerifiedAt: string | null;
    createdAt: string;
    updatedAt: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

interface Props {
    users: Paginated<User>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: UserController.index().url,
    },
];

const formatDateTime = (value: string) =>
    new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const isDeleteDialogOpen = ref(false);
const pendingDeleteUser = ref<User | null>(null);
const pendingDeleteAction = ref<{
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

const confirmDelete = () => {
    if (!pendingDeleteAction.value) {
        return;
    }

    pendingDeleteAction.value.submit();
    isDeleteDialogOpen.value = false;
};

const cancelDelete = () => {
    isDeleteDialogOpen.value = false;
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        pendingDeleteUser.value = null;
        pendingDeleteAction.value = null;
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <Card>
                <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
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
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="overflow-hidden rounded-lg border border-sidebar-border/60 shadow-sm dark:border-sidebar-border">
                        <table class="min-w-full divide-y divide-sidebar-border/60 dark:divide-sidebar-border">
                            <thead class="bg-muted/20">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                    <th scope="col" class="px-4 py-3">Name</th>
                                    <th scope="col" class="px-4 py-3">Email</th>
                                    <th scope="col" class="px-4 py-3">
                                        Email status
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        Created
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        Updated
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sidebar-border/60 text-sm text-foreground dark:divide-sidebar-border">
                                <tr
                                    v-for="user in props.users.data"
                                    :key="user.id"
                                    class="bg-background transition hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3 font-medium">
                                        {{ user.name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="UserController.show.url(user.id)"
                                            class="text-primary underline-offset-4 transition hover:underline"
                                        >
                                            {{ user.email }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                                user.emailVerifiedAt
                                                    ? 'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-100'
                                                    : 'bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-100',
                                            ]"
                                        >
                                            {{
                                                user.emailVerifiedAt
                                                    ? 'Verified'
                                                    : 'Pending'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ formatDateTime(user.createdAt) }}
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ formatDateTime(user.updatedAt) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                as-child
                                                size="sm"
                                                variant="secondary"
                                            >
                                                <Link
                                                    :href="
                                                        UserController.edit.url(
                                                            user.id,
                                                        )
                                                    "
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Form
                                                v-bind="UserController.destroy.form(user.id)"
                                                class="inline-flex"
                                                v-slot="{ processing, submit }"
                                            >
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    size="sm"
                                                    :disabled="processing"
                                                    @click="
                                                        openDeleteDialog(
                                                            user,
                                                            () => submit(),
                                                            () => processing,
                                                        )
                                                    "
                                                >
                                                    Delete
                                                </Button>
                                            </Form>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="props.users.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-6 text-center text-sm text-muted-foreground"
                                    >
                                        No users found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <nav
                        v-if="props.users.links.length > 1"
                        class="flex items-center justify-between gap-2"
                        aria-label="Pagination"
                    >
                        <span class="text-sm text-muted-foreground">
                            Showing
                            {{ props.users.from ?? 0 }}-
                            {{ props.users.to ?? 0 }} of
                            {{ props.users.total }}
                        </span>
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="link in props.users.links"
                                :key="link.label"
                                :href="link.url ?? ''"
                                preserve-scroll
                                :class="[
                                    'rounded-md px-3 py-1 text-sm transition',
                                    !link.url
                                        ? 'pointer-events-none text-muted-foreground/60'
                                        : 'hover:bg-muted/70',
                                    link.active
                                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                        : 'text-foreground',
                                ]"
                            >
                                <span v-html="link.label" />
                            </Link>
                        </div>
                    </nav>
            </CardContent>
        </Card>

        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete user</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete
                        <span class="font-medium text-foreground">
                            {{ pendingDeleteUser?.name ?? 'this user' }}
                        </span>
                        ?
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
    </AppLayout>
</template>

