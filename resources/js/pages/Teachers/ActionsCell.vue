<script setup lang="ts">
import { Link, Form, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import TeacherController from '@/actions/App/Http/Controllers/TeacherController';
import type { Teacher } from './columns';
import { computed } from 'vue';

interface Props {
    teacher: Teacher;
    onDeleteClick: (
        teacher: Teacher,
        submit: () => void,
        processing: () => boolean,
    ) => void;
    onRestoreClick?: (
        teacher: Teacher,
        submit: () => void,
        processing: () => boolean,
    ) => void;
    onForceDeleteClick?: (
        teacher: Teacher,
        submit: () => void,
        processing: () => boolean,
    ) => void;
}

const props = defineProps<Props>();

const isDeleted = computed(() => !!props.teacher.deletedAt);

const handleRestore = () => {
    router.post(`/teachers/${props.teacher.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['teachers'] });
        },
    });
};

const handleForceDelete = () => {
    router.delete(`/teachers/${props.teacher.id}/force-delete`, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['teachers'] });
        },
    });
};
</script>

<template>
    <div class="flex items-center justify-end gap-2">
        <template v-if="!isDeleted">
            <Button as-child size="sm" variant="secondary">
                <Link :href="TeacherController.edit.url(teacher.id)">
                    Edit
                </Link>
            </Button>
            <Form
                v-bind="TeacherController.destroy.form(teacher.id)"
                class="inline-flex"
                v-slot="{ processing, submit }"
            >
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    :disabled="processing"
                    @click="onDeleteClick(teacher, () => submit(), () => processing)"
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
                @click="onRestoreClick ? onRestoreClick(teacher, handleRestore, () => false) : handleRestore()"
            >
                Restore
            </Button>
            <Button
                type="button"
                variant="destructive"
                size="sm"
                :disabled="false"
                @click="onForceDeleteClick ? onForceDeleteClick(teacher, handleForceDelete, () => false) : handleForceDelete()"
            >
                Force Delete
            </Button>
        </template>
    </div>
</template>

