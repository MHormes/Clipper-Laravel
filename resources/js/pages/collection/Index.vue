<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SeriesCard from '@/components/series/SeriesCard.vue';
import Pagination from '@/components/Pagination.vue';
import SortButton from '@/components/SortButton.vue';
import { useFilters } from '@/util/useFilters';
import { Head, Link } from '@inertiajs/vue3';
import { Search, Inbox, X, Library } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{ series: any; filters?: any }>();
const { search, sortCol, sortDir, isFiltered, resetFilters, toggleSort } = useFilters('collection.index', props.filters);
</script>

<template>
    <Head title="My Collection" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr,auto] items-center gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight">My Collection</h1>
                    <p class="text-muted-foreground text-sm">Overview of all series you have started collecting.</p>
                </div>

                <div class="grid grid-cols-1 md:flex md:items-center md:gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 lg:w-96 mb-2 md:mb-0">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input v-model="search" type="text" placeholder="Search your collection..." class="w-full pl-10 pr-10 py-2.5 bg-white dark:bg-black border border-sidebar-border rounded-xl focus:ring-2 focus:ring-orange-500 outline-none text-sm shadow-sm" />
                        <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-1"><X class="w-4 h-4" /></button>
                    </div>

                    <Link :href="route('collection.clippers')" class="shrink-0 px-5 py-2.5 bg-orange-600 text-white rounded-xl font-bold text-sm hover:bg-orange-700 transition-all shadow-md active:scale-95 flex items-center gap-2">
                        <Inbox class="w-4 h-4" /> VIEW ALL CLIPPERS
                    </Link>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 mb-12 pb-4 border-b border-sidebar-border/50">
                <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mr-2">Sort By:</span>
                
                <SortButton label="Name" column="name" :active-column="sortCol" :direction="sortDir" @toggle="toggleSort" />
                <SortButton label="Date Added" column="created_at" :active-column="sortCol" :direction="sortDir" @toggle="toggleSort" />

                <button v-if="isFiltered" @click="resetFilters" class="flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold border border-dashed border-red-500/50 text-red-500 hover:bg-red-500 hover:text-white ml-auto">
                    <X class="w-3 h-3" /> Reset All
                </button>
            </div>

            <div v-if="series.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <SeriesCard v-for="item in series.data" :key="item.id" 
                    :item="{ ...item, collected_count: item.collected_count, clippers_count: item.total_count }" 
                />
            </div>

            <div v-else class="w-full flex flex-col items-center justify-center py-24 bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border shadow-sm">
                <div class="p-8 rounded-full bg-gray-50 dark:bg-white/5 mb-6 text-gray-300 dark:text-gray-700">
                    <component :is="isFiltered ? Search : Library" class="w-16 h-16" />
                </div>
                <h2 class="text-3xl font-black mb-3">{{ isFiltered ? 'No Results' : 'No Collections Started' }}</h2>
                <button v-if="isFiltered" @click="search = ''" class="px-8 py-4 bg-gray-100 dark:bg-white/5 rounded-2xl font-black">Reset Search</button>
                <Link v-else :href="route('series.index')" class="px-8 py-4 bg-orange-600 text-white rounded-2xl font-black">GO TO CATALOG</Link>
            </div>

            <Pagination :links="series.links" />
        </div>
    </AppLayout>
</template>