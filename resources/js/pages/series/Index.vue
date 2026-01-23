<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SeriesCard from '@/components/series/SeriesCard.vue';
import Pagination from '@/components/Pagination.vue';
import SortButton from '@/components/SortButton.vue';
import { useFilters } from '@/util/useFilters';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage<any>();
const props = defineProps<{ series: any; filters?: any }>();
const isAdmin = computed(() => page.props.auth.is_admin);
const canCreate = computed(() => !!page.props.auth.user); // Anyone logged in can at least request

const { search, sortCol, sortDir, isFiltered, resetFilters, toggleSort } = useFilters('series.index', props.filters);
</script>

<template>
    <Head title="Series Catalog" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr,auto] items-center gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight">Series Catalog</h1>
                    <p class="text-muted-foreground text-sm">Browse all released and custom clipper sets.</p>
                </div>

                <div class="grid grid-cols-1 md:flex md:items-center md:gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 lg:w-96 mb-2 md:mb-0">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input v-model="search" type="text" placeholder="Search series..." class="w-full pl-10 pr-10 py-2.5 bg-white dark:bg-black border border-sidebar-border rounded-xl focus:ring-2 focus:ring-orange-500 text-sm shadow-sm" />
                        <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground p-1"><X class="w-4 h-4" /></button>
                    </div>

                    <Link v-if="canCreate" :href="route('series.create')" class="px-5 py-2.5 bg-orange-600 text-white rounded-xl font-bold text-sm hover:bg-orange-700 transition-all shadow-lg shadow-orange-900/20">
                        + {{ isAdmin ? 'REGISTER NEW SERIES' : 'REQUEST NEW SERIES' }}
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
                    :item="{ ...item, collected_count: item.collected_clippers_count, clippers_count: item.clippers_count }" 
                />
            </div>
            
            <div v-else class="w-full flex flex-col items-center justify-center py-24 bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border">
                <Search class="w-16 h-16 text-gray-300 mb-6" />
                <h2 class="text-3xl font-black mb-3">No Results Found</h2>
                <button @click="search = ''" class="px-8 py-4 bg-gray-100 dark:bg-white/5 rounded-2xl font-black transition-all">Reset Search</button>
            </div>

            <Pagination :links="series.links" />
        </div>
    </AppLayout>
</template>