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
import { Spinner } from '@/components/ui/spinner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teachers',
        href: TeacherController.index().url,
    },
    {
        title: 'Create teacher',
        href: TeacherController.create().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create teacher" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        title="Create teacher"
                        description="Add a new teacher to the platform"
                    />
                </div>
                <Button as-child variant="secondary" class="w-full md:w-auto">
                    <Link :href="TeacherController.index().url"> Cancel </Link>
                </Button>
            </CardHeader>
            <Form
                v-bind="TeacherController.store.form()"
                class="contents"
                :reset-on-success="[
                    'teacher_number',
                    'name',
                    'email',
                    'phone',
                ]"
                v-slot="{ errors, processing }"
            >
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="teacher_number">Teacher Number</Label>
                        <Input
                            id="teacher_number"
                            type="text"
                            name="teacher_number"
                            required
                            placeholder="TCH-00001"
                            autofocus
                        />
                        <InputError :message="errors.teacher_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            name="name"
                            autocomplete="name"
                            required
                            placeholder="Full name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            required
                            placeholder="email@example.com"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            type="tel"
                            name="phone"
                            autocomplete="tel"
                            placeholder="+1234567890"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                </CardContent>
                <CardFooter class="justify-end">
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="create-teacher-button"
                    >
                        <Spinner v-if="processing" />
                        Create teacher
                    </Button>
                </CardFooter>
            </Form>
        </Card>
    </AppLayout>
</template>

