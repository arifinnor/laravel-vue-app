<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

interface ChartOfAccount {
    id: string;
    code: string;
    name: string;
    account_type: string;
    display: string;
}

interface Props {
    modelValue?: string | null;
    accounts: ChartOfAccount[];
    accountTypeFilter?: string | null;
    isRequired?: boolean;
    placeholder?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    accounts: () => [],
    accountTypeFilter: null,
    isRequired: false,
    placeholder: 'Select account...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const open = ref(false);

const filteredAccounts = computed(() => {
    let accounts = props.accounts;

    if (props.accountTypeFilter) {
        accounts = accounts.filter(
            (account) => account.account_type === props.accountTypeFilter,
        );
    }

    return accounts;
});

const selectedAccount = computed(() => {
    if (!props.modelValue) {
        return null;
    }

    return filteredAccounts.value.find((account) => account.id === props.modelValue) || null;
});

const displayValue = computed(() => {
    if (selectedAccount.value) {
        return selectedAccount.value.display;
    }

    return props.placeholder;
});

const selectAccount = (accountId: string | null) => {
    emit('update:modelValue', accountId);
    open.value = false;
};
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                role="combobox"
                :aria-expanded="open"
                :class="cn(
                    'w-full justify-between',
                    !selectedAccount && 'text-muted-foreground',
                    props.class,
                )"
            >
                {{ displayValue }}
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-full p-0" :style="{ width: 'var(--radix-popover-trigger-width)' }">
            <Command>
                <CommandInput placeholder="Search by code or name..." />
                <CommandList>
                    <CommandEmpty>No account found.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="account in filteredAccounts"
                            :key="account.id"
                            :value="account.display"
                            @select="() => selectAccount(account.id)"
                        >
                            <Check
                                :class="cn(
                                    'mr-2 h-4 w-4',
                                    selectedAccount?.id === account.id ? 'opacity-100' : 'opacity-0',
                                )"
                            />
                            <div class="flex flex-col">
                                <span class="font-mono text-sm">{{ account.code }}</span>
                                <span class="text-xs text-muted-foreground">{{ account.name }}</span>
                            </div>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>

