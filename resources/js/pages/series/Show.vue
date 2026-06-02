<script setup lang="ts">
import ConfirmationModal from '@/components/modal/ConfirmationModal.vue';
import NoteModal from '@/components/modal/NoteModal.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { AppPageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCheck,
    CheckCircle,
    Heart,
    Library,
    PencilLine,
    User as UserIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
    new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

// --- Auth & Admin ---
const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.is_admin);
const canManageCollection = computed(() => !!props.canManageCollection);
const collectionOwnerName = computed(() => props.collectionOwnerName || 'User');
const isReadOnlyProfileView = computed(
    () => !canManageCollection.value && !!props.profileUserId,
);

// --- Stats ---
const registeredCount = computed(() => props.series.clippers.length);
const collectedCount = computed(() => Object.keys(props.userCollection).length);
const isFullyCollected = computed(
    () =>
        collectedCount.value === registeredCount.value &&
        registeredCount.value > 0,
);

// --- State ---
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const detailsModalOpen = ref(false);
const activeClipper = ref<Clipper | null>(null);
const seriesImageLoaded = ref(false);
const clipperImagesLoaded = ref(false);
let seriesPreloadRun = 0;
let clipperPreloadRun = 0;

// --- Helpers ---
const clipperNumbers = computed(() =>
    props.series.custom
        ? props.series.clippers.map((c) => c.series_number)
        : [1, 2, 3, 4],
);
const clippersByNumber = computed(
    () =>
        new Map(
            props.series.clippers.map((clipper) => [
                clipper.series_number,
                clipper,
            ]),
        ),
);
const isOwned = (clipperId: number) => !!props.userCollection[clipperId];
const getClipperByNumber = (n: number) => clippersByNumber.value.get(n);
const clipperSlots = computed(() =>
    clipperNumbers.value.map((number) => ({
        number,
        clipper: getClipperByNumber(number),
    })),
);

const preloadImage = (src: string) =>
    new Promise<void>((resolve) => {
        if (!src || typeof window === 'undefined') {
            resolve();
            return;
        }

        const image = new window.Image();
        image.onload = () => resolve();
        image.onerror = () => resolve();
        image.src = src;

        if (image.complete) {
            resolve();
        }
    });

watch(
    () => `${props.series.id}:${props.series.image_data}`,
    async () => {
        const run = ++seriesPreloadRun;
        seriesImageLoaded.value = false;

        await preloadImage(props.series.image_data);

        if (run === seriesPreloadRun) {
            seriesImageLoaded.value = true;
        }
    },
    { immediate: true },
);

watch(
    () =>
        `${props.series.id}:${props.series.clippers
            .map((clipper) => `${clipper.id}:${clipper.image_data}`)
            .join('|')}`,
    async () => {
        const run = ++clipperPreloadRun;
        clipperImagesLoaded.value = props.series.clippers.length === 0;

        await Promise.all(
            props.series.clippers.map((clipper) =>
                preloadImage(clipper.image_data),
            ),
        );

        if (run === clipperPreloadRun) {
            clipperImagesLoaded.value = true;
        }
    },
    { immediate: true },
);

// --- Actions ---
const openClipperDetails = (clipper: Clipper) => {
    activeClipper.value = clipper;
    detailsModalOpen.value = true;
};

const toggleCollection = (clipperId: number) => {
    if (!canManageCollection.value) return;
    router.post(
        route('clippers.toggle', clipperId),
        {},
        { preserveScroll: true },
    );
};

