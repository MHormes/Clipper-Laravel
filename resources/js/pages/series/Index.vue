<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps<{
    series: {
        data: Array<{
            id: string;
            name: string;
            image_data: string;
            custom: boolean;
            clippers_count: number;
        }>;
        links: any; // For pagination
    }
}>();
</script>

<template>

    <Head title="Series Catalog" />

    <AppLayout>
        <div class="max-w-7xl mx-auto p-6">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight">Series Catalog</h1>
                    <p class="text-muted-foreground">Browse all released and custom clipper sets.</p>
                </div>

                <Link v-if="$page.props.auth.can.manage_series" :href="route('series.create')"
                    class="px-4 py-2 bg-orange-600 text-white rounded-lg font-bold text-sm hover:bg-orange-700 transition-colors">
                    + REGISTER NEW SERIES
                </Link>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <Link v-for="item in series.data" :key="item.id" :href="route('series.show', item.id)"
                    class="group bg-white dark:bg-[#161615] rounded-2xl overflow-hidden border border-sidebar-border shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">

                    <div class="aspect-[4/3] relative overflow-hidden bg-white dark:bg-black">
                        <img :src="`/storage/${item.image_data}`"
                            class="w-full h-full object-contain" />
                        <div v-if="item.custom"
                            class="absolute top-3 left-3 px-2 py-1 bg-black/50 backdrop-blur-md text-[10px] text-white font-bold rounded">
                            CUSTOM
                        </div>
                    </div>

                    <div class="p-4">
                        <h3 class="font-bold text-lg truncate group-hover:text-orange-600 transition-colors">
                            {{ item.name }}
                        </h3>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-widest">
                                {{ item.clippers_count }} Clippers
                            </span>
                            <span class="text-xs text-orange-600 font-bold group-hover:underline">View Set →</span>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-if="series.links.length > 3" class="mt-12 flex justify-center gap-2">
                <Link v-for="(link, k) in series.links" :key="k" :href="link.url" v-html="link.label"
                    class="px-4 py-2 rounded-lg text-sm border transition-colors"
                    :class="{ 'bg-orange-600 text-white border-orange-600': link.active, 'bg-white dark:bg-black border-sidebar-border': !link.active }" />
            </div>
        </div>
    </AppLayout>
</template>