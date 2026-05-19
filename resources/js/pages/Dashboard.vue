<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';
import CursorCard from '@/components/CursorCard.vue';
import {
    Layers,
    Library,
    Flame,
    CheckCircle,
    ArrowRight,
    PlusCircle,
    Trophy,
    ListCheck
} from 'lucide-vue-next';

// Define Props from Controller
defineProps<{
    recentSeries: Array<{
        id: string;
        name: string;
        image_data: string;
        clippers_count: number;
        requester?: { name: string };
        created_at: string;
    }>;
    stats: Array<{ label: string, value: string | number }>;
    pendingRequests?: {
        series: number;
        clippers: number;
    };
}>();

const { props: pageProps } = usePage<any>();
const isAdmin = computed(() => pageProps.auth.is_admin);

const getStatConfig = (label: string) => {
    switch (label) {
        case 'Total Series':
            return { icon: Library, color: 'text-info', bg: 'bg-info/10' };
        case 'Total Clippers':
            return { icon: Flame, color: 'text-primary', bg: 'bg-primary/10' };
        case 'My Clippers':
            return { icon: CheckCircle, color: 'text-info', bg: 'bg-info/10' };
        case 'My Series':
            return { icon: ListCheck, color: 'text-info', bg: 'bg-info/10' };
        case 'Completed Series':
            return { icon: Trophy, color: 'text-warning', bg: 'bg-warning/10' };
        default:
            return { icon: Layers, color: 'text-muted-content', bg: 'bg-muted-background/10' };
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

/**
 * Handle redirection when clicking on a stat box.
 *
 * @param label The label of the stat box clicked
 */
const handleStatClick = (label: string) => {
    switch (label) {
        case 'Total Clippers':
            router.get(route('series.index'));
            break;
        case 'Total Series':
            router.get(route('series.index'));
            break;
        case 'My Series':
            router.get(route('series.index', { filter: 'collected' }));
            break;
        case 'My Clippers':
            router.get(route('collection.clippers'));
            break;
        case 'Completed Series':
            router.get(route('series.index', { filter: 'completed' }));
            break;
    }
};
</script>

<template>

    <Head>
        <title>Clipper-MS | Dashboard</title>
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-8 max-w-7xl mx-auto w-full">

            <div class="grid gap-4 grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <CursorCard v-for="stat in stats" :key="stat.label"
                    @click="handleStatClick(stat.label)"
                    className="flex flex-col rounded-2xl border border-border-color p-4 sm:p-6 shadow-sm transition-all hover:shadow-md bg-component-background cursor-pointer hover:border-primary/50"
                >
                    <div class="relative z-10">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-sm font-bold text-muted-content uppercase tracking-wider min-w-0">{{ stat.label }}</span>
                            <div :class="['p-2 rounded-lg transition-transform group-hover:scale-110 flex-shrink-0', getStatConfig(stat.label).bg]">
                                <component :is="getStatConfig(stat.label).icon" :class="['w-5 h-5', getStatConfig(stat.label).color]" />
                            </div>
                        </div>
                        <span class="text-4xl font-black tracking-tight">{{ stat.value }}</span>
                    </div>
                </CursorCard>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-2">
                <Link :href="route('series.index')" class="group">
                    <CursorCard className="rounded-2xl border border-border-color p-8 shadow-sm transition-all hover:border-primary/50 hover:shadow-md bg-component-background">
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 rounded-lg bg-primary/10 text-primary">
                                    <Library class="w-6 h-6" />
                                </div>
                                <h3 class="text-2xl font-black">Series Catalog</h3>
                            </div>
                            <p class="text-muted-content text-sm leading-relaxed max-w-[280px]">View all series, check missing designs, and organize your sets.</p>
                            <div class="mt-8 flex items-center font-bold text-primary group-hover:gap-2 transition-all">
                                <span>Open Catalog</span>
                                <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link :href="route('series.create')" class="group">
                    <CursorCard 
                        className="rounded-2xl border border-border-color p-8 shadow-sm transition-all bg-component-background"
                        :class="isAdmin ? 'hover:border-success/50' : 'hover:border-primary/50'"
                    >
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div :class="['p-2 rounded-lg', isAdmin ? 'bg-success/10 text-success' : 'bg-primary/10 text-primary']">
                                    <PlusCircle v-if="isAdmin" class="w-6 h-6" />
                                    <Flame v-else class="w-6 h-6" />
                                </div>
                                <h3 class="text-2xl font-black">{{ isAdmin ? 'Add to System' : 'Request New Series' }}</h3>
                            </div>
                            <p class="text-muted-content text-sm leading-relaxed max-w-[280px]">
                                {{ isAdmin ? 'Found a new series? Help the community by adding it to the database.' : 'Suggest a new series to be added to the system.' }}
                            </p>
                            <div class="mt-8 flex items-center font-bold group-hover:gap-2 transition-all"
                                 :class="isAdmin ? 'text-success' : 'text-primary'">
                                <span>{{ isAdmin ? 'Register Series' : 'Submit Request' }}</span>
                                <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link v-if="isAdmin && (pendingRequests?.series || pendingRequests?.clippers)" :href="route('admin.requests.series.index')" class="group">
                    <CursorCard className="rounded-2xl border-2 border-primary bg-primary/5 p-8 shadow-sm transition-all hover:shadow-md">
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-primary text-button-content">
                                        <ArrowRight class="w-6 h-6 -rotate-45" />
                                    </div>
                                    <h3 class="text-2xl font-black">Pending Requests</h3>
                                </div>
                                <span class="bg-primary text-button-content px-3 py-1 rounded-full text-xs font-black">
                                    {{ (pendingRequests?.series || 0) + (pendingRequests?.clippers || 0) }}
                                </span>
                            </div>
                            <p class="text-primary text-sm font-bold uppercase tracking-tight">
                                {{ pendingRequests?.series }} Series • {{ pendingRequests?.clippers }} Clippers
                            </p>
                            <div class="mt-8 flex items-center font-bold text-primary group-hover:gap-2 transition-all">
                                <span>Review Now</span>
                                <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link :href="route('series.index', { filter: 'collected' })" class="group">
                    <CursorCard className="rounded-2xl border border-border-color p-8 shadow-sm transition-all hover:border-info/50 hover:shadow-md bg-component-background">
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 rounded-lg bg-info/10 text-info">
                                    <Layers class="w-6 h-6" />
                                </div>
                                <h3 class="text-2xl font-black">My Series</h3>
                            </div>
                            <p class="text-muted-content text-sm leading-relaxed max-w-[280px]">Track your progress and view your collected series.</p>
                            <div class="mt-8 flex items-center font-bold text-info group-hover:gap-2 transition-all">
                                <span>View Collection</span>
                                <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link :href="route('collection.clippers')" class="group">
                    <CursorCard className="rounded-2xl border border-border-color p-8 shadow-sm transition-all hover:border-info/50 hover:shadow-md bg-component-background">
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 rounded-lg bg-info/10 text-info">
                                    <CheckCircle class="w-6 h-6" />
                                </div>
                                <h3 class="text-2xl font-black">My Clippers</h3>
                            </div>
                            <p class="text-muted-content text-sm leading-relaxed max-w-[280px]">A dedicated board view of every single clipper you own.</p>
                            <div class="mt-8 flex items-center font-bold text-info group-hover:gap-2 transition-all">
                                <span>Open Board View</span>
                                <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>
            </div>

            <CursorCard className="flex-1 rounded-2xl border border-border-color p-6 shadow-sm bg-component-background">
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold">Recent Additions</h3>
                        <Link :href="route('series.index')"
                            class="text-xs font-bold text-primary uppercase hover:underline">
                            View All Series
                        </Link>
                    </div>

                    <div class="space-y-4">
                        <div v-for="item in recentSeries" :key="item.id"
                            class="flex items-center gap-4 rounded-lg border border-border-color/50 p-4 hover:bg-[var(--hover-overlay)] transition-colors bg-component-background/50 backdrop-blur-sm">

                            <div
                                class="h-12 w-16 rounded  overflow-hidden border border-border-color bg-component-background">
                                <img :src="item.image_data" :alt="item.name + 'Clipper Lighter Series'" class="h-full w-full object-contain" />
                            </div>

                            <div class="flex-1">
                                <Link :href="route('series.show', { series: item.id, slug: (item as any).slug })"
                                    class="font-bold text-sm hover:text-primary transition-colors">
                                    {{ item.name }}
                                </Link>
                                <p class="text-[10px] text-muted-content uppercase tracking-tight">
                                    {{ item.clippers_count }} Design(s) • Added by {{ item.requester?.name || 'System' }}
                                </p>
                            </div>

                            <Link :href="route('series.show', { series: item.id, slug: (item as any).slug })"
                                class="text-[10px] font-black text-primary uppercase border border-primary/20 px-3 py-1 rounded hover:bg-primary hover:text-button-content!  transition-all">
                                View Series
                            </Link>
                        </div>

                        <div v-if="recentSeries.length === 0"
                            class="text-center py-10 text-muted-content text-sm italic">
                            No series added yet.
                        </div>
                    </div>
                </div>
            </CursorCard>

        </div>
    </AppLayout>
</template>

