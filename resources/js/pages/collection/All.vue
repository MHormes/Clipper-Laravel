<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';   
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Library, Heart, Search, X } from 'lucide-vue-next';
import { useFilters } from '@/util/useFilters';

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
    filters?: any;
}>();

const { search, isFiltered } = useFilters('collection.clippers', props.filters);
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

                <div class="grid grid-cols-1 md:flex md:items-center md:gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 lg:w-96 mb-2 md:mb-0">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input v-model="search" type="text" placeholder="Search by series name..." class="w-full pl-10 pr-10 py-2.5 bg-white dark:bg-black border border-sidebar-border rounded-xl focus:ring-2 focus:ring-orange-500 outline-none text-sm shadow-sm" />
                        <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-1"><X class="w-4 h-4" /></button>
                    </div>

                    <Link :href="route('collection.index')"
                        class="shrink-0 px-5 py-2.5 bg-orange-600 text-white rounded-xl font-bold text-sm hover:bg-orange-700 transition-all shadow-md active:scale-95 flex items-center gap-2">
                        BACK TO SERIES VIEW
                    </Link>
                </div>
            </div>

            <!-- Grid -->
            <div class="min-h-[500px] flex flex-col">
                <div v-if="clippers.data.length > 0" class="grid grid-cols-6 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 xl:grid-cols-16 gap-1">
                    <div v-for="clipper in clippers.data" :key="clipper.id" class="group relative">
                        <div class="bg-white dark:bg-[#161615] rounded-2xl border-2 border-sidebar-border shadow-sm transition-all hover:border-orange-500 hover:shadow-lg">
                            
                            <div class="aspect-[1/4] rounded-xl overflow-hidden border border-gray-100 dark:border-gray-900 bg-white dark:bg-black relative group-hover:scale-[1.02] transition-transform duration-300">
                                <img :src="clipper.image_data" :alt="clipper.series.name + ' #' + clipper.series_number + 'Clipper Lighter'" class="w-full h-full object-cover" />
                            </div>

                            <Link :href="route('series.show', clipper.series.id)" class="absolute inset-0 z-0" aria-label="View Series">
                                <span class="sr-only">View Series</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="w-full h-full flex flex-col items-center justify-center py-24 bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border shadow-sm">
                    <div class="p-8 rounded-full bg-gray-50 dark:bg-white/5 mb-6 text-gray-300 dark:text-gray-700">
                        <component :is="isFiltered ? Search : Library" class="w-16 h-16" />
                    </div>
                    <h2 class="text-3xl font-black mb-3">{{ isFiltered ? 'No Results Found' : 'No Clippers Found' }}</h2>
                    <p v-if="!isFiltered" class="text-muted-foreground mb-10 text-center max-w-sm px-6">
                        Start your collection by adding clippers from the Series Catalog.
                    </p>
                    <button v-if="isFiltered" @click="search = ''" class="px-8 py-4 bg-gray-100 dark:bg-white/5 rounded-2xl font-black active:scale-95 transition-all">
                        RESET SEARCH
                    </button>
                    <Link v-else :href="route('series.index')"
                        class="flex items-center gap-2 px-8 py-4 bg-orange-600 text-white hover:bg-orange-700 rounded-2xl font-black transition-all shadow-lg shadow-orange-900/20 active:scale-95"
                    >
                        GO TO CATALOG
                    </Link>
                </div>

                <Pagination :links="clippers.links" />
            </div>
        </div>
    </AppLayout>
</template>
