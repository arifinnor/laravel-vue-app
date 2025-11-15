<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Props {
    nextCursor: string | null;
    prevCursor: string | null;
    hasNextPage?: boolean;
    hasPrevPage?: boolean;
    path: string;
    perPage: number;
    dataCount: number;
    perPageOptions: number[];
}

const props = withDefaults(defineProps<Props>(), {
    hasNextPage: false,
    hasPrevPage: false,
});

const selectedPerPage = ref(String(props.perPage));
const isUpdatingFromProps = ref(false);

watch(() => props.perPage, (newValue) => {
    isUpdatingFromProps.value = true;
    selectedPerPage.value = String(newValue);
    isUpdatingFromProps.value = false;
});

watch(selectedPerPage, (newValue) => {
    if (isUpdatingFromProps.value) {
        return;
    }
    const url = new URL(props.path, window.location.origin);
    url.searchParams.set('per_page', newValue);
    url.searchParams.delete('cursor');

    const currentSearch = new URLSearchParams(window.location.search).get('search');
    if (currentSearch) {
        url.searchParams.set('search', currentSearch);
    }

    router.visit(url.pathname + url.search, {
        only: ['teachers'],
        preserveState: false,
        preserveScroll: false,
        replace: true,
    });
});

const getNextUrl = (): string | null => {
    if (!props.nextCursor) {
        return null;
    }

    const url = new URL(props.path, window.location.origin);
    url.searchParams.set('cursor', props.nextCursor);

    const currentSearch = new URLSearchParams(window.location.search).get('search');
    if (currentSearch) {
        url.searchParams.set('search', currentSearch);
    }

    const currentPerPage = new URLSearchParams(window.location.search).get('per_page');
    if (currentPerPage) {
        url.searchParams.set('per_page', currentPerPage);
    }

    return url.pathname + url.search;
};

const getPrevUrl = (): string | null => {
    if (!props.prevCursor) {
        return null;
    }

    const url = new URL(props.path, window.location.origin);
    url.searchParams.set('cursor', props.prevCursor);

    const currentSearch = new URLSearchParams(window.location.search).get('search');
    if (currentSearch) {
        url.searchParams.set('search', currentSearch);
    }

    const currentPerPage = new URLSearchParams(window.location.search).get('per_page');
    if (currentPerPage) {
        url.searchParams.set('per_page', currentPerPage);
    }

    return url.pathname + url.search;
};

const nextUrl = computed(() => getNextUrl());
const prevUrl = computed(() => getPrevUrl());
</script>

<template>
    <nav
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        aria-label="Pagination"
    >
        <div class="flex items-center gap-2">
            <Label for="per-page" class="text-sm text-muted-foreground whitespace-nowrap">
                Per page:
            </Label>
            <Select v-model="selectedPerPage">
                <SelectTrigger id="per-page" class="w-20">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent class="min-w-[80px]">
                    <SelectItem
                        v-for="option in props.perPageOptions"
                        :key="option"
                        :value="String(option)"
                        class="whitespace-nowrap"
                    >
                        {{ option }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-muted-foreground">
                Showing {{ props.dataCount }} {{ props.dataCount === 1 ? 'result' : 'results' }}
            </span>
            <div class="flex items-center gap-2">
                <Button
                    v-if="prevUrl"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="prevUrl" preserve-scroll>
                        Previous
                    </Link>
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    disabled
                >
                    Previous
                </Button>
                <Button
                    v-if="nextUrl"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="nextUrl" preserve-scroll>
                        Next
                    </Link>
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    disabled
                >
                    Next
                </Button>
            </div>
        </div>
    </nav>
</template>

