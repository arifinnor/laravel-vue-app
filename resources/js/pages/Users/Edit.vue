<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface UserFormData {
    id: number;
    name: string;
    email: string;
}

interface Props {
    user: UserFormData;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: UserController.index().url,
    },
    {
        title: props.user.name,
        href: UserController.show.url(props.user.id),
    },
    {
        title: 'Edit',
        href: UserController.edit.url(props.user.id),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${props.user.name}`" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading
                        :title="`Edit ${props.user.name}`"
                        description="Update the user details"
                    />
                </div>
                <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row">
                    <Button
                        as-child
                        variant="secondary"
                        class="w-full md:w-auto"
                    >
                        <Link :href="UserController.show.url(props.user.id)">
                            View user
                        </Link>
                    </Button>
                    <Button
                        as-child
                        variant="ghost"
                        class="w-full md:w-auto"
                    >
                        <Link :href="UserController.index().url">
                            Back to users
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <Form
                v-bind="UserController.update.form(props.user.id)"
                class="contents"
                :reset-on-success="['password', 'password_confirmation']"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            required
                            autocomplete="name"
                            :default-value="props.user.name"
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
                            :default-value="props.user.email"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Leave blank to keep the current password"
                        />
                        <InputError :message="errors.password" />
                        <p class="text-xs text-muted-foreground">
                            Leave blank to keep the current password.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirm password</Label
                        >
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Re-enter new password"
                        />
                        <InputError
                            :message="errors.password_confirmation"
                        />
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
                        data-test="update-user-button"
                    >
                        Save changes
                    </Button>
                </CardFooter>
            </Form>
        </Card>
    </AppLayout>
</template>

