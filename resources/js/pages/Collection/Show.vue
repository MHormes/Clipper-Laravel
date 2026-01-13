<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Heart } from 'lucide-vue-next';

interface Clipper {
    id: number;
    series_number: number;
    image_data: string;
}

interface Series {
    id: string;
    name: string;
    custom: boolean;
    image_data: string;
    clippers: Clipper[];
}

const props = defineProps<{
    series: Series;
    userCollection: number[]; // Array of series_numbers collected
}>();

const toggleCollection = (clipperId: number) => {
    router.post(route('clippers.toggle', clipperId), {}, {
        preserveScroll: true,
    });
};

</script>

<template>

    <Head :title="series.name + ' - My Collection'" />

    <AppLayout>
        <div class="max-w-7xl mx-auto p-6 space-y-8">
            <div class="flex flex-col md:flex-row gap-8 items-start bg-white dark:bg-[#161615] p-8 rounded-3xl border border-sidebar-border relative">
                
                <div class="w-full md:w-1/3 aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-black">
                    <img :src="series.image_data" class="w-full h-full object-cover" />
                </div>

                <div class="flex-1 space-y-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-4xl font-black uppercase tracking-tight">{{ series.name }}</h1>
                            <span v-if="series.custom"
                                class="px-3 py-1 bg-orange-100 text-orange-700 text-[10px] font-black rounded-full uppercase">
                                Custom Set
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground font-medium mt-2">
                            You have collected <span class="text-foreground font-bold">{{ series.clippers.length }}</span> clippers from this series.
                        </p>
                    </div>

                    <div class="pt-4 flex gap-4">
                        <Link :href="route('collection.index')"
                            class="px-6 py-3 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 rounded-xl font-bold text-sm transition-all uppercase">
                            Back to Collection
                        </Link>
                        <Link :href="route('series.show', series.id)"
                            class="px-6 py-3 bg-orange-600 text-white hover:bg-orange-700 rounded-xl font-bold text-sm transition-all uppercase shadow-lg shadow-orange-900/20">
                            View Full Series
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Collected Clippers Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                <div v-for="clipper in series.clippers" :key="clipper.id" class="group">
                    <div class="bg-white dark:bg-[#161615] p-4 rounded-2xl border-2 border-sidebar-border shadow-sm transition-all hover:border-orange-500">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-xs font-black text-gray-400 uppercase">#{{ clipper.series_number }}</span>
                            <button @click="toggleCollection(clipper.id)"
                                class="p-2 rounded-full transition-all text-red-500 bg-red-50 dark:bg-red-500/10 hover:bg-red-100">
                                <Heart class="w-5 h-5 fill-current" />
                            </button>
                        </div>
                        <div class="aspect-[1/4] rounded-xl overflow-hidden border border-gray-100 dark:border-gray-900 bg-white dark:bg-black">
                            <img :src="clipper.image_data" class="w-full h-full object-cover" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
