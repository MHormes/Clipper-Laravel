<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    item: {
        id: string;
        name: string;
        image_data: string;
        custom: boolean;
        clippers_count?: number; // Total possible
        collected_count?: number; // Count for progress
    };
    href?: string;
}>();

const totalTarget = computed(() => (props.item.custom ? Math.max(props.item.clippers_count || 0, 1) : 4));
const progressWidth = computed(() => ((props.item.collected_count || 0) / totalTarget.value) * 100);
const targetHref = computed(() => props.href || route('series.show', { series: props.item.id, slug: (props.item as any).slug }));
</script>

<template>
    <Link :href="targetHref"
        class="group bg-component-background rounded-2xl overflow-hidden border border-border-color shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">

        <div class="aspect-[4/3] relative overflow-hidden bg-component-background border-b border-border-color">
            <img :src="item.image_data" class="w-full h-full object-contain" />
            <div v-if="item.custom" class="absolute top-3 left-3 px-2 py-1 bg-black/60 backdrop-blur-md text-[10px] text-button-content font-bold rounded">
                CUSTOM
            </div>
        </div>

        <div class="p-5">
            <h3 class="font-bold text-lg truncate group-hover:text-primary transition-colors">
                {{ item.name }}
            </h3>

            <div class="flex flex-col gap-2 mt-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-muted-content uppercase tracking-widest">
                        {{ item.collected_count || 0 }} / {{ totalTarget }} Collected
                    </span>
                    <span class="text-[10px] text-primary font-bold group-hover:underline">VIEW →</span>
                </div>
            </div>

            <div class="w-full bg-muted-background dark:bg-muted-background h-1.5 rounded-full mt-3">
                <div class="bg-primary h-full transition-all rounded-full" :style="{ width: `${progressWidth}%` }"></div>
            </div>
        </div>
    </Link>
</template>
