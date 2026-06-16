<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';
import { Clock, Check, X, ClipboardCheck, Loader2 } from 'lucide-vue-next';
import ConfirmationModal from '@/components/modal/ConfirmationModal.vue';
import DeclineModal from '@/components/modal/DeclineModal.vue';

defineProps<{
    groupedClippers: Record<string, Array<{
        id: string;
        series_id: string;
        series_number: number;
        image_data: string;
        pending_image_data: string | null;
        created_at: string;
        requester: { name: string };
        series: { name: string; image_data: string };
    }>>;
}>();

const form = useForm({
    decline_reason: '' as string,
});

const showAcceptModal = ref(false);
const showDeclineModal = ref(false);
const pendingActionId = ref<string | null>(null);

const acceptClipper = (id: string) => {
    pendingActionId.value = id;
    showAcceptModal.value = true;
};

const confirmAccept = () => {
    if (!pendingActionId.value) return;
    form.post(route('admin.requests.clippers.accept', pendingActionId.value));
    showAcceptModal.value = false;
};

const declineClipper = (id: string) => {
    pendingActionId.value = id;
    showDeclineModal.value = true;
};

const confirmDecline = (reason: string) => {
    if (!pendingActionId.value) return;
    form.decline_reason = reason;
    form.delete(route('admin.requests.clippers.decline', pendingActionId.value));
    showDeclineModal.value = false;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric'
    });
};
</script>

