<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, watch, computed } from 'vue';
import { Search, Inbox, X } from 'lucide-vue-next';
import { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();
const canRegister = computed(() => page.props.auth.can.manage_series);

const props = defineProps<{
    series: {
        data: Array<{
            id: string;
            name: string;
            image_data: string;
            custom: boolean;
            clippers_count: number;
        }>;
        links: any; // For pagination
    };
    filters?: {
        search?: string;
    };
}>();

const search = ref(props.filters?.search || '');

const clearSearch = () => {
    search.value = '';
};

let timeout: any = null;
watch(search, (value) => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('series.index'), { search: value }, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    }, 300);
});
</script>

<template>

    <Head title="Series Catalog" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <!-- Stable Header: Using grid to prevent shifts -->
            <div class="grid grid-cols-1 lg:grid-cols-[1fr,auto] items-center gap-6 mb-20 h-auto lg:h-16">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight leading-tight">Series Catalog</h1>
                    <p class="text-muted-foreground text-sm">Browse all released and custom clipper sets.</p>
                </div>

                <div class="flex items-center gap-4 w-full lg:w-auto">
                    <div class="relative flex-1 lg:w-96">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Search series by name..."
                            class="w-full pl-10 pr-10 py-2.5 bg-white dark:bg-black border border-sidebar-border rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-all text-sm shadow-sm"
                        />
                        <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-1">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <Link v-if="canRegister" :href="route('series.create')"
                        class="shrink-0 px-5 py-2.5 bg-orange-600 text-white rounded-xl font-bold text-sm hover:bg-orange-700 transition-all shadow-md active:scale-95">
                        + REGISTER NEW SERIES
                    </Link>
                </div>
            </div>

            <!-- Content Container with fixed minimum height and stable layout -->
            <div class="min-h-[500px] flex flex-col">
                <div class="flex-1">
                    <div v-if="series.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <Link v-for="item in series.data" :key="item.id" :href="route('series.show', item.id)"
                            class="group bg-white dark:bg-[#161615] rounded-2xl overflow-hidden border border-sidebar-border shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
        
                            <div class="aspect-[4/3] relative overflow-hidden bg-white dark:bg-black border-b border-sidebar-border">
                                <img :src="`/storage/${item.image_data}`"
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
                                        {{ item.clippers_count }} Clippers
                                    </span>
                                    <span class="text-xs text-orange-600 font-bold group-hover:underline">View Series →</span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="w-full h-full flex flex-col items-center justify-center py-24 bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border shadow-sm">
                        <div class="p-8 rounded-full bg-gray-50 dark:bg-white/5 mb-6 text-gray-300 dark:text-gray-700">
                            <Search class="w-16 h-16" />
                        </div>
                        <h2 class="text-3xl font-black mb-3">No Results Found</h2>
                        <p class="text-muted-foreground mb-10 text-center max-w-sm px-6">
                            We couldn't find any series matching "<span class="text-foreground font-bold">{{ search }}</span>".
                        </p>
                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <button 
                                @click="clearSearch"
                                class="flex items-center gap-2 px-8 py-4 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 rounded-2xl font-black transition-all active:scale-95"
                            >
                                <X class="w-5 h-5" />
                                Reset Search
                            </button>
                            <Link v-if="canRegister" :href="route('series.create')"
                                class="flex items-center gap-2 px-8 py-4 bg-orange-600 text-white hover:bg-orange-700 rounded-2xl font-black transition-all shadow-lg shadow-orange-900/20 active:scale-95"
                            >
                                + REGISTER NEW SERIES
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pagination: Stable position at the bottom of the content area -->
                <div v-if="series.links.length > 3 && series.data.length > 0" class="mt-12 mb-6 flex justify-center gap-2">
                    <Link v-for="(link, k) in series.links" :key="k" :href="link.url" v-html="link.label"
                        class="px-5 py-2.5 rounded-xl text-sm border font-bold transition-all shadow-sm"
                        :class="{ 
                            'bg-orange-600 text-white border-orange-600 shadow-orange-900/10': link.active, 
                            'bg-white dark:bg-black border-sidebar-border hover:border-orange-500/50': !link.active,
                            'opacity-50 cursor-not-allowed': !link.url
                        }" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>