<script setup lang="ts">
import { Link, Form, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import UserController from '@/actions/App/Http/Controllers/UserController';
import type { User } from './columns';
import { computed } from 'vue';

interface Props {
    user: User;
    onDeleteClick: (
        user: User,
        submit: () => void,
        processing: () => boolean,
    ) => void;
    onRestoreClick?: (
        user: User,
        submit: () => void,
        processing: () => boolean,
    ) => void;
    onForceDeleteClick?: (
        user: User,
        submit: () => void,
        processing: () => boolean,
    ) => void;
}

const props = defineProps<Props>();

const isDeleted = computed(() => !!props.user.deletedAt);

const handleRestore = () => {
    router.post(`/users/${props.user.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['users'] });
        },
    });
};

const handleForceDelete = () => {
    router.delete(`/users/${props.user.id}/force-delete`, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['users'] });
        },
    });
};
</script>

<template>
    <div class="flex items-center justify-end gap-2">
        <template v-if="!isDeleted">
            <Button as-child size="sm" variant="secondary">
                <Link :href="UserController.edit.url(user.id)">
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
                    @click="onDeleteClick(user, () => submit(), () => processing)"
                >
                    Delete
                </Button>
            </Form>
        </template>
        <template v-else>
            <Button
                type="button"
                size="sm"
                variant="secondary"
                :disabled="false"
                @click="onRestoreClick ? onRestoreClick(user, handleRestore, () => false) : handleRestore()"
            >
                Restore
            </Button>
            <Button
                type="button"
                variant="destructive"
                size="sm"
                :disabled="false"
                @click="onForceDeleteClick ? onForceDeleteClick(user, handleForceDelete, () => false) : handleForceDelete()"
            >
                Force Delete
            </Button>
        </template>
    </div>
</template>

