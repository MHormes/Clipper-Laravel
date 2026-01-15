<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Library, Heart } from 'lucide-vue-next';

interface Clipper {
    id: number;
    series_number: number;
    image_data: string;
    series: {
        id: string;
        name: string;
    };
    created_at: string;
}

const props = defineProps<{
    clippers: {
        data: Clipper[];
        links: any;
    };
}>();

const toggleCollection = (clipperId: number) => {
    router.post(route('clippers.toggle', clipperId), {}, {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="My Clippers" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <!-- Header -->
            <div class="grid grid-cols-1 lg:grid-cols-[1fr,auto] items-center gap-6 mb-20 h-auto lg:h-16">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight leading-tight">My Clippers</h1>
                    <p class="text-muted-foreground text-sm">A complete board of every clipper you own.</p>
                </div>
                <div>
                    <Link :href="route('collection.index')"
                        class="text-sm font-bold text-orange-600 hover:underline uppercase">
                        Back to Series View
                    </Link>
                </div>
            </div>

            <!-- Grid -->
            <div class="min-h-[500px] flex flex-col">
                <div v-if="clippers.data.length > 0" class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 xl:grid-cols-16 gap-1">
                    <div v-for="clipper in clippers.data" :key="clipper.id" class="group relative">
                        <div class="bg-white dark:bg-[#161615] rounded-2xl border-2 border-sidebar-border shadow-sm transition-all hover:border-orange-500 hover:shadow-lg">
                            
                            <!-- <div class="flex justify-between items-center mb-4">
                                <span class="text-[10px] font-black text-gray-400 uppercase truncate max-w-[80px]" :title="clipper.series.name">
                                    {{ clipper.series.name }}
                                </span>
                                <button @click="toggleCollection(clipper.id)"
                                    class="p-1.5 rounded-full transition-all text-red-500 bg-red-50 dark:bg-red-500/10 hover:bg-red-100">
                                    <Heart class="w-4 h-4 fill-current" />
                                </button>
                            </div> -->
                            
                            <div class="aspect-[1/4] rounded-xl overflow-hidden border border-gray-100 dark:border-gray-900 bg-white dark:bg-black relative group-hover:scale-[1.02] transition-transform duration-300">
                                <img :src="clipper.image_data" class="w-full h-full object-cover" />
                            </div>

                            <Link :href="route('collection.show', clipper.series.id)" class="absolute inset-0 z-0" aria-label="View Series">
                                <span class="sr-only">View Series</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="w-full h-full flex flex-col items-center justify-center py-24 bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border shadow-sm">
                    <div class="p-8 rounded-full bg-gray-50 dark:bg-white/5 mb-6 text-gray-300 dark:text-gray-700">
                        <Library class="w-16 h-16" />
                    </div>
                    <h2 class="text-3xl font-black mb-3">No Clippers Found</h2>
                    <p class="text-muted-foreground mb-10 text-center max-w-sm px-6">
                        Start your collection by adding clippers from the Series Catalog.
                    </p>
                    <Link :href="route('series.index')"
                        class="flex items-center gap-2 px-8 py-4 bg-orange-600 text-white hover:bg-orange-700 rounded-2xl font-black transition-all shadow-lg shadow-orange-900/20 active:scale-95"
                    >
                        GO TO CATALOG
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="clippers.links.length > 3 && clippers.data.length > 0" class="mt-12 mb-6 flex justify-center gap-2">
                    <template v-for="(link, k) in clippers.links" :key="k">
                        <span v-if="link.url === null" 
                            v-html="link.label" 
                            class="px-5 py-2.5 rounded-xl text-sm border font-bold opacity-50 cursor-not-allowed bg-white dark:bg-black border-sidebar-border"
                        />
                        
                        <Link v-else 
                            :href="link.url" 
                            v-html="link.label"
                            class="px-5 py-2.5 rounded-xl text-sm border font-bold transition-all shadow-sm"
                            :class="{ 
                                'bg-orange-600 text-white border-orange-600 shadow-orange-900/10': link.active, 
                                'bg-white dark:bg-black border-sidebar-border hover:border-orange-500/50': !link.active 
                            }" 
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