<template>

    <Head title="Pending Clipper Requests" />
    <AppLayout>
        <div class="max-w-7xl mx-auto p-4 md:p-6">
            <div class="flex items-center gap-4 mb-10">
                <div class="p-3 rounded-2xl bg-primary/10 text-primary">
                    <ClipboardCheck class="w-8 h-8" />
                </div>
                <div>
                    <h1 class="text-4xl font-black uppercase tracking-tighter">Clipper Requests</h1>
                    <p class="text-muted-content font-bold uppercase tracking-widest text-xs mt-1">Review additions to
                        existing series</p>
                </div>
            </div>

            <div v-if="Object.keys(groupedClippers).length === 0"
                class="bg-component-background rounded-3xl p-20 border border-border-color text-center shadow-sm relative overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl pointer-events-none">
                </div>
                <div class="relative z-10">
                    <div class="bg-primary/10 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <Clock class="w-10 h-10 text-primary" />
                    </div>
                    <h2 class="text-2xl font-black uppercase tracking-tight mb-2 text-primary-content">No Pending
                        Clippers</h2>
                    <p class="text-muted-content font-medium max-w-md mx-auto uppercase text-xs tracking-widest">All
                        clipper requests have been processed. Great job!</p>
                </div>
            </div>

            <div v-else class="space-y-12">
                <div v-for="(clippers, seriesId) in groupedClippers" :key="seriesId" class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl overflow-hidden border border-border-color shadow-sm bg-component-background">
                            <img :src="clippers[0].series.image_data" class="w-full h-full object-cover" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-tight leading-none text-primary-content">{{
                                clippers[0].series.name }}</h3>
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-content mt-1">{{
                                clippers.length }} Pending Requests</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                        <template v-for="clipper in clippers" :key="clipper.id">
                        <!-- Replacement: two boxes with arrow -->
                        <template v-if="clipper.pending_image_data">
                            <div class="relative col-span-2 bg-component-background rounded-2xl border border-border-color overflow-hidden group/item shadow-sm hover:border-primary/30 transition-all">
                                <Transition enter-active-class="transition-opacity duration-200"
                                    enter-from-class="opacity-0" leave-active-class="transition-opacity duration-200"
                                    leave-to-class="opacity-0">
                                    <div v-if="form.processing && pendingActionId === clipper.id"
                                        class="absolute inset-0 z-20 bg-component-background/80 backdrop-blur-sm flex items-center justify-center rounded-2xl">
                                        <Loader2 class="w-6 h-6 text-primary animate-spin" />
                                    </div>
                                </Transition>

                                <div class="p-3 flex items-center gap-3">
                                    <div class="flex-1 rounded-xl overflow-hidden border border-border-color">
                                        <div class="aspect-[1/4] relative bg-media-bg">
                                            <img :src="clipper.image_data"
                                                class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover/item:scale-105" />
                                        </div>
                                        <div class="px-2 py-1 text-center">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-muted-content">Current</span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-muted-content font-bold text-sm">→</div>
                                    <div class="flex-1 rounded-xl overflow-hidden border border-warning/50">
                                        <div class="aspect-[1/4] relative bg-media-bg">
                                            <img :src="clipper.pending_image_data"
                                                class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover/item:scale-105" />
                                        </div>
                                        <div class="px-2 py-1 text-center">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-warning">New</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-3 pb-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="px-1.5 py-0.5 rounded bg-warning/15 text-[8px] font-black uppercase tracking-widest text-warning">Replacement</span>
                                        <span class="px-2 py-0.5 rounded bg-primary/10 text-[9px] font-black uppercase tracking-widest text-primary">Slot #{{ clipper.series_number }}</span>
                                        <span class="text-[8px] font-bold text-muted-content">{{ formatDate(clipper.created_at) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <p class="text-[10px] font-bold text-muted-content truncate uppercase tracking-tight">{{ clipper.requester.name }}</p>
                                        <div class="flex gap-2">
                                            <button @click="acceptClipper(clipper.id)"
                                                class="p-1.5 bg-primary text-button-content rounded-lg hover:bg-primary hover:text-button-content! transition-colors shadow-lg">
                                                <Check class="w-3.5 h-3.5" />
                                            </button>
                                            <button @click="declineClipper(clipper.id)"
                                                class="p-1.5 bg-error text-button-content rounded-lg hover:bg-error hover:text-button-content! transition-colors shadow-lg">
                                                <X class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- New clipper: standard card -->
                        <div v-else
                            class="relative bg-component-background rounded-2xl border border-border-color overflow-hidden group/item shadow-sm hover:border-primary/30 transition-all">

                            <Transition enter-active-class="transition-opacity duration-200"
                                enter-from-class="opacity-0" leave-active-class="transition-opacity duration-200"
                                leave-to-class="opacity-0">
                                <div v-if="form.processing && pendingActionId === clipper.id"
                                    class="absolute inset-0 z-20 bg-component-background/80 backdrop-blur-sm flex items-center justify-center rounded-2xl">
                                    <Loader2 class="w-6 h-6 text-primary animate-spin" />
                                </div>
                            </Transition>

                            <!-- New clipper: single image -->
                            <div class="aspect-[1/2] sm:aspect-[1/4] relative bg-media-bg overflow-hidden border-b border-border-color">
                                <img :src="clipper.image_data"
                                    class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover/item:scale-110" />
                                <div
                                    class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 to-transparent flex justify-center gap-2 sm:translate-y-full sm:group-hover/item:translate-y-0 transition-transform">
                                    <button @click="acceptClipper(clipper.id)"
                                        class="p-2 bg-primary text-button-content rounded-lg hover:bg-primary hover:text-button-content! transition-colors shadow-lg">
                                        <Check class="w-4 h-4" />
                                    </button>
                                    <button @click="declineClipper(clipper.id)"
                                        class="p-2 bg-error text-button-content rounded-lg hover:bg-error hover:text-button-content! transition-colors shadow-lg">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="px-2 py-0.5 rounded bg-primary/10 text-[9px] font-black uppercase tracking-widest text-primary">Slot
                                        #{{ clipper.series_number }}</span>
                                    <span class="text-[8px] font-bold text-muted-content">{{
                                        formatDate(clipper.created_at) }}</span>
                                </div>
                                <p class="text-[10px] font-bold text-muted-content truncate uppercase tracking-tight">
                                    {{ clipper.requester.name }}
                                </p>
                            </div>
                        </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal :open="showAcceptModal" title="Accept Clipper"
            description="Are you sure you want to accept this clipper request?" confirmText="Accept"
            @confirm="confirmAccept" @cancel="showAcceptModal = false" @update:open="showAcceptModal = $event"
            :loading="form.processing" />

        <DeclineModal :open="showDeclineModal" title="Decline Clipper Request"
            description="This will permanently delete this clipper request. This action cannot be undone."
            @confirm="confirmDecline" @cancel="showDeclineModal = false" @update:open="showDeclineModal = $event"
            :loading="form.processing" />
    </AppLayout>
</template>
