import { h } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import UserController from '@/actions/App/Http/Controllers/UserController';
import ActionsCell from './ActionsCell.vue';

export interface User {
    id: number;
    name: string;
    email: string;
    emailVerifiedAt: string | null;
    createdAt: string;
    updatedAt: string;
    deletedAt: string | null;
}

const formatDateTime = (value: string): string =>
    new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

export const createColumns = (
    onDeleteClick: (
        user: User,
        submit: () => void,
        processing: () => boolean,
    ) => void,
    onRestoreClick?: (
        user: User,
        submit: () => void,
        processing: () => boolean,
    ) => void,
    onForceDeleteClick?: (
        user: User,
        submit: () => void,
        processing: () => boolean,
    ) => void,
): ColumnDef<User>[] => [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            return h('div', { class: 'font-medium' }, row.getValue('name'));
        },
    },
    {
        accessorKey: 'email',
        header: 'Email',
        cell: ({ row }) => {
            const user = row.original;
            return h(
                Link,
                {
                    href: UserController.show.url(user.id),
                    class: 'text-primary underline-offset-4 transition hover:underline',
                },
                () => row.getValue('email'),
            );
        },
    },
    {
        accessorKey: 'emailVerifiedAt',
        header: 'Email status',
        cell: ({ row }) => {
            const isVerified = !!row.getValue('emailVerifiedAt');
            return h(
                Badge,
                {
                    class: [
                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                        isVerified
                            ? 'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-100'
                            : 'bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-100',
                    ],
                },
                () => (isVerified ? 'Verified' : 'Pending'),
            );
        },
    },
    {
        accessorKey: 'deletedAt',
        header: 'Status',
        cell: ({ row }) => {
            const deletedAt = row.getValue('deletedAt') as string | null;
            if (deletedAt) {
                return h(
                    Badge,
                    {
                        class: 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-500/10 text-red-700 dark:bg-red-500/20 dark:text-red-100',
                    },
                    () => 'Deleted',
                );
            }
            return h(
                Badge,
                {
                    class: 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-100',
                },
                () => 'Active',
            );
        },
    },
    {
        accessorKey: 'createdAt',
        header: 'Created',
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'text-muted-foreground' },
                formatDateTime(row.getValue('createdAt')),
            );
        },
    },
    {
        accessorKey: 'updatedAt',
        header: 'Updated',
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'text-muted-foreground' },
                formatDateTime(row.getValue('updatedAt')),
            );
        },
    },
    {
        id: 'actions',
        header: () => h('div', { class: 'text-right' }, 'Actions'),
        cell: ({ row }) => {
            const user = row.original;
            return h(ActionsCell, {
                user,
                onDeleteClick,
                onRestoreClick,
                onForceDeleteClick,
            });
        },
    },
];

