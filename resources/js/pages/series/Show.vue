<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import NoteModal from '@/components/modal/NoteModal.vue';
import ConfirmationModal from '@/components/modal/ConfirmationModal.vue';
import { AppPageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCheck, Heart, PencilLine, User as UserIcon, Calendar, Library, CheckCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

interface Clipper {
    id: number;
    series_number: number;
    image_data: string;
}

interface CollectionDetails {
    notes: string | null;
    location_bought: string | null;
    clipper_id: number;
}

interface Series {
    id: string;
    name: string;
    slug?: string;
    custom: boolean;
    image_data: string;
    clippers: Clipper[];
    requester?: { name: string };
    created_at: string;
    last_updated_at: string;
}

const props = defineProps<{
    series: Series;
    // Map of clipper_id -> collection details
    userCollection: Record<number, CollectionDetails>;
    canManageCollection?: boolean;
    collectionOwnerName?: string;
    profileUserId?: string | null;
}>();

// --- Dates ---
const formatDate = (dateStr: string) =>
    new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });

// --- Auth & Admin ---
const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.is_admin);
const canManageCollection = computed(() => !!props.canManageCollection);
const collectionOwnerName = computed(() => props.collectionOwnerName || 'User');
const isReadOnlyProfileView = computed(() => !canManageCollection.value && !!props.profileUserId);

// --- Stats ---
const registeredCount = computed(() => props.series.clippers.length);
const collectedCount = computed(() => Object.keys(props.userCollection).length);
const isFullyCollected = computed(() => collectedCount.value === registeredCount.value && registeredCount.value > 0);

// --- State ---
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const detailsModalOpen = ref(false);
const activeClipper = ref<Clipper | null>(null);

// --- Helpers ---
const isOwned = (clipperId: number) => !!props.userCollection[clipperId];
const getClipperByNumber = (n: number) => props.series.clippers.find(c => c.series_number === n);

// --- Actions ---
const openClipperDetails = (clipper: Clipper) => {
    activeClipper.value = clipper;
    detailsModalOpen.value = true;
};

const toggleCollection = (clipperId: number) => {
    if (!canManageCollection.value) return;
    router.post(route('clippers.toggle', clipperId), {}, { preserveScroll: true });
};

