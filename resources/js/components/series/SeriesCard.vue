<script setup lang="ts">
import Skeleton from '@/components/ui/Skeleton.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    item: {
        id: string;
        name: string;
        slug?: string;
        image_data: string;
        custom: boolean;
        clippers_count?: number; // Total possible
        collected_count?: number; // Count for progress
    };
    href?: string;
}>();

const totalTarget = computed(() =>
    props.item.custom ? Math.max(props.item.clippers_count || 0, 1) : 4,
);
const progressWidth = computed(
    () => ((props.item.collected_count || 0) / totalTarget.value) * 100,
);
const targetHref = computed(
    () =>
        props.href ||
        route('series.show', { series: props.item.id, slug: props.item.slug }),
);
const imageLoaded = ref(false);

watch(
    () => `${props.item.id}:${props.item.image_data}`,
    () => {
        imageLoaded.value = false;
    },
    { immediate: true },
);
</script>

<template>
    <Link
        :href="targetHref"
        class="group overflow-hidden rounded-2xl border border-border-color bg-component-background shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl"
    >
        <div
            class="relative aspect-[4/3] overflow-hidden border-b border-border-color bg-component-background"
        >
            <Skeleton
                v-if="!imageLoaded"
                class="absolute inset-0 h-full w-full rounded-none"
            />
            <img
                v-show="imageLoaded"
                :src="item.image_data"
                class="h-full w-full object-contain"
                @load="imageLoaded = true"
                @error="imageLoaded = true"
            />
            <div
                v-if="item.custom"
                class="absolute top-3 left-3 rounded bg-black/60 px-2 py-1 text-[10px] font-bold text-button-content backdrop-blur-md"
            >
                CUSTOM
            </div>
        </div>

        <div class="p-5">
            <h3
                class="truncate text-lg font-bold transition-colors group-hover:text-primary"
            >
                {{ item.name }}
            </h3>

            <div class="mt-3 flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold tracking-widest text-muted-content uppercase"
                    >
                        {{ item.collected_count || 0 }} /
                        {{ totalTarget }} Collected
                    </span>
                    <span
                        class="text-[10px] font-bold text-primary group-hover:underline"
                        >VIEW →</span
                    >
                </div>
            </div>

            <div class="mt-3 h-1.5 w-full rounded-full bg-muted-background">
                <div
                    class="h-full rounded-full bg-primary transition-all"
                    :style="{ width: `${progressWidth}%` }"
                ></div>
            </div>
        </div>
    </Link>
</template>
