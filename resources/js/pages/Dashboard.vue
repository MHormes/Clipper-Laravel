<script setup lang="ts">
import CursorCard from '@/components/CursorCard.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle,
    Flame,
    Layers,
    Library,
    ListCheck,
    PlusCircle,
    Trophy,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

interface RecentSeries {
    id: string;
    name: string;
    slug?: string;
    image_data: string;
    clippers_count: number;
    requester?: { name: string };
    created_at: string;
}

// Define Props from Controller
defineProps<{
    recentSeries: RecentSeries[];
    stats: Array<{ label: string; value: string | number }>;
    pendingRequests?: {
        series: number;
        clippers: number;
    };
}>();

const { props: pageProps } = usePage<any>();
const isAdmin = computed(() => pageProps.auth.is_admin);
const recentImageLoadState = ref<Record<string, boolean>>({});

const recentImageKey = (item: RecentSeries) => `${item.id}:${item.image_data}`;
const isRecentImageLoaded = (item: RecentSeries) =>
    !!recentImageLoadState.value[recentImageKey(item)];
const markRecentImageLoaded = (item: RecentSeries) => {
    recentImageLoadState.value = {
        ...recentImageLoadState.value,
        [recentImageKey(item)]: true,
    };
};

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
            return {
                icon: Layers,
                color: 'text-muted-content',
                bg: 'bg-muted-background/10',
            };
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
        <div class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-8 p-4 md:p-8">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <CursorCard v-for="stat in stats" :key="stat.label" @click="handleStatClick(stat.label)"
                    className="flex flex-col rounded-2xl border border-border-color p-4 sm:p-6 shadow-sm transition-all hover:shadow-md bg-component-background cursor-pointer hover:border-primary/50">
                    <div class="relative z-10">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <span class="min-w-0 text-sm font-bold tracking-wider text-muted-content uppercase">{{
                                stat.label }}</span>
                            <div :class="[
                                'hidden flex-shrink-0 rounded-lg p-2 transition-transform group-hover:scale-110 sm:flex',
                                getStatConfig(stat.label).bg,
                            ]">
                                <component :is="getStatConfig(stat.label).icon" :class="[
                                    'h-5 w-5',
                                    getStatConfig(stat.label).color,
                                ]" />
                            </div>
                        </div>
                        <span class="text-4xl font-black tracking-tight">{{
                            stat.value
                        }}</span>
                    </div>
                </CursorCard>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-2">
                <Link :href="route('series.index')" class="group">
                    <CursorCard
                        className="rounded-2xl border border-border-color p-8 shadow-sm transition-all hover:border-primary/50 hover:shadow-md bg-component-background">
                        <div class="relative z-10">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-lg bg-primary/10 p-2 text-primary">
                                    <Library class="h-6 w-6" />
                                </div>
                                <h3 class="text-2xl font-black">
                                    Series Catalog
                                </h3>
                            </div>
                            <p class="max-w-[280px] text-sm leading-relaxed text-muted-content">
                                View all series, check missing designs, and
                                organize your sets.
                            </p>
                            <div class="mt-8 flex items-center font-bold text-primary transition-all group-hover:gap-2">
                                <span>Open Catalog</span>
                                <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link :href="route('series.create')" class="group">
                    <CursorCard
                        className="rounded-2xl border border-border-color p-8 shadow-sm transition-all bg-component-background"
                        :class="isAdmin
                            ? 'hover:border-success/50'
                            : 'hover:border-primary/50'
                            ">
                        <div class="relative z-10">
                            <div class="mb-4 flex items-center gap-3">
                                <div :class="[
                                    'rounded-lg p-2',
                                    isAdmin
                                        ? 'bg-success/10 text-success'
                                        : 'bg-primary/10 text-primary',
                                ]">
                                    <PlusCircle v-if="isAdmin" class="h-6 w-6" />
                                    <Flame v-else class="h-6 w-6" />
                                </div>
                                <h3 class="text-2xl font-black">
                                    {{
                                        isAdmin
                                            ? 'Add to System'
                                            : 'Request New Series'
                                    }}
                                </h3>
                            </div>
                            <p class="max-w-[280px] text-sm leading-relaxed text-muted-content">
                                {{
                                    isAdmin
                                        ? 'Found a new series? Help the community by adding it to the database.'
                                        : 'Suggest a new series to be added to the system.'
                                }}
                            </p>
                            <div class="mt-8 flex items-center font-bold transition-all group-hover:gap-2" :class="isAdmin ? 'text-success' : 'text-primary'
                                ">
                                <span>{{
                                    isAdmin
                                        ? 'Register Series'
                                        : 'Submit Request'
                                }}</span>
                                <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link v-if="
                    isAdmin &&
                    (pendingRequests?.series || pendingRequests?.clippers)
                " :href="route('admin.requests.series.index')" class="group">
                    <CursorCard
                        className="rounded-2xl border-2 border-primary bg-primary/5 p-8 shadow-sm transition-all hover:shadow-md">
                        <div class="relative z-10">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-primary p-2 text-button-content">
                                        <ArrowRight class="h-6 w-6 -rotate-45" />
                                    </div>
                                    <h3 class="text-2xl font-black">
                                        Pending Requests
                                    </h3>
                                </div>
                                <span class="rounded-full bg-primary px-3 py-1 text-xs font-black text-button-content">
                                    {{
                                        (pendingRequests?.series || 0) +
                                        (pendingRequests?.clippers || 0)
                                    }}
                                </span>
                            </div>
                            <p class="text-sm font-bold tracking-tight text-primary uppercase">
                                {{ pendingRequests?.series }} Series •
                                {{ pendingRequests?.clippers }} Clippers
                            </p>
                            <div class="mt-8 flex items-center font-bold text-primary transition-all group-hover:gap-2">
                                <span>Review Now</span>
                                <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link :href="route('series.index', { filter: 'collected' })" class="group">
                    <CursorCard
                        className="rounded-2xl border border-border-color p-8 shadow-sm transition-all hover:border-info/50 hover:shadow-md bg-component-background">
                        <div class="relative z-10">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-lg bg-info/10 p-2 text-info">
                                    <Layers class="h-6 w-6" />
                                </div>
                                <h3 class="text-2xl font-black">My Series</h3>
                            </div>
                            <p class="max-w-[280px] text-sm leading-relaxed text-muted-content">
                                Track your progress and view your collected
                                series.
                            </p>
                            <div class="mt-8 flex items-center font-bold text-info transition-all group-hover:gap-2">
                                <span>View Collection</span>
                                <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>

                <Link :href="route('collection.clippers')" class="group">
                    <CursorCard
                        className="rounded-2xl border border-border-color p-8 shadow-sm transition-all hover:border-info/50 hover:shadow-md bg-component-background">
                        <div class="relative z-10">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-lg bg-info/10 p-2 text-info">
                                    <CheckCircle class="h-6 w-6" />
                                </div>
                                <h3 class="text-2xl font-black">My Clippers</h3>
                            </div>
                            <p class="max-w-[280px] text-sm leading-relaxed text-muted-content">
                                A dedicated board view of every single clipper
                                you own.
                            </p>
                            <div class="mt-8 flex items-center font-bold text-info transition-all group-hover:gap-2">
                                <span>Open Board View</span>
                                <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </div>
                        </div>
                    </CursorCard>
                </Link>
            </div>

            <CursorCard className="flex-1 rounded-2xl border border-border-color p-6 shadow-sm bg-component-background">
                <div class="relative z-10">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-lg font-bold">Recent Additions</h3>
                        <Link :href="route('series.index')"
                            class="text-xs font-bold text-primary uppercase hover:underline">
                            View All Series
                        </Link>
                    </div>

                    <div class="space-y-4">
                        <div v-for="item in recentSeries" :key="item.id"
                            class="flex items-center gap-4 rounded-lg border border-border-color/50 bg-component-background/50 p-4 backdrop-blur-sm transition-colors hover:bg-[var(--hover-overlay)]">
                            <div
                                class="relative h-12 w-16 overflow-hidden rounded border border-border-color bg-component-background">
                                <Skeleton v-if="!isRecentImageLoaded(item)"
                                    class="absolute inset-0 h-full w-full rounded-none" />
                                <img v-show="isRecentImageLoaded(item)" :src="item.image_data"
                                    :alt="item.name + ' Clipper Lighter Series'" class="h-full w-full object-contain"
                                    @load="markRecentImageLoaded(item)" @error="markRecentImageLoaded(item)" />
                            </div>

                            <div class="flex-1">
                                <Link :href="route('series.show', {
                                    series: item.id,
                                    slug: item.slug,
                                })
                                    " class="text-sm font-bold transition-colors hover:text-primary">
                                    {{ item.name }}
                                </Link>
                                <p class="text-[10px] tracking-tight text-muted-content uppercase">
                                    {{ item.clippers_count }} Design(s)<span class="hidden sm:inline">
                                        • Added by
                                        {{
                                            item.requester?.name || 'System'
                                        }}</span>
                                </p>
                            </div>

                            <Link :href="route('series.show', {
                                series: item.id,
                                slug: item.slug,
                            })
                                "
                                class="rounded border border-primary/20 px-3 py-1 text-[10px] font-black text-primary uppercase transition-all hover:bg-primary hover:text-button-content!">
                                View Series
                            </Link>
                        </div>

                        <div v-if="recentSeries.length === 0"
                            class="py-10 text-center text-sm text-muted-content italic">
                            No series added yet.
                        </div>
                    </div>
                </div>
            </CursorCard>
        </div>
    </AppLayout>
</template>
