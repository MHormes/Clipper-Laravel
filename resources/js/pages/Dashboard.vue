<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// Define Props from Controller
defineProps<{
    recentSeries: Array<{
        id: string;
        name: string;
        image_data: string;
        clippers_count: number;
        user?: { name: string };
        created_at: string;
    }>;
    stats: Array<{ label: string, value: string | number }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>

    <Head title="Clipper MS | Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <div class="grid gap-4 md:grid-cols-3">
                <div v-for="stat in stats" :key="stat.label"
                    class="flex flex-col rounded-xl border border-sidebar-border bg-white p-6 shadow-sm dark:bg-[#161615]">
                    <span class="text-sm font-medium text-muted-foreground">{{ stat.label }}</span>
                    <span class="text-3xl font-bold tracking-tight">{{ stat.value }}</span>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-2">
                <Link :href="route('series.index')"
                    class="group relative overflow-hidden rounded-2xl border border-sidebar-border bg-white p-8 shadow-sm transition-all hover:border-orange-500/50 dark:bg-[#161615]">
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold">Series Catalog</h3>
                        <p class="mt-2 text-muted-foreground">View all series, check missing designs, and organize your
                            sets.</p>
                        <div class="mt-6 font-semibold text-orange-600 group-hover:underline">Open Catalog →</div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-5 transition-transform group-hover:scale-110">
                        <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17,1H7A2,2 0 0,0 5,3V21A2,2 0 0,0 7,23H17A2,2 0 0,0 19,21V3A2,2 0 0,0 17,1Z" />
                        </svg>
                    </div>
                </Link>

                <Link :href="route('series.create')"
                    class="group relative overflow-hidden rounded-2xl border border-sidebar-border bg-white p-8 shadow-sm transition-all hover:border-green-500/50 dark:bg-[#161615]">
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold">Add to System</h3>
                        <p class="mt-2 text-muted-foreground">Found a new series? Help the community by adding it to the
                            database.</p>
                        <div class="mt-6 font-semibold text-green-600 group-hover:underline">Register Series →</div>
                    </div>
                </Link>
            </div>

            <div class="flex-1 rounded-2xl border border-sidebar-border bg-white p-6 shadow-sm dark:bg-[#161615]">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold">Recent Additions</h3>
                    <Link :href="route('series.index')"
                        class="text-xs font-bold text-orange-600 uppercase hover:underline">
                        View All Series
                    </Link>
                </div>

                <div class="space-y-4">
                    <div v-for="item in recentSeries" :key="item.id"
                        class="flex items-center gap-4 rounded-lg border border-sidebar-border/50 p-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">

                        <div
                            class="h-12 w-16 rounded bg-gray-100 dark:bg-gray-800 overflow-hidden border border-sidebar-border">
                            <img :src="`/storage/${item.image_data}`" class="h-full w-full object-cover" />
                        </div>

                        <div class="flex-1">
                            <Link :href="route('series.show', item.id)"
                                class="font-bold text-sm hover:text-orange-600 transition-colors">
                                {{ item.name }}
                            </Link>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-tight">
                                {{ item.clippers_count }} Designs • Added by {{ item.user?.name || 'System' }}
                            </p>
                        </div>

                        <Link :href="route('series.show', item.id)"
                            class="text-[10px] font-black text-orange-600 uppercase border border-orange-600/20 px-3 py-1 rounded hover:bg-orange-600 hover:text-white transition-all">
                            View Set
                        </Link>
                    </div>

                    <div v-if="recentSeries.length === 0"
                        class="text-center py-10 text-muted-foreground text-sm italic">
                        No series added yet.
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>