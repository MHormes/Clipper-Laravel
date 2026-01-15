<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import NoteModal from '@/components/modal/NoteModal.vue';
import ConfirmationModal from '@/components/modal/ConfirmationModal.vue';
import { AppPageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCheck, Heart, PencilLine } from 'lucide-vue-next';
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
    custom: boolean;
    image_data: string;
    clippers: Clipper[];
    requester?: { name: string };
}

const props = defineProps<{
    series: Series;
    // Map of clipper_id -> collection details
    userCollection: Record<number, CollectionDetails>;
}>();

// --- Auth & Admin ---
const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

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
    router.post(route('clippers.toggle', clipperId), {}, { preserveScroll: true });
};

const toggleAll = () => {
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
    <Head :title="series.name" />

    <AppLayout>
        <div class="max-w-7xl mx-auto p-6 space-y-8">
            <div class="flex flex-col md:flex-row gap-8 items-start bg-white dark:bg-[#161615] p-8 rounded-3xl border border-sidebar-border relative">
                
                <div v-if="isAdmin" class="absolute top-8 right-8 flex gap-2">
                    <Link :href="route('series.edit', series.id)" class="px-4 py-2 bg-orange-600 text-white text-sm font-bold rounded-xl hover:bg-orange-700 transition-all shadow-lg">
                        Edit Series
                    </Link>
                    <button @click="showDeleteModal = true" class="px-4 py-2 bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white text-sm font-bold rounded-xl transition-all border border-red-600/20 shadow-sm">
                        Delete
                    </button>
                </div>

                <div class="w-full md:w-1/3 aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-black">
                    <img :src="series.image_data" class="w-full h-full object-cover" />
                </div>

                <div class="flex-1 space-y-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-4xl font-black uppercase tracking-tight">{{ series.name }}</h1>
                            <span v-if="series.custom" class="px-3 py-1 bg-orange-100 text-orange-700 text-[10px] font-black rounded-full uppercase">Custom Set</span>
                        </div>
                        <p class="text-sm text-muted-foreground font-medium">
                            Added by <span class="text-foreground font-bold">{{ series.requester?.name || 'System' }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-sidebar-border pt-6">
                        <div v-if="isAdmin">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">System Status</span>
                            <p class="text-xl font-bold">{{ registeredCount }} / {{ series.custom ? registeredCount : 4 }} Registered</p>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full mt-2">
                                <div class="bg-blue-500 h-full transition-all" :style="{ width: `${(registeredCount / (series.custom ? Math.max(registeredCount, 1) : 4)) * 100}%` }"></div>
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Your Collection</span>
                            <p class="text-xl font-bold text-orange-600">{{ collectedCount }} / {{ series.custom ? registeredCount : 4 }} Owned</p>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full mt-2">
                                <div class="bg-orange-500 h-full transition-all" :style="{ width: `${(collectedCount / (series.custom ? Math.max(registeredCount, 1) : 4)) * 100}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button @click="toggleAll" class="w-full sm:w-auto px-6 py-3 rounded-xl font-black uppercase text-sm transition-all flex items-center justify-center gap-2 group/btn"
                            :class="isFullyCollected ? 'bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white border border-red-600/20 shadow-sm' : 'bg-orange-600 text-white hover:bg-orange-700 shadow-lg shadow-orange-600/20'">
                            <CheckCheck v-if="isFullyCollected" class="w-4 h-4" />
                            <Heart v-else class="w-4 h-4 fill-current group-hover/btn:scale-110 transition-transform" />
                            {{ isFullyCollected ? 'Uncollect Series' : 'Collect Complete Series' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                
                <template v-for="n in (series.custom ? series.clippers.map(c => c.series_number) : [1, 2, 3, 4])" :key="n">
                    <div v-if="getClipperByNumber(n)" class="group">
                        <div class="bg-white dark:bg-[#161615] p-4 rounded-2xl border-2 border-sidebar-border shadow-sm transition-all hover:border-orange-500 relative">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-xs font-black text-gray-400 uppercase">#{{ n }}</span>
                                
                                <div class="flex gap-1">
                                    <button 
                                        v-if="isOwned(getClipperByNumber(n)!.id)"
                                        @click="openClipperDetails(getClipperByNumber(n)!)"
                                        class="p-2 rounded-full bg-gray-50 dark:bg-white/5 text-gray-400 hover:text-blue-500 transition-all"
                                        title="Edit notes/location"
                                    >
                                        <PencilLine class="w-4 h-4" />
                                    </button>

                                    <button @click="toggleCollection(getClipperByNumber(n)!.id)"
                                        class="p-2 rounded-full transition-all" 
                                        :class="isOwned(getClipperByNumber(n)!.id) ? 'text-red-500 bg-red-50 dark:bg-red-500/10' : 'text-gray-300 hover:text-orange-500 bg-gray-50 dark:bg-white/5'">
                                        <Heart class="w-5 h-5" :fill="isOwned(getClipperByNumber(n)!.id) ? 'currentColor' : 'none'" />
                                    </button>
                                </div>
                            </div>

                            <div class="aspect-[1/4] rounded-xl overflow-hidden border border-gray-100 dark:border-gray-900 bg-white dark:bg-black">
                                <img :src="getClipperByNumber(n)!.image_data" class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>

                    <div v-else-if="!series.custom" class="h-full min-h-[320px] border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl flex flex-col items-center justify-center bg-gray-50/50 dark:bg-white/5 opacity-40">
                        <span class="text-xs font-bold uppercase text-gray-400 tracking-widest">#{{ n }} Missing</span>
                        <Link v-if="isAdmin" :href="route('series.edit', series.id)" class="mt-4 text-[10px] font-bold text-orange-600 underline uppercase">Upload Design</Link>
                    </div>
                </template>
            </div>
        </div>

        <NoteModal 
            :show="detailsModalOpen"
            :clipper="activeClipper"
            :initial-notes="activeClipper ? userCollection[activeClipper.id]?.notes : ''"
            :initial-location="activeClipper ? userCollection[activeClipper.id]?.location_bought : ''"
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