const toggleAll = () => {
    if (!canManageCollection.value) return;
    router.post(
        route('series.toggle-collection', props.series.id),
        {},
        { preserveScroll: true },
    );
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(route('series.destroy', props.series.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};
</script>

<template>
    <Head>
        <title>{{ series.name }} | Clipper-MS</title>
    </Head>

    <AppLayout>
        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <div v-if="isReadOnlyProfileView">
                <h1
                    class="text-3xl font-black tracking-tight uppercase md:text-4xl"
                >
                    Currently Viewing For {{ collectionOwnerName }}
                </h1>
            </div>

            <!-- Series Header Card -->
            <div
                class="relative flex flex-col items-start gap-8 overflow-hidden rounded-3xl border border-border-color bg-component-background p-4 shadow-sm sm:p-8 md:flex-row"
            >
                <!-- Background Accent -->
                <div
                    class="absolute top-0 right-0 -mt-32 -mr-32 h-64 w-64 rounded-full bg-primary/5 blur-3xl"
                ></div>

                <div
                    class="relative aspect-[4/3] w-full overflow-hidden rounded-2xl border border-border-color bg-primary-background shadow-2xl md:w-1/3"
                >
                    <Skeleton
                        v-if="!seriesImageLoaded"
                        class="absolute inset-0 h-full w-full rounded-2xl"
                    />
                    <img
                        v-show="seriesImageLoaded"
                        :src="series.image_data"
                        :alt="series.name + ' Clipper Lighter Series'"
                        class="h-full w-full object-cover"
                    />
                </div>

                <div class="relative z-10 min-w-0 flex-1 space-y-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1
                                class="text-4xl font-black tracking-tight uppercase"
                            >
                                {{ series.name }}
                            </h1>
                            <span
                                v-if="series.custom"
                                class="rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-[10px] font-black text-button-content uppercase"
                                >Custom Set</span
                            >
                        </div>

                        <div
                            v-if="!isReadOnlyProfileView"
                            class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2"
                        >
                            <div class="flex items-start gap-2 text-xs">
                                <div
                                    class="mt-0.5 rounded-md bg-muted-background p-1"
                                >
                                    <UserIcon
                                        class="h-3 w-3 text-muted-content"
                                    />
                                </div>
                                <div class="leading-tight">
                                    <p
                                        class="text-[10px] font-black tracking-widest text-muted-content uppercase"
                                    >
                                        Added By
                                    </p>
                                    <p class="font-bold text-primary-content">
                                        {{ series.requester?.name || 'System' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 text-xs">
                                <div
                                    class="mt-0.5 rounded-md bg-muted-background p-1"
                                >
                                    <Calendar
                                        class="h-3 w-3 text-muted-content"
                                    />
                                </div>
                                <div class="leading-tight">
                                    <p
                                        class="text-[10px] font-black tracking-widest text-muted-content uppercase"
                                    >
                                        Series Created
                                    </p>
                                    <p class="font-bold text-primary-content">
                                        {{ formatDate(series.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 text-xs">
                                <div
                                    class="mt-0.5 rounded-md bg-muted-background p-1"
                                >
                                    <Calendar
                                        class="h-3 w-3 text-muted-content"
                                    />
                                </div>
                                <div class="leading-tight">
                                    <p
                                        class="text-[10px] font-black tracking-widest text-muted-content uppercase"
                                    >
                                        Last Updated
                                    </p>
                                    <p class="font-bold text-primary-content">
                                        {{ formatDate(series.last_updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 border-t border-border-color pt-6 sm:grid-cols-2"
                    >
                        <div
                            v-if="isAdmin"
                            class="rounded-2xl border border-border-color/50 bg-muted-background/30 p-4"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <span
                                    class="text-[10px] font-black tracking-widest text-muted-content uppercase"
                                    >System Status</span
                                >
                                <Library class="h-4 w-4 text-info" />
                            </div>
                            <p class="text-xl font-bold">
                                {{ registeredCount }} /
                                {{
                                    series.custom ? registeredCount : 4
                                }}
                                Registered
                            </p>
                            <div
                                class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted-background"
                            >
                                <div
                                    class="h-full bg-info transition-all"
                                    :style="{
                                        width: `${(registeredCount / (series.custom ? Math.max(registeredCount, 1) : 4)) * 100}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-primary/10 bg-primary/5 p-4"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <span
                                    class="text-[10px] font-black tracking-widest text-primary uppercase opacity-70"
                                >
                                    {{
                                        canManageCollection
                                            ? 'Your Collection'
                                            : 'Collection Progress'
                                    }}
                                </span>
                                <CheckCircle class="h-4 w-4 text-primary" />
                            </div>
                            <p class="text-xl font-bold text-primary">
                                {{ collectedCount }} /
                                {{ series.custom ? registeredCount : 4 }}
                                {{
                                    canManageCollection ? 'Owned' : 'Collected'
                                }}
                            </p>
                            <div
                                class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-primary/10"
                            >
                                <div
                                    class="h-full bg-primary transition-all"
                                    :style="{
                                        width: `${(collectedCount / (series.custom ? Math.max(registeredCount, 1) : 4)) * 100}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons - RESTORED TO GRID -->
                    <div
                        v-if="canManageCollection"
                        class="grid grid-cols-1 gap-3 pt-4 sm:grid-cols-2"
                    >
                        <button
                            @click="toggleAll"
                            class="group/btn flex w-full items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-black uppercase transition-all"
                            :class="
                                isFullyCollected
                                    ? 'border border-error/20 bg-error/10 text-error shadow-sm hover:bg-error hover:text-button-content!'
                                    : 'bg-primary text-button-content shadow-lg shadow-primary/20 hover:bg-primary hover:text-button-content!'
                            "
                        >
                            <CheckCheck
                                v-if="isFullyCollected"
                                class="h-4 w-4"
                            />
                            <Heart
                                v-else
                                class="h-4 w-4 fill-current transition-transform group-hover/btn:scale-110"
                            />
                            {{
                                isFullyCollected
                                    ? 'Uncollect Series'
                                    : 'Collect Complete Series'
                            }}
                        </button>

                        <div class="grid grid-cols-2 gap-3">
                            <template v-if="isAdmin">
                                <Link
                                    :href="route('series.edit', series.id)"
                                    class="flex items-center justify-center rounded-xl bg-primary text-sm font-bold text-button-content shadow-lg transition-all hover:bg-primary hover:text-button-content!"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="showDeleteModal = true"
                                    class="rounded-xl border border-error/20 bg-error/10 text-center text-sm font-bold text-error shadow-sm transition-all hover:bg-error hover:text-button-content!"
                                >
                                    Delete
                                </button>
                            </template>
                            <template v-else>
                                <Link
                                    :href="
                                        route(
                                            'series.request-clippers',
                                            series.id,
                                        )
                                    "
                                    class="col-span-2 flex items-center justify-center rounded-xl border border-primary/20 bg-primary/10 text-sm font-bold text-primary shadow-sm transition-all hover:bg-primary hover:text-button-content!"
                                >
                                    Request Clippers
                                </Link>
                            </template>
                        </div>
                    </div>
                    <div v-else class="pt-4">
                        <Link
                            :href="
                                route('series.show', {
                                    series: series.id,
                                    slug: series.slug,
                                })
                            "
                            class="inline-flex w-full items-center justify-center rounded-xl border border-primary/20 bg-primary px-6 py-3 text-sm font-black tracking-wide text-button-content uppercase shadow-lg shadow-primary/20 transition-all hover:bg-primary hover:text-button-content!"
                        >
                            View Series for Yourself
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Clippers Grid -->
            <div class="grid grid-cols-4 gap-4 lg:grid-cols-6 xl:grid-cols-8">
                <template v-for="slot in clipperSlots" :key="slot.number">
                    <div v-if="slot.clipper" class="group">
                        <div
                            class="relative rounded-2xl border border-border-color bg-component-background p-1 shadow-sm transition-all hover:border-primary/50 sm:p-4"
                        >
                            <div class="mb-4 flex items-center">
                                <span
                                    class="hidden p-2 text-xs font-black text-muted-content uppercase md:block"
                                    >#{{ slot.number }}</span
                                >

                                <!-- canManageCollection: spread on small (pencil left, heart right), grouped on md+ -->
                                <div
                                    v-if="canManageCollection"
                                    class="flex flex-1 flex-row-reverse flex-wrap items-center justify-between gap-2 md:ml-auto md:flex-none md:flex-row md:justify-end md:gap-1"
                                >
                                    <!-- Heart first in DOM: right side on small (row-reverse), top if stacking -->
                                    <button
                                        @click="
                                            toggleCollection(slot.clipper.id)
                                        "
                                        class="rounded-full p-2 transition-all"
                                        :class="
                                            isOwned(slot.clipper.id)
                                                ? 'bg-error/10 text-error'
                                                : 'bg-muted-background text-muted-content hover:text-primary'
                                        "
                                    >
                                        <Heart
                                            class="h-6 w-6 md:h-5 md:w-5"
                                            :fill="
                                                isOwned(slot.clipper.id)
                                                    ? 'currentColor'
                                                    : 'none'
                                            "
                                        />
                                    </button>
                                    <!-- Pencil second in DOM: left side on small (row-reverse), below heart if stacking; first on md+ via order -->
                                    <button
                                        v-if="isOwned(slot.clipper.id)"
                                        @click="
                                            openClipperDetails(slot.clipper)
                                        "
                                        class="rounded-full bg-muted-background p-2 text-muted-content transition-all hover:text-info md:order-first"
                                        title="Edit notes/location"
                                    >
                                        <PencilLine
                                            class="h-5 w-5 md:h-4 md:w-4"
                                        />
                                    </button>
                                    <!-- Invisible spacer: keeps uncollected cards same height as collected (stacked) cards below md -->
                                    <div
                                        v-else
                                        class="invisible p-2 md:hidden"
                                        aria-hidden="true"
                                    >
                                        <div class="h-5 w-5" />
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="mx-auto rounded-full px-2 py-1 text-[10px] font-black tracking-widest uppercase"
                                    :class="
                                        isOwned(slot.clipper.id)
                                            ? 'border border-success/20 bg-success/10 text-success'
                                            : 'border border-border-color bg-muted-background text-muted-content'
                                    "
                                >
                                    {{
                                        isOwned(slot.clipper.id)
                                            ? 'Owned'
                                            : 'Missing'
                                    }}
                                </div>
                            </div>

                            <div
                                class="relative aspect-[1/4] overflow-hidden rounded-xl border border-border-color bg-primary-background transition-transform duration-300 group-hover:scale-[1.02]"
                            >
                                <Skeleton
                                    v-if="!clipperImagesLoaded"
                                    class="absolute inset-0 h-full w-full rounded-xl"
                                />
                                <img
                                    v-show="clipperImagesLoaded"
                                    :src="slot.clipper.image_data"
                                    :alt="
                                        series.name +
                                        ' #' +
                                        slot.number +
                                        ' Clipper Lighter'
                                    "
                                    class="h-full w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="!series.custom"
                        class="flex h-full flex-col items-center justify-center rounded-2xl border border-dashed border-border-color bg-muted-background/20 opacity-60"
                    >
                        <div
                            class="mb-2 rounded-xl bg-muted-background p-1 sm:mb-3 sm:p-3"
                        >
                            <Library
                                class="h-4 w-4 text-muted-content opacity-20 sm:h-6 sm:w-6"
                            />
                        </div>
                        <span
                            class="text-center text-[8px] leading-tight font-bold tracking-wide text-muted-content uppercase sm:text-xs sm:tracking-widest"
                            >#{{ slot.number }} Missing</span
                        >
                        <Link
                            v-if="isAdmin"
                            :href="route('series.edit', series.id)"
                            class="mt-2 text-center text-[7px] font-bold text-primary uppercase underline sm:mt-4 sm:text-[10px]"
                            >Upload Design</Link
                        >
                    </div>
                </template>
            </div>
        </div>

        <NoteModal
            v-if="canManageCollection"
            :show="detailsModalOpen"
            :clipper="activeClipper"
            :initial-notes="
                activeClipper ? userCollection[activeClipper.id]?.notes : ''
            "
            :initial-location="
                activeClipper
                    ? userCollection[activeClipper.id]?.location_bought
                    : ''
            "
            @close="detailsModalOpen = false"
        />

        <ConfirmationModal
            v-model:open="showDeleteModal"
            :title="`Delete ${series.name}?`"
            description="Are you sure you want to delete this entire series? This will remove all images and collection data for all users. This action cannot be undone."
            confirm-text="Delete Series"
            :loading="isDeleting"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
