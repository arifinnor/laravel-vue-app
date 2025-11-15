<script setup lang="ts">
import TeacherController from '@/actions/App/Http/Controllers/TeacherController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface TeacherFormData {
    id: string;
    teacherNumber: string;
    name: string;
    email: string;
    phone: string | null;
}

interface Props {
    teacher: TeacherFormData;
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
    {
        title: 'Edit',
        href: TeacherController.edit.url(props.teacher.id),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${props.teacher.name}`" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        :title="`Edit ${props.teacher.name}`"
                        description="Update the teacher details"
                    />
                </div>
                <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                    <Button
                        as-child
                        variant="secondary"
                        class="w-full md:w-auto"
                    >
                        <Link :href="TeacherController.show.url(props.teacher.id)">
                            View teacher
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        class="w-full md:w-auto"
                    >
                        <Link :href="TeacherController.index().url">
                            Back to teachers
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <Form
                v-bind="TeacherController.update.form(props.teacher.id)"
                class="contents"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="teacher_number">Teacher Number</Label>
                        <Input
                            id="teacher_number"
                            name="teacher_number"
                            type="text"
                            required
                            :default-value="props.teacher.teacherNumber"
                        />
                        <InputError :message="errors.teacher_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            required
                            autocomplete="name"
                            :default-value="props.teacher.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            required
                            autocomplete="email"
                            :default-value="props.teacher.email"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            name="phone"
                            type="tel"
                            autocomplete="tel"
                            :default-value="props.teacher.phone || ''"
                            placeholder="+1234567890"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                </CardContent>

                <CardFooter class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end">
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-neutral-600 dark:text-neutral-300"
                        >
                            Changes saved.
                        </p>
                    </Transition>

                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="update-teacher-button"
                    >
                        Save changes
                    </Button>
                </CardFooter>
            </Form>
        </Card>
    </AppLayout>
</template>

