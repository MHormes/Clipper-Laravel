<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, watch, computed } from 'vue';
import { Search, Inbox, X, Library, ArrowUp, ArrowDown, ArrowUpDown } from 'lucide-vue-next';
import { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();

const props = defineProps<{
    series: {
        data: Array<{
            id: string;
            name: string;
            image_data: string;
            custom: boolean;
            collected_count: number;
            total_count: number;
        }>;
        links: any; // For pagination
    };
    filters?: {
        search?: string;
        sortCol?: string;
        sortDir?: string;
    };
}>();

const search = ref(props.filters?.search || '');
const sortCol = ref(props.filters?.sortCol || '');
const sortDir = ref(props.filters?.sortDir || '');

const isFiltered = computed(() => {
    return search.value !== '' || sortCol.value !== '' || sortDir.value !== '';
});

const resetFilters = () => {
    search.value = '';
    sortCol.value = '';
    sortDir.value = '';
    updateResults();
};

const clearSearch = () => {
    search.value = '';
    updateResults();
};

const toggleSort = (column: string) => {
    if (sortCol.value !== column) {
        sortCol.value = column;
        sortDir.value = 'asc';
    } else if (sortDir.value === 'asc') {
        sortDir.value = 'desc';
    } else {
        sortCol.value = '';
        sortDir.value = '';
    }
    updateResults();
};

const updateResults = () => {
    router.get(route('collection.index'), {
        search: search.value,
        sortCol: sortCol.value,
        sortDir: sortDir.value
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

let timeout: any = null;
watch(search, () => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(updateResults, 300);
});

</script>

<template>

    <Head title="My Collection" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <!-- Stable Header -->
            <div class="grid grid-cols-1 lg:grid-cols-[1fr,auto] items-center gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight leading-tight">My Collection</h1>
                    <p class="text-muted-foreground text-sm">Overview of all series you have started collecting.</p>
                </div>

                <div class="flex items-center gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 lg:w-96">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search your collection..."
                            class="w-full pl-10 pr-10 py-2.5 bg-white dark:bg-black border border-sidebar-border rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-all text-sm shadow-sm"
                        />
                        <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-1">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <Link :href="route('collection.clippers')"
                        class="shrink-0 px-5 py-2.5 bg-orange-600 text-white rounded-xl font-bold text-sm hover:bg-orange-700 transition-all shadow-md active:scale-95 flex items-center gap-2">
                        <Inbox class="w-4 h-4" />
                        VIEW ALL CLIPPERS
                    </Link>
                </div>
            </div>

            <!-- Sort Controls -->
            <div class="flex flex-wrap items-center gap-3 mb-12 pb-4 border-b border-sidebar-border/50">
                <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mr-2">Sort By:</span>
            
                <button @click="toggleSort('name')"
                    :class="['flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold transition-all border', 
                    sortCol === 'name' ? 'bg-orange-600 border-orange-600 text-white shadow-md' : 'bg-white dark:bg-white/5 border-sidebar-border text-muted-foreground']">
                    Name
                    <ArrowUp v-if="sortCol === 'name' && sortDir === 'asc'" class="w-3 h-3" />
                    <ArrowDown v-else-if="sortCol === 'name' && sortDir === 'desc'" class="w-3 h-3" />
                    <ArrowUpDown v-else class="w-3 h-3 opacity-30" />
                </button>

                <button @click="toggleSort('created_at')"
                    :class="['flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold transition-all border', 
                    sortCol === 'created_at' ? 'bg-orange-600 border-orange-600 text-white shadow-md' : 'bg-white dark:bg-white/5 border-sidebar-border text-muted-foreground']">
                    Date Added
                    <ArrowUp v-if="sortCol === 'created_at' && sortDir === 'asc'" class="w-3 h-3" />
                    <ArrowDown v-else-if="sortCol === 'created_at' && sortDir === 'desc'" class="w-3 h-3" />
                    <ArrowUpDown v-else class="w-3 h-3 opacity-30" />
                </button>

                <button 
                    v-if="isFiltered"
                    @click="resetFilters"
                    class="flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold transition-all border border-dashed border-red-500/50 text-red-500 hover:bg-red-500 hover:text-white ml-auto"
                >
                    <X class="w-3 h-3" />
                    Reset All
                </button>
            </div>

            <!-- Content Container -->
            <div class="min-h-[500px] flex flex-col">
                <div class="flex-1">
                    <div v-if="series.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <Link v-for="item in series.data" :key="item.id" :href="route('collection.show', item.id)"
                            class="group bg-white dark:bg-[#161615] rounded-2xl overflow-hidden border border-sidebar-border shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
        
                            <div class="aspect-[4/3] relative overflow-hidden bg-white dark:bg-black border-b border-sidebar-border">
                                <img :src="item.image_data"
                                    class="w-full h-full object-contain" />
                                <div v-if="item.custom"
                                    class="absolute top-3 left-3 px-2 py-1 bg-black/50 backdrop-blur-md text-[10px] text-white font-bold rounded">
                                    CUSTOM
                                </div>
                            </div>
        
                            <div class="p-5">
                                <h3 class="font-bold text-lg truncate group-hover:text-orange-600 transition-colors">
                                    {{ item.name }}
                                </h3>
                                <div class="flex justify-between items-center mt-3">
                                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-widest bg-gray-100 dark:bg-white/5 px-2 py-1 rounded">
                                        {{ item.collected_count }} / {{item.custom ? item.total_count : 4 }} Collected
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full mt-3">
                                    <div class="bg-orange-500 h-full transition-all rounded-full"
                                        :style="{ width: `${(item.collected_count / (item.custom ?  Math.max(item.total_count, 1) : 4)) * 100}%` }">
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-else-if="isFiltered" class="w-full flex flex-col items-center justify-center py-24 bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border">
                        <Search class="w-16 h-16 text-gray-300 mb-6" />
                        <h2 class="text-3xl font-black mb-3">No Viewable Series</h2>
                        <p class="text-muted-foreground mb-10 text-center max-w-sm px-6">
                            We couldn't find any of your collected series matching "<span class="text-foreground font-bold">{{ search }}</span>".
                        </p>
                        <button @click="clearSearch" class="px-8 py-4 bg-gray-100 dark:bg-white/5 rounded-2xl font-black transition-all">
                            Reset Search
                        </button>
                    </div>

                    <div v-else class="w-full h-full flex flex-col items-center justify-center py-24 bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border shadow-sm">
                        <div class="p-8 rounded-full bg-gray-50 dark:bg-white/5 mb-6 text-gray-300 dark:text-gray-700">
                            <Library class="w-16 h-16" />
                        </div>
                        <h2 class="text-3xl font-black mb-3">No Collections Started</h2>
                        <p class="text-muted-foreground mb-10 text-center max-w-sm px-6">
                            You haven't added any clippers to your collection yet. Visit the catalog to start collecting!
                        </p>
                        <Link :href="route('series.index')"
                            class="flex items-center gap-2 px-8 py-4 bg-orange-600 text-white hover:bg-orange-700 rounded-2xl font-black transition-all shadow-lg shadow-orange-900/20 active:scale-95"
                        >
                            GO TO CATALOG
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                 <div v-if="series.links.length > 3 && series.data.length > 0" class="mt-12 mb-6 flex justify-center gap-2">
                    <template v-for="(link, k) in series.links" :key="k">
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
