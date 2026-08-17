<script setup lang="ts">
import { ref, nextTick } from 'vue';
import { Search, ChevronDown } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        search: string;
        groups: { label: string; items: any[] }[];
        itemValue: (item: any) => string;
        selectedLabel: string | null;
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
    }>(),
    {
        placeholder: 'Choose an option...',
        searchPlaceholder: 'Search...',
        emptyText: 'No matches.',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
    'update:search': [value: string];
}>();

const isOpen = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);

const open = () => {
    isOpen.value = true;
    nextTick(() => searchInput.value?.focus());
};

const select = (item: any) => {
    emit('update:modelValue', props.itemValue(item));
    isOpen.value = false;
    emit('update:search', '');
};

const close = (event: FocusEvent) => {
    const next = event.relatedTarget as Node | null;
    if (!(event.currentTarget as HTMLElement).contains(next)) {
        isOpen.value = false;
    }
};
</script>

<template>
    <div class="relative" @focusout="close">
        <button
            type="button"
            @click="isOpen ? (isOpen = false) : open()"
            class="flex w-full items-center justify-between rounded-xl border border-border-color bg-component-background p-3 text-left text-sm text-primary-content focus:border-primary focus:ring-primary"
        >
            <span :class="{ 'text-muted-content/50': !selectedLabel }">
                {{ selectedLabel ?? placeholder }}
            </span>
            <ChevronDown class="h-4 w-4 shrink-0 text-muted-content" />
        </button>

        <div
            v-if="isOpen"
            class="absolute z-10 mt-2 w-full overflow-hidden rounded-xl border border-border-color bg-primary-background shadow-2xl"
        >
            <div class="relative border-b border-border-color p-2">
                <input
                    ref="searchInput"
                    :value="search"
                    @input="emit('update:search', ($event.target as HTMLInputElement).value)"
                    type="text"
                    class="w-full rounded-lg border border-border-color bg-component-background p-2 pl-8 text-sm text-primary-content placeholder:text-muted-content/50 focus:border-primary focus:ring-primary"
                    :placeholder="searchPlaceholder"
                    @keydown.escape="isOpen = false"
                />
                <Search class="absolute top-1/2 left-4 h-3.5 w-3.5 -translate-y-1/2 text-muted-content" />
            </div>
            <div class="max-h-52 overflow-y-auto p-1">
                <div v-if="groups.length === 0" class="p-3 text-center text-xs text-muted-content">
                    {{ emptyText }}
                </div>
                <div v-for="group in groups" :key="group.label">
                    <div class="px-2 pt-2 pb-1 text-[10px] font-black tracking-widest text-muted-content uppercase">
                        {{ group.label }}
                    </div>
                    <button
                        v-for="item in group.items"
                        :key="itemValue(item)"
                        type="button"
                        @click="select(item)"
                        class="w-full rounded-lg px-2 py-2 text-left text-sm text-primary-content transition-colors hover:bg-hover-overlay"
                    >
                        <slot name="item" :item="item">{{ itemValue(item) }}</slot>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
