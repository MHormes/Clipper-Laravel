<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { AppPageProps } from '@/types';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { Heart, CheckCheck } from 'lucide-vue-next';

interface Clipper {
    id: number;
    series_number: number;
    image_data: string;
}

interface Requester {
    name: string;
}

interface Series {
    id: string;
    name: string;
    custom: boolean;
    image_data: string;
    clippers: Clipper[];
    requester?: Requester;
}

const props = defineProps<{
    series: Series;
    userCollection: number[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const registeredCount = computed(() => props.series.clippers.length);
const collectedCount = computed(() => props.userCollection.length);

const showDeleteModal = ref(false);
const isDeleting = ref(false);

const toggleCollection = (clipperId: number) => {
    router.post(route('clippers.toggle', clipperId), {}, {
        preserveScroll: true,
    });
};

const toggleAll = () => {
    router.post(route('series.toggle-collection', props.series.id), {}, {
        preserveScroll: true,
    });
};

const deleteSeries = () => {
    showDeleteModal.value = true;
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

const getClipperByNumber = (n: number) => {
    return props.series.clippers.find(c => c.series_number === n);
};
</script>

<template>

    <Head :title="series.name" />

    <AppLayout>
        <div class="max-w-7xl mx-auto p-6 space-y-8">
            <div
                class="flex flex-col md:flex-row gap-8 items-start bg-white dark:bg-[#161615] p-8 rounded-3xl border border-sidebar-border relative">

                <div v-if="isAdmin" class="absolute top-8 right-8 flex gap-2">
                    <Link :href="route('series.edit', series.id)"
                        class="px-4 py-2 bg-orange-600 text-white text-sm font-bold rounded-xl hover:bg-orange-700 transition-all shadow-lg">
                        Edit Series
                    </Link>
                    <button @click="deleteSeries"
                        class="px-4 py-2 bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white text-sm font-bold rounded-xl transition-all border border-red-600/20 shadow-sm">
                        Delete
                    </button>
                </div>

                <div
                    class="w-full md:w-1/3 aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-black">
                    <img :src="series.image_data" class="w-full h-full object-cover" />
                </div>

                <div class="flex-1 space-y-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-4xl font-black uppercase tracking-tight">{{ series.name }}</h1>
                            <span v-if="series.custom"
                                class="px-3 py-1 bg-orange-100 text-orange-700 text-[10px] font-black rounded-full uppercase">
                                Custom Set
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground font-medium">
                            Added by <span class="text-foreground font-bold">{{ series.requester?.name || 'System'
                                }}</span>
                        </p>
                        <p v-if="isAdmin"
                            class="text-sm text-muted-foreground mt-1 font-mono uppercase tracking-tighter">
                            Series ID: {{ series.id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-sidebar-border pt-6">
                        <div v-if="isAdmin">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">System
                                Status</span>
                            <p class="text-xl font-bold">{{ registeredCount }} / {{ series.custom ? registeredCount : 4 }} Registered</p>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full mt-2">
                                <div class="bg-blue-500 h-full transition-all"
                                    :style="{ width: `${(registeredCount / (series.custom ?Math.max(registeredCount, 1) : 4)) * 100}%` }"></div>
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Your
                                Collection</span>
                            <p class="text-xl font-bold text-orange-600">{{ collectedCount }} / {{ series.custom ? registeredCount : 4 }} Owned</p>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full mt-2">
                                <div class="bg-orange-500 h-full transition-all"
                                    :style="{ width: `${(collectedCount / (series.custom ? registeredCount : 4)) * 100}%` }">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button 
                            @click="toggleAll"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl font-black uppercase text-sm transition-all flex items-center justify-center gap-2 group/btn"
                            :class="collectedCount === registeredCount 
                                ? 'bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white border border-red-600/20 shadow-sm' 
                                : 'bg-orange-600 text-white hover:bg-orange-700 shadow-lg shadow-orange-600/20'"
                        >
                            <CheckCheck v-if="collectedCount === registeredCount" class="w-4 h-4" />
                            <Heart v-else class="w-4 h-4 fill-current group-hover/btn:scale-110 transition-transform" />
                            {{ collectedCount === registeredCount ? 'Uncollect Series' : 'Collect Complete Series' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                <!-- Custom Series: Loop directly through clippers -->
                <template v-if="series.custom">
                    <div v-for="clipper in series.clippers" :key="clipper.id" class="group">
                        <div class="bg-white dark:bg-[#161615] p-4 rounded-2xl border-2 border-sidebar-border shadow-sm transition-all hover:border-orange-500">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-xs font-black text-gray-400 uppercase">#{{ clipper.series_number }}</span>
                                <button @click="toggleCollection(clipper.id)"
                                    class="p-2 rounded-full transition-all" :class="userCollection.includes(clipper.series_number)
                                        ? 'text-red-500 bg-red-50 dark:bg-red-500/10'
                                        : 'text-gray-300 hover:text-orange-500 bg-gray-50 dark:bg-white/5'">
                                    <Heart class="w-5 h-5" :fill="userCollection.includes(clipper.series_number) ? 'currentColor' : 'none'" />
                                </button>
                            </div>
                            <div class="aspect-[1/4] rounded-xl overflow-hidden border border-gray-100 dark:border-gray-900 bg-white dark:bg-black">
                                <img :src="clipper.image_data" class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Standard Series: Show fixed 4 slots -->
                <template v-else>
                    <div v-for="n in 4" :key="n" class="group">
                        <div v-if="getClipperByNumber(n)"
                            class="bg-white dark:bg-[#161615] p-4 rounded-2xl border-2 border-sidebar-border shadow-sm transition-all hover:border-orange-500">
    
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-xs font-black text-gray-400 uppercase">#{{ n }}</span>
    
                                <button @click="toggleCollection(getClipperByNumber(n)!.id)"
                                    class="p-2 rounded-full transition-all" :class="userCollection.includes(n)
                                        ? 'text-red-500 bg-red-50 dark:bg-red-500/10'
                                        : 'text-gray-300 hover:text-orange-500 bg-gray-50 dark:bg-white/5'">
                                    <Heart class="w-5 h-5" :fill="userCollection.includes(n) ? 'currentColor' : 'none'" />
                                </button>
                            </div>
    
                            <div
                                class="aspect-[1/4] rounded-xl overflow-hidden border border-gray-100 dark:border-gray-900 bg-white dark:bg-black">
                                <img :src="getClipperByNumber(n)!.image_data"
                                    class="w-full h-full object-cover" />
                            </div>
                        </div>
    
                        <div v-else
                            class="h-full min-h-[320px] border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl flex flex-col items-center justify-center bg-gray-50/50 dark:bg-white/5 transition-all"
                            :class="isAdmin ? 'hover:border-orange-500/50 hover:bg-orange-50/10' : 'opacity-40'">
                            <span class="text-xs font-bold uppercase text-gray-400 tracking-widest">#{{ n }} Missing</span>
                            <Link v-if="isAdmin" :href="route('series.edit', series.id)"
                                class="mt-4 text-[10px] font-bold text-orange-600 underline uppercase">
                                Upload Design
                            </Link>
                        </div>
                    </div>
                </template>
            </div>
        </div>

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