<script setup lang="ts">
import StudentController from '@/actions/App/Http/Controllers/StudentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { ref } from 'vue';

interface GuardianFormData {
    name?: string;
    email?: string;
    phone?: string;
    relationship?: string;
    address?: string;
    relationship_type?: string;
    is_primary: boolean;
}

const guardians = ref<GuardianFormData[]>([]);

const addGuardian = () => {
    guardians.value.push({
        name: '',
        email: '',
        phone: '',
        relationship: '',
        address: '',
        relationship_type: '',
        is_primary: false,
    });
};

const removeGuardian = (index: number) => {
    guardians.value.splice(index, 1);
};

const getGuardiansData = () => {
    return guardians.value
        .filter((g) => {
            return g.name && g.name.trim() !== '';
        })
        .map((guardian) => {
            return {
                name: guardian.name!,
                email: guardian.email || null,
                phone: guardian.phone || null,
                relationship: guardian.relationship || null,
                address: guardian.address || null,
                relationship_type: guardian.relationship_type || null,
                is_primary: guardian.is_primary,
            };
        });
};

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const handleSubmit = (e: Event) => {
    const form = e.target as HTMLFormElement;
    const formData = new FormData(form);
    const data: Record<string, any> = {
        name: formData.get('name'),
        student_number: formData.get('student_number'),
        email: formData.get('email') || null,
        phone: formData.get('phone') || null,
    };

    const guardiansData = getGuardiansData();

    // Check for multiple primary guardians
    const primaryCount = guardiansData.filter((g) => g.is_primary).length;
    if (primaryCount > 1) {
        errors.value = { guardians: 'Only one guardian can be set as primary.' };
        processing.value = false;
        return;
    }

    if (guardiansData.length > 0) {
        data.guardians = guardiansData;
    }

    processing.value = true;
    router.post(StudentController.store.url(), data, {
        preserveScroll: true,
        onError: (pageErrors) => {
            errors.value = pageErrors as Record<string, string>;
            processing.value = false;
        },
        onSuccess: () => {
            guardians.value = [];
            processing.value = false;
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Students',
        href: StudentController.index().url,
    },
    {
        title: 'Create student',
        href: StudentController.create().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Create student" />

        <Card>
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading title="Create student" description="Add a new student to the platform" />
                </div>
                <Button as-child variant="secondary" class="w-full md:w-auto">
                    <Link :href="StudentController.index().url"> Cancel </Link>
                </Button>
            </CardHeader>
            <form :action="StudentController.store.url()" method="post" class="contents" @submit.prevent="handleSubmit">
                <CardContent class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="student_number">Student Number</Label>
                        <Input id="student_number" name="student_number" type="text" required
                            placeholder="Student number" />
                        <InputError :message="errors.student_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" type="text" name="name" autocomplete="name" required placeholder="Full name"
                            autofocus />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input id="email" type="email" name="email" autocomplete="email"
                            placeholder="email@example.com" />
                        <InputError :message="errors.email" />
                        <p class="text-sm text-muted-foreground">
                            Optional - students under 17 may not have an email address
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" type="tel" name="phone" autocomplete="tel" placeholder="+1234567890" />
                        <InputError :message="errors.phone" />
                        <p class="text-sm text-muted-foreground">
                            Optional - students under 17 may not have a phone number
                        </p>
                    </div>
                </CardContent>
                <CardFooter class="justify-end">
                    <Button type="submit" :disabled="processing" data-test="create-student-button">
                        <Spinner v-if="processing" />
                        Create student
                    </Button>
                </CardFooter>
            </form>
        </Card>

        <Card class="mt-6">
            <CardHeader class="flex flex-col gap-4 pb-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full">
                    <Heading title="Guardians" description="Add guardians for this student (optional)" />
                </div>
                <Button type="button" variant="outline" class="w-full md:w-auto" @click="addGuardian">
                    Add Guardian
                </Button>
            </CardHeader>

            <CardContent class="space-y-6">
                <div v-if="errors.guardians && typeof errors.guardians === 'string'"
                    class="rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-sm text-destructive">
                    {{ errors.guardians }}
                </div>

                <div v-if="guardians.length === 0" class="text-center py-8 text-muted-foreground">
                    <p>No guardians added yet. Click "Add Guardian" to add one.</p>
                </div>

                <div v-for="(guardian, index) in guardians" :key="index"
                    class="space-y-4 rounded-lg border border-sidebar-border/60 p-4 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <h5 class="font-semibold">Guardian {{ index + 1 }}</h5>
                        <Button type="button" variant="destructive" size="sm" @click="removeGuardian(index)">
                            Remove
                        </Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`guardian_name_${index}`">Name *</Label>
                            <Input :id="`guardian_name_${index}`" v-model="guardian.name" type="text" required
                                autocomplete="name" placeholder="Full name" />
                            <InputError :message="errors[`guardians.${index}.name`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`guardian_email_${index}`">Email address</Label>
                            <Input :id="`guardian_email_${index}`" v-model="guardian.email" type="email"
                                autocomplete="email" placeholder="email@example.com" />
                            <InputError :message="errors[`guardians.${index}.email`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`guardian_phone_${index}`">Phone</Label>
                            <Input :id="`guardian_phone_${index}`" v-model="guardian.phone" type="tel"
                                autocomplete="tel" placeholder="+1234567890" />
                            <InputError :message="errors[`guardians.${index}.phone`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`guardian_relationship_${index}`">Relationship</Label>
                            <Input :id="`guardian_relationship_${index}`" v-model="guardian.relationship" type="text"
                                placeholder="e.g., Mother, Father, Legal Guardian" />
                            <InputError :message="errors[`guardians.${index}.relationship`]" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label :for="`guardian_address_${index}`">Address</Label>
                            <Textarea :id="`guardian_address_${index}`" v-model="guardian.address"
                                placeholder="Full address" :rows="3" />
                            <InputError :message="errors[`guardians.${index}.address`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`guardian_relationship_type_${index}`">Relationship Type (to Student)</Label>
                            <Input :id="`guardian_relationship_type_${index}`" v-model="guardian.relationship_type"
                                type="text" placeholder="e.g., Mother, Father, Legal Guardian" />
                            <InputError :message="errors[`guardians.${index}.relationship_type`]" />
                        </div>

                        <div class="flex items-center gap-2 pt-6">
                            <Checkbox :id="`guardian_is_primary_${index}`" v-model:checked="guardian.is_primary" />
                            <Label :for="`guardian_is_primary_${index}`" class="cursor-pointer">
                                Set as primary guardian
                            </Label>
                            <InputError :message="errors[`guardians.${index}.is_primary`]" />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
