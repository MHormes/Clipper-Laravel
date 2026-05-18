<script setup lang="ts">
import { ArrowUp, ArrowDown, ArrowUpDown } from 'lucide-vue-next';

defineProps<{
    label: string;
    column: string;
    activeColumn: string;
    direction: string;
}>();

defineEmits(['toggle']);
</script>

<template>
    <button @click="$emit('toggle', column)"
        :class="['flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold transition-all border',
        activeColumn === column ? 'bg-primary border-primary text-button-content shadow-md' : 'bg-muted-background border-border-color text-muted-content']">
        {{ label }}
        <ArrowUp v-if="activeColumn === column && direction === 'asc'" class="w-3 h-3" />
        <ArrowDown v-else-if="activeColumn === column && direction === 'desc'" class="w-3 h-3" />
        <ArrowUpDown v-else class="w-3 h-3 opacity-30" />
    </button>
</template>
