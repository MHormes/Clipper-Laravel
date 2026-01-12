<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { 
    Layers, 
    Library, 
    Flame, 
    CheckCircle,
    ArrowRight,
    PlusCircle,
    Trophy
} from 'lucide-vue-next';

// Define Props from Controller
defineProps<{
    recentSeries: Array<{
        id: string;
        name: string;
        image_data: string;
        clippers_count: number;
        creator?: { name: string };
        created_at: string;
    }>;
    stats: Array<{ label: string, value: string | number }>;
}>();

const getStatConfig = (label: string) => {
    switch (label) {
        case 'Total Series':
            return { icon: Library, color: 'text-blue-600', bg: 'bg-blue-500/10' };
        case 'Total Clippers':
            return { icon: Flame, color: 'text-orange-600', bg: 'bg-orange-500/10' };
        case 'My Clippers':
            return { icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-500/10' };
        case 'My Series':
            return { icon: Layers, color: 'text-purple-600', bg: 'bg-purple-500/10' };
        case 'Completed Series':
            return { icon: Trophy, color: 'text-amber-600', bg: 'bg-amber-500/10' };
        default:
            return { icon: Layers, color: 'text-gray-600', bg: 'bg-gray-500/10' };
    }
};

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
        <div class="flex h-full flex-1 flex-col gap-8 p-8 max-w-7xl mx-auto w-full">

            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div v-for="stat in stats" :key="stat.label"
                    class="group relative flex flex-col rounded-2xl border border-sidebar-border bg-white p-6 shadow-sm transition-all hover:shadow-md dark:bg-[#161615]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-muted-foreground uppercase tracking-wider">{{ stat.label }}</span>
                        <div :class="['p-2 rounded-lg transition-transform group-hover:scale-110', getStatConfig(stat.label).bg]">
                            <component :is="getStatConfig(stat.label).icon" :class="['w-5 h-5', getStatConfig(stat.label).color]" />
                        </div>
                    </div>
                    <span class="text-4xl font-black tracking-tight">{{ stat.value }}</span>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-2">
                <Link :href="route('series.index')"
                    class="group relative overflow-hidden rounded-2xl border border-sidebar-border bg-white p-8 shadow-sm transition-all hover:border-orange-500/50 hover:shadow-md dark:bg-[#161615]">
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 rounded-lg bg-orange-500/10 text-orange-600">
                                <Library class="w-6 h-6" />
                            </div>
                            <h3 class="text-2xl font-black">Series Catalog</h3>
                        </div>
                        <p class="text-muted-foreground text-sm leading-relaxed max-w-[280px]">View all series, check missing designs, and organize your sets.</p>
                        <div class="mt-8 flex items-center font-bold text-orange-600 group-hover:gap-2 transition-all">
                            <span>Open Catalog</span>
                            <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                        </div>
                    </div>
                </Link>

                <Link v-if="$page.props.auth.can.manage_series" :href="route('series.create')"
                    class="group relative overflow-hidden rounded-2xl border border-sidebar-border bg-white p-8 shadow-sm transition-all hover:border-green-500/50 hover:shadow-md dark:bg-[#161615]">
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 rounded-lg bg-green-500/10 text-green-600">
                                <PlusCircle class="w-6 h-6" />
                            </div>
                            <h3 class="text-2xl font-black">Add to System</h3>
                        </div>
                        <p class="text-muted-foreground text-sm leading-relaxed max-w-[280px]">Found a new series? Help the community by adding it to the database.</p>
                        <div class="mt-8 flex items-center font-bold text-green-600 group-hover:gap-2 transition-all">
                            <span>Register Series</span>
                            <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                        </div>
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
                            class="h-12 w-16 rounded bg-white dark:bg-black overflow-hidden border border-sidebar-border">
                            <img :src="`/storage/${item.image_data}`" class="h-full w-full object-contain" />
                        </div>

                        <div class="flex-1">
                            <Link :href="route('series.show', item.id)"
                                class="font-bold text-sm hover:text-orange-600 transition-colors">
                                {{ item.name }}
                            </Link>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-tight">
                                {{ item.clippers_count }} Designs • Added by {{ item.creator?.name || 'System' }}
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