const toggleAll = () => {
    if (!canManageCollection.value) return;
    router.post(route('series.toggle-collection', props.series.id), {}, { preserveScroll: true });
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(route('series.destroy', props.series.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
};
</script>

<template>

    <Head>
        <title>{{ series.name }} | Clipper-MS</title>
    </Head>

    <AppLayout>
        <div class="max-w-7xl mx-auto p-6 space-y-6">
            <div v-if="isReadOnlyProfileView">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight">
                    Currently Viewing For {{ collectionOwnerName }}
                </h1>
            </div>

            <!-- Series Header Card -->
            <div
                class="flex flex-col md:flex-row gap-8 items-start bg-component-background p-4 sm:p-8 rounded-3xl border border-border-color shadow-sm relative overflow-hidden">
                <!-- Background Accent -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>

                <div
                    class="w-full md:w-1/3 aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl border border-border-color bg-primary-background">
                    <img :src="series.image_data" :alt="series.name + ' Clipper Lighter Series'"
                        class="w-full h-full object-cover" />
                </div>

                <div class="flex-1 min-w-0 space-y-6 relative z-10">
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-4xl font-black uppercase tracking-tight">{{ series.name }}</h1>
                            <span v-if="series.custom"
                                class="px-3 py-1 bg-primary/10 text-button-content text-[10px] font-black rounded-full uppercase border border-primary/20">Custom
                                Set</span>
                        </div>

                        <div v-if="!isReadOnlyProfileView" class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-3">
                            <div class="flex items-start gap-2 text-xs">
                                <div class="p-1 rounded-md bg-muted-background mt-0.5">
                                    <UserIcon class="w-3 h-3 text-muted-content" />
                                </div>
                                <div class="leading-tight">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-muted-content">Added
                                        By</p>
                                    <p class="font-bold text-primary-content">{{ series.requester?.name || 'System' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 text-xs">
                                <div class="p-1 rounded-md bg-muted-background mt-0.5">
                                    <Calendar class="w-3 h-3 text-muted-content" />
                                </div>
                                <div class="leading-tight">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-muted-content">
                                        Series Created</p>
                                    <p class="font-bold text-primary-content">{{ formatDate(series.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 text-xs">
                                <div class="p-1 rounded-md bg-muted-background mt-0.5">
                                    <Calendar class="w-3 h-3 text-muted-content" />
                                </div>
                                <div class="leading-tight">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-muted-content">Last
                                        Updated</p>
                                    <p class="font-bold text-primary-content">{{ formatDate(series.last_updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-border-color pt-6">
                        <div v-if="isAdmin"
                            class="p-4 rounded-2xl bg-muted-background/30 border border-border-color/50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black text-muted-content uppercase tracking-widest">System
                                    Status</span>
                                <Library class="w-4 h-4 text-info" />
                            </div>
                            <p class="text-xl font-bold">{{ registeredCount }} / {{ series.custom ? registeredCount : 4
                                }} Registered</p>
                            <div class="w-full bg-muted-background h-1.5 rounded-full mt-2 overflow-hidden">
                                <div class="bg-info h-full transition-all"
                                    :style="{ width: `${(registeredCount / (series.custom ? Math.max(registeredCount, 1) : 4)) * 100}%` }">
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl bg-primary/5 border border-primary/10">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black text-primary uppercase tracking-widest opacity-70">
                                    {{ canManageCollection ? 'Your Collection' : 'Collection Progress' }}
                                </span>
                                <CheckCircle class="w-4 h-4 text-primary" />
                            </div>
                            <p class="text-xl font-bold text-primary">
                                {{ collectedCount }} / {{ series.custom ? registeredCount : 4 }} {{ canManageCollection
                                    ? 'Owned' : 'Collected' }}
                            </p>
                            <div class="w-full bg-primary/10 h-1.5 rounded-full mt-2 overflow-hidden">
                                <div class="bg-primary h-full transition-all"
                                    :style="{ width: `${(collectedCount / (series.custom ? Math.max(registeredCount, 1) : 4)) * 100}%` }">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons - RESTORED TO GRID -->
                    <div v-if="canManageCollection" class="pt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button @click="toggleAll"
                            class="w-full px-6 py-3 rounded-xl font-black uppercase text-sm transition-all flex items-center justify-center gap-2 group/btn"
                            :class="isFullyCollected ? 'bg-error/10 text-error hover:bg-error hover:text-button-content!  border border-error/20 shadow-sm' : 'bg-primary text-button-content hover:bg-primary hover:text-button-content!  shadow-lg shadow-primary/20'">
                            <CheckCheck v-if="isFullyCollected" class="w-4 h-4" />
                            <Heart v-else class="w-4 h-4 fill-current group-hover/btn:scale-110 transition-transform" />
                            {{ isFullyCollected ? 'Uncollect Series' : 'Collect Complete Series' }}
                        </button>

                        <div class="grid grid-cols-2 gap-3">
                            <template v-if="isAdmin">
                                <Link :href="route('series.edit', series.id)"
                                    class="flex items-center justify-center bg-primary text-button-content text-sm font-bold rounded-xl hover:bg-primary hover:text-button-content!  transition-all shadow-lg">
                                    Edit
                                </Link>
                                <button @click="showDeleteModal = true"
                                    class="text-center bg-error/10 text-error text-sm font-bold rounded-xl hover:bg-error hover:text-button-content!  transition-all border border-error/20 shadow-sm">
                                    Delete
                                </button>
                            </template>
                            <template v-else>
                                <Link :href="route('series.request-clippers', series.id)"
                                    class="col-span-2 flex items-center justify-center bg-primary/10 text-primary hover:bg-primary hover:text-button-content!  text-sm font-bold rounded-xl transition-all border border-primary/20 shadow-sm">
                                    Request Clippers
                                </Link>
                            </template>
                        </div>
                    </div>
                    <div v-else class="pt-4">
                        <Link :href="route('series.show', { series: series.id, slug: series.slug })"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-primary/20 bg-primary px-6 py-3 text-sm font-black uppercase tracking-wide text-button-content transition-all hover:bg-primary hover:text-button-content! shadow-lg shadow-primary/20">
                            View Series for Yourself
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Clippers Grid -->
            <div class="grid grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                <template v-for="n in (series.custom ? series.clippers.map(c => c.series_number) : [1, 2, 3, 4])"
                    :key="n">
                    <div v-if="getClipperByNumber(n)" class="group">
                        <div
                            class="bg-component-background p-1 sm:p-4 rounded-2xl border border-border-color shadow-sm transition-all hover:border-primary/50 relative">
                            <div class="flex justify-between mb-4">
                                <span class="hidden sm:block text-xs font-black text-muted-content uppercase p-2">#{{ n
                                    }}</span>

                                <div class="flex gap-0.5 sm:gap-1" :class="{ 'flex-1 justify-center': !canManageCollection }">
                                    <button v-if="canManageCollection && isOwned(getClipperByNumber(n)!.id)"
                                        @click="openClipperDetails(getClipperByNumber(n)!)"
                                        class="p-1 sm:p-2 rounded-full bg-muted-background text-muted-content hover:text-info transition-all"
                                        title="Edit notes/location">
                                        <PencilLine class="w-3 h-3 sm:w-4 sm:h-4" />
                                    </button>

                                    <button v-if="canManageCollection"
                                        @click="toggleCollection(getClipperByNumber(n)!.id)"
                                        class="p-1 sm:p-2 rounded-full transition-all"
                                        :class="isOwned(getClipperByNumber(n)!.id) ? 'text-error bg-error/10' : 'text-muted-content hover:text-primary bg-muted-background'">
                                        <Heart class="w-4 h-4 sm:w-5 sm:h-5"
                                            :fill="isOwned(getClipperByNumber(n)!.id) ? 'currentColor' : 'none'" />
                                    </button>

                                    <div v-else
                                        class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-widest "
                                        :class="isOwned(getClipperByNumber(n)!.id) ? 'bg-success/10 text-success border border-success/20' : 'bg-muted-background text-muted-content border border-border-color'">
                                        {{ isOwned(getClipperByNumber(n)!.id) ? 'Owned' : 'Missing' }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="aspect-[1/4] rounded-xl overflow-hidden border border-border-color bg-primary-background group-hover:scale-[1.02] transition-transform duration-300">
                                <img :src="getClipperByNumber(n)!.image_data"
                                    :alt="series.name + ' #' + n + ' Clipper Lighter'"
                                    class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>

                    <div v-else-if="!series.custom"
                        class="h-full border border-dashed border-border-color rounded-2xl flex flex-col items-center justify-center bg-muted-background/20 opacity-60">
                        <div class="p-1 sm:p-3 rounded-xl bg-muted-background mb-2 sm:mb-3">
                            <Library class="w-4 h-4 sm:w-6 sm:h-6 text-muted-content opacity-20" />
                        </div>
                        <span class="text-[8px] sm:text-xs font-bold uppercase text-muted-content tracking-wide sm:tracking-widest text-center leading-tight">#{{ n }} Missing</span>
                        <Link v-if="isAdmin" :href="route('series.edit', series.id)"
                            class="mt-2 sm:mt-4 text-[7px] sm:text-[10px] font-bold text-primary underline uppercase text-center">Upload Design</Link>
                    </div>
                </template>
            </div>
        </div>

        <NoteModal v-if="canManageCollection" :show="detailsModalOpen" :clipper="activeClipper"
            :initial-notes="activeClipper ? userCollection[activeClipper.id]?.notes : ''"
            :initial-location="activeClipper ? userCollection[activeClipper.id]?.location_bought : ''"
            @close="detailsModalOpen = false" />

        <ConfirmationModal v-model:open="showDeleteModal" :title="`Delete ${series.name}?`"
            description="Are you sure you want to delete this entire series? This will remove all images and collection data for all users. This action cannot be undone."
            confirm-text="Delete Series" :loading="isDeleting" @confirm="confirmDelete" />
    </AppLayout>
</template>
