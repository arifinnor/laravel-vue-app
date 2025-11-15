<script setup lang="ts">
import TeacherController from '@/actions/App/Http/Controllers/TeacherController';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

interface Teacher {
    id: string;
    teacherNumber: string;
    name: string;
    email: string;
    phone: string | null;
    createdAt: string;
    updatedAt: string;
}

interface Props {
    teacher: Teacher;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teachers',
        href: TeacherController.index().url,
    },
    {
        title: props.teacher.name,
        href: TeacherController.show.url(props.teacher.id),
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="props.teacher.name" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        :title="props.teacher.name"
                        description="Teacher details and activity"
                    />
                </div>
                <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                    <Button
                        as-child
                        class="w-full md:w-auto"
                    >
                        <Link :href="TeacherController.edit.url(props.teacher.id)">
                            Edit teacher
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="secondary"
                        class="w-full md:w-auto"
                    >
                        <Link :href="TeacherController.index().url">
                            Back to teachers
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <dl class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Teacher Number
                        </dt>
                        <dd class="mt-2 text-base font-semibold text-foreground">
                            {{ props.teacher.teacherNumber }}
                        </dd>
                    </div>
                    <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Name
                        </dt>
                        <dd class="mt-2 text-base font-semibold text-foreground">
                            {{ props.teacher.name }}
                        </dd>
                    </div>
                    <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Email
                        </dt>
                        <dd class="mt-2 text-base font-semibold text-foreground">
                            {{ props.teacher.email }}
                        </dd>
                    </div>
                    <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Phone
                        </dt>
                        <dd class="mt-2 text-base font-semibold text-foreground">
                            {{ props.teacher.phone || '—' }}
                        </dd>
                    </div>
                    <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Created at
                        </dt>
                        <dd class="mt-2 text-base font-semibold text-foreground">
                            {{ formatDateTime(props.teacher.createdAt) }}
                        </dd>
                    </div>
                    <div class="rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Last updated
                        </dt>
                        <dd class="mt-2 text-base font-semibold text-foreground">
                            {{ formatDateTime(props.teacher.updatedAt) }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </AppLayout>
</template>

