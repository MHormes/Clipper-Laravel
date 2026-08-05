<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import SeriesCard from '@/components/series/SeriesCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArrowLeft, CheckCircle2, Loader2, Package, Search, Sparkles, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

interface Profile {
    id: string;
    name: string;
    collected_clippers_count: number;
    completed_series_count: number;
    contributions_count: number;
    following_count: number;
    followers_count: number;
    can_follow: boolean;
    is_following: boolean;
}

interface SeriesData {
    data: Array<{
        id: string;
        name: string;
        image_data: string;
        custom: boolean;
        clippers_count: number;
        collected_clippers_count: number;
    }>;
    links: Array<any>;
    total: number;
}

const props = defineProps<{
    profile: Profile;
    series: SeriesData;
    filters?: {
        search?: string;
        filter?: 'all' | 'completed';
    };
}>();

const search = ref(props.filters?.search ?? '');
const completedOnly = ref((props.filters?.filter ?? 'all') === 'completed');

watch(
    () => props.filters,
    (newFilters) => {
        search.value = newFilters?.search ?? '';
        completedOnly.value = (newFilters?.filter ?? 'all') === 'completed';
    },
    { deep: true }
);

const updateResults = () => {
    router.get(
        route('users.show', props.profile.id),
        {
            search: search.value,
            filter: completedOnly.value ? 'completed' : 'all',
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

let timeout: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (timeout) {
        clearTimeout(timeout);
    }

    timeout = setTimeout(updateResults, 300);
});

watch(completedOnly, () => {
    updateResults();
});

const initial = computed(() => props.profile.name.charAt(0).toUpperCase());

const followLoading = ref(false);
const toggleFollow = () => {
    followLoading.value = true;
    router.post(
        route('users.toggle-follow', props.profile.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                followLoading.value = false;
            },
        }
    );
};
</script>

<template>

    <Head :title="`${profile.name} | User Profile`" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-4 md:p-6">
            <div class="mb-6">
                <Link :href="route('users.index')"
                    class="inline-flex items-center gap-2 text-sm font-bold text-muted-content hover:text-primary transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                    BACK TO FIND USERS
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[320px,1fr] gap-6">
                <aside
                    class="rounded-2xl border border-border-color bg-component-background p-5 h-fit lg:sticky lg:top-20">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary text-lg font-black">
                            {{ initial }}
                        </div>
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-black uppercase tracking-tight text-primary-content">{{
                                profile.name }}</h1>
                            <p class="text-xs text-muted-content uppercase tracking-widest font-bold">Collector Profile
                            </p>
                        </div>
                    </div>

                    <button v-if="profile.can_follow" type="button" @click="toggleFollow" :disabled="followLoading"
                        class="mt-4 w-full px-4 py-2.5 rounded-xl font-black text-sm uppercase tracking-wider transition-all border flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
                        :class="profile.is_following
                            ? 'bg-primary-background text-primary-content border-border-color hover:border-primary/40'
                            : 'bg-primary text-button-content border-primary hover:bg-primary hover:text-button-content!'">
                        <Loader2 v-if="followLoading" class="w-4 h-4 animate-spin" />
                        {{ profile.is_following ? 'Unfollow' : 'Follow' }}
                    </button>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-border-color bg-primary-background p-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-content">Following</p>
                            <p class="mt-1 text-xl font-black text-primary-content">{{ profile.following_count }}</p>
                        </div>
                        <div class="rounded-xl border border-border-color bg-primary-background p-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-content">Followers</p>
                            <p class="mt-1 text-xl font-black text-primary-content">{{ profile.followers_count }}</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div class="rounded-xl border border-border-color bg-primary-background p-3">
                            <p
                                class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-content">
                                <Package class="size-3.5" />
                                Collected Clippers
                            </p>
                            <p class="mt-1 text-2xl font-black text-primary-content">{{ profile.collected_clippers_count
                            }}</p>
                        </div>

                        <div class="rounded-xl border border-border-color bg-primary-background p-3">
                            <p
                                class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-content">
                                <CheckCircle2 class="size-3.5" />
                                Completed Series
                            </p>
                            <p class="mt-1 text-2xl font-black text-primary-content">{{ profile.completed_series_count
                            }}</p>
                        </div>

                        <div class="rounded-xl border border-border-color bg-primary-background p-3">
                            <p
                                class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-content">
                                <Sparkles class="size-3.5" />
                                Contributions
                            </p>
                            <p class="mt-1 text-2xl font-black text-primary-content">{{ profile.contributions_count }}
                            </p>
                        </div>
                    </div>
                </aside>

                <section>
                    <div class="grid grid-cols-1 lg:grid-cols-[1fr,auto] items-center gap-4 mb-8">
                        <div>
                            <h2 class="text-3xl font-black uppercase tracking-tight">Collected Series</h2>
                            <p class="text-sm text-muted-content">Browse this user's collected sets and completion
                                progress.</p>
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

                            <button type="button" @click="completedOnly = !completedOnly"
                                class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all border"
                                :class="completedOnly
                                    ? 'bg-primary text-button-content border-primary'
                                    : 'bg-primary-background text-primary-content border-border-color hover:border-primary/40'">
                                {{ completedOnly ? 'SHOW ALL' : 'SHOW COMPLETED ONLY' }}
                            </button>
                        </div>
                    </div>

                    <div class="mb-6 flex items-center gap-2 px-1">
                        <span class="text-[10px] font-black uppercase tracking-widest text-muted-content">
                            Showing <span class="text-primary-content">{{ series.total }}</span> series
                        </span>
                        <div class="ml-2 h-px flex-1 bg-border-color/30"></div>
                    </div>

                    <div v-if="series.data.length > 0"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <SeriesCard v-for="item in series.data" :key="item.id"
                            :href="route('users.series.show', { user: profile.id, series: item.id, slug: (item as any).slug })"
                            :item="{ ...item, collected_count: item.collected_clippers_count, clippers_count: item.clippers_count }" />
                    </div>

                    <div v-else
                        class="w-full flex flex-col items-center justify-center py-24 bg-component-background rounded-3xl border border-dashed border-border-color">
                        <Search class="w-16 h-16 text-muted-content mb-6" />
                        <h3 class="text-3xl font-black mb-3">No Results Found</h3>
                        <button @click="search = ''"
                            class="px-8 py-4 bg-muted-background rounded-2xl font-black transition-all">
                            RESET SEARCH
                        </button>
                    </div>

                    <Pagination :links="series.links" />
                </section>
            </div>
        </div>
    </AppLayout>
</template>
