<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SeriesCard from '@/components/series/SeriesCard.vue';
import Pagination from '@/components/Pagination.vue';
import SortButton from '@/components/SortButton.vue';
import { useFilters } from '@/util/useFilters';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage<any>();
const props = defineProps<{ series: any; filters?: any }>();
const isAdmin = computed(() => page.props.auth.is_admin);
const canCreate = computed(() => !!page.props.auth.user); // Anyone logged in can at least request

const { search, sortCol, sortDir, filter, type, isFiltered, resetFilters, toggleSort } = useFilters('series.index', props.filters);

const originFilters = [
    { label: 'All', value: 'all' },
    { label: 'Official', value: 'official' },
    { label: 'Custom', value: 'custom' }
];

const statusFilters = [
    { label: 'All', value: 'all' },
    { label: 'Collected', value: 'collected' },
    { label: 'Not Collected', value: 'none' },
    { label: 'Completed', value: 'completed' }
];
</script>

<template>

    <Head title="Series Catalog" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-4 md:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr,auto] items-center gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight">Series Catalog</h1>
                    <p class="text-muted-content text-sm">Browse all released and custom clipper sets.</p>
                </div>

                <div class="grid grid-cols-1 md:flex md:items-center md:gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 lg:w-96 mb-2 md:mb-0">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-content" />
                        <input v-model="search" type="text" placeholder="Search series..."
                            class="w-full pl-10 pr-10 py-2.5 bg-primary-background border border-border-color rounded-xl focus:ring-2 focus:ring-primary text-sm shadow-sm" />
                        <button v-if="search" @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-content p-1">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <Link v-if="canCreate" :href="route('series.create')"
                        class="px-5 py-2.5 bg-primary text-button-content rounded-xl font-bold text-sm hover:bg-primary hover:text-button-content!  transition-all shadow-lg shadow-primary/20">
                        + {{ isAdmin ? 'REGISTER NEW SERIES' : 'REQUEST NEW SERIES' }}
                    </Link>
                </div>
            </div>

            <div class="flex flex-col gap-6 mb-12 pb-4 border-b border-border-color/50">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <div class="flex flex-wrap items-center gap-6">
                        <!-- Series Origin Group -->
                        <div class="flex flex-col gap-2">
                            <span
                                class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-content/60 px-1">Origin</span>
                            <div
                                class="flex flex-wrap justify-center items-center gap-x-1 gap-y-0.5 bg-border-color/30 p-1 rounded-xl">
                                <button v-for="f in originFilters" :key="f.value" @click="type = f.value" :class="[
                                    'px-2 sm:px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all',
                                    type === f.value ? 'bg-primary text-button-content shadow-sm' : 'bg-muted-background text-muted-content hover:text-primary-content'
                                ]">
                                    {{ f.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Status Group -->
                        <div class="flex flex-col gap-2">
                            <span
                                class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-content/60 px-1">Your
                                Status</span>
                            <div
                                class="flex flex-wrap justify-center items-center gap-x-1 gap-y-0.5 bg-border-color/30 p-1 rounded-xl">
                                <button v-for="f in statusFilters" :key="f.value" @click="filter = f.value" :class="[
                                    'px-2 sm:px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all',
                                    filter === f.value ? 'bg-primary text-button-content shadow-sm' : 'bg-muted-background text-muted-content hover:text-primary-content'
                                ]">
                                    {{ f.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 gap-y-2 self-end">
                        <span class="text-[10px] font-black uppercase tracking-widest text-muted-content mr-2">Sort
                            By:</span>

                        <SortButton label="Name" column="name" :active-column="sortCol" :direction="sortDir"
                            @toggle="toggleSort" />
                        <SortButton label="Date Added" column="created_at" :active-column="sortCol" :direction="sortDir"
                            @toggle="toggleSort" />

                        <button v-if="isFiltered" @click="resetFilters"
                            class="flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold border border-dashed border-error/50 text-error hover:bg-error hover:text-button-content!  ml-auto">
                            <X class="w-3 h-3" /> Reset All
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 px-1">
                    <span class="text-[10px] font-black uppercase tracking-widest text-muted-content">
                        Showing <span class="text-primary-content">{{ series.total }}</span> series
                    </span>
                    <div class="h-px flex-1 bg-border-color/30 ml-2"></div>
                </div>
            </div>

            <div v-if="series.data.length > 0"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <SeriesCard v-for="item in series.data" :key="item.id"
                    :item="{ ...item, collected_count: item.collected_clippers_count, clippers_count: item.clippers_count }" />
            </div>

            <div v-else
                class="w-full flex flex-col items-center justify-center py-24 bg-component-background rounded-3xl border border-dashed border-border-color">
                <Search class="w-16 h-16 text-muted-content mb-6" />
                <h2 class="text-3xl font-black mb-3">No Results Found</h2>
                <button @click="search = ''"
                    class="px-8 py-4 bg-muted-background rounded-2xl font-black transition-all">Reset Search</button>
            </div>

            <Pagination :links="series.links" />
        </div>
    </AppLayout>
</template>
