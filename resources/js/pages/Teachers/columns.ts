import { h } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import TeacherController from '@/actions/App/Http/Controllers/TeacherController';
import ActionsCell from './ActionsCell.vue';

export interface Teacher {
    id: string;
    teacherNumber: string;
    name: string;
    email: string;
    phone: string | null;
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
        teacher: Teacher,
        submit: () => void,
        processing: () => boolean,
    ) => void,
    onRestoreClick?: (
        teacher: Teacher,
        submit: () => void,
        processing: () => boolean,
    ) => void,
    onForceDeleteClick?: (
        teacher: Teacher,
        submit: () => void,
        processing: () => boolean,
    ) => void,
): ColumnDef<Teacher>[] => [
    {
        accessorKey: 'teacherNumber',
        header: 'Teacher Number',
        cell: ({ row }) => {
            return h('div', { class: 'font-medium' }, row.getValue('teacherNumber'));
        },
    },
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
            const teacher = row.original;
            return h(
                Link,
                {
                    href: TeacherController.show.url(teacher.id),
                    class: 'text-primary underline-offset-4 transition hover:underline',
                },
                () => row.getValue('email'),
            );
        },
    },
    {
        accessorKey: 'phone',
        header: 'Phone',
        cell: ({ row }) => {
            const phone = row.getValue('phone') as string | null;
            return h('div', { class: 'text-muted-foreground' }, phone || '—');
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
            const teacher = row.original;
            return h(ActionsCell, {
                teacher,
                onDeleteClick,
                onRestoreClick,
                onForceDeleteClick,
            });
        },
    },
];

