<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { route } from 'ziggy-js';
import { Plus, X } from 'lucide-vue-next';
import ImageCropper from '@/components/ImageCropper.vue';
import { ensureJpg } from '@/util/imageSupport';

interface Clipper {
    id: number;
    series_number: number;
    image_data: string;
}

interface Series {
    id: string;
    name: string;
    custom: boolean;
    image_data: string;
    clippers: Clipper[];
}

const props = defineProps<{
    series: Series;
}>();

// Previews for UX: Initialize based on available clippers
const getInitialPreviews = () => {
    // If custom, only show existing clippers. If standard, show at least 4 slots.
    const minSlots = props.series.custom ? 0 : 4;
    const maxNum = Math.max(minSlots, ...props.series.clippers.map(c => c.series_number));
    
    const previews: (string | null)[] = [];
    for (let i = 1; i <= maxNum; i++) {
        const clipper = props.series.clippers.find(c => c.series_number === i);
        previews.push(clipper ? clipper.image_data : null);
    }
    return previews;
};

const getInitialClippers = () => {
    const minSlots = props.series.custom ? 0 : 4;
    const maxNum = Math.max(minSlots, ...props.series.clippers.map(c => c.series_number));
    
    return Array.from({ length: maxNum }, (_, i) => {
        const slotNumber = i + 1;
        const clipper = props.series.clippers.find(c => c.series_number === slotNumber);
        return { 
            id: clipper?.id || null, 
            image: null as File | null 
        };
    });
};

const seriesPreview = ref<string | null>(props.series.image_data);
const clipperPreviews = ref<(string | null)[]>(getInitialPreviews());

const form = useForm({
    _method: 'PUT', // Spoofing PUT for multipart/form-data support
    name: props.series.name,
    custom: Boolean(props.series.custom),
    image: null as File | null,
    clippers: getInitialClippers(),
    deleted_ids: [] as number[], // Track IDs to delete from DB
});

// Cropper State
const cropperOpen = ref(false);
const cropperSrc = ref<string | null>(null);
const cropperTitle = ref('');
const cropperAspectRatio = ref(1);
const cropperTarget = ref<{ type: 'series' | 'clipper', index?: number } | null>(null);

// Watch custom toggle
watch(() => form.custom, (isCustom) => {
    if (isCustom) {
        // Standard -> Custom: Sweep away empty slots and shift filled ones
        const filledClippers = [];
        const filledPreviews = [];
        
        for (let i = 0; i < form.clippers.length; i++) {
            // A slot is "filled" if it has a file OR an existing ID/preview
            if (form.clippers[i].image || clipperPreviews.value[i]) {
                filledClippers.push(form.clippers[i]);
                filledPreviews.push(clipperPreviews.value[i]);
            }
        }

        if (filledClippers.length > 0) {
            form.clippers = filledClippers;
            clipperPreviews.value = filledPreviews;
        } else {
            // If all empty, reset to 1 empty slot
            form.clippers = [{ id: null, image: null }];
            clipperPreviews.value = [null];
        }
    } else {
        // Custom -> Standard: Pad or truncate to exactly 4 slots
        if (form.clippers.length > 4) {
            form.clippers = form.clippers.slice(0, 4);
            clipperPreviews.value = clipperPreviews.value.slice(0, 4);
        } else {
            while (form.clippers.length < 4) {
                form.clippers.push({ id: null, image: null });
                clipperPreviews.value.push(null);
            }
        }
    }
});

const clearSlot = (index: number) => {
    const clipper = form.clippers[index];
    if (clipper.id) {
        form.deleted_ids.push(clipper.id);
        clipper.id = null;
    }
    clipper.image = null;
    clipperPreviews.value[index] = null;
};

const addSlot = () => {
    form.clippers.push({ id: null, image: null });
    clipperPreviews.value.push(null);
};

const removeSlot = (index: number) => {
    if (form.custom && form.clippers.length <= 1) {
        return;
    }
    const clipper = form.clippers[index];
    if (clipper.id) {
        form.deleted_ids.push(clipper.id);
    }
    form.clippers.splice(index, 1);
    clipperPreviews.value.splice(index, 1);
};

const isProcessing = ref(false);

const handleSeriesImage = async (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        isProcessing.value = true;
        try {
            const processedFile = await ensureJpg(file);
            cropperSrc.value = URL.createObjectURL(processedFile as Blob);
            cropperTitle.value = 'Crop Series Main Image';
            cropperAspectRatio.value = 4 / 3;
            cropperTarget.value = { type: 'series' };
            cropperOpen.value = true;
        } finally {
            isProcessing.value = false;
        }
    }
    (e.target as HTMLInputElement).value = '';
};

const handleClipperImage = async (index: number, e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        isProcessing.value = true;
        try {
            const processedFile = await ensureJpg(file);
            cropperSrc.value = URL.createObjectURL(processedFile as Blob);
            cropperTitle.value = `Crop Clipper #${index + 1}`;
            cropperAspectRatio.value = 1 / 4;
            cropperTarget.value = { type: 'clipper', index };
            cropperOpen.value = true;
        } finally {
            isProcessing.value = false;
        }
    }
    (e.target as HTMLInputElement).value = '';
};

const onCropDone = (blob: Blob) => {
    if (!cropperTarget.value) return;

    const file = new File([blob], 'cropped.jpg', { type: 'image/jpeg' });
    const url = URL.createObjectURL(blob);

    if (cropperTarget.value.type === 'series') {
        form.image = file;
        seriesPreview.value = url;
    } else if (cropperTarget.value.type === 'clipper' && cropperTarget.value.index !== undefined) {
        form.clippers[cropperTarget.value.index].image = file;
        clipperPreviews.value[cropperTarget.value.index] = url;
    }
};

const submit = () => {
    // Custom series validation
    if (form.custom) {
        if (form.clippers.length === 0) {
            form.setError('clippers', 'Custom series must have at least one clipper.');
            return;
        }

        const missingImages = form.clippers.some(c => !c.id && !c.image);
        if (missingImages) {
            form.setError('clippers', 'All clipper slots in a custom series must have an image.');
            return;
        }
    }

    form.post(route('series.update', props.series.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>

    <Head :title="'Edit ' + series.name" />

    <AppLayout>
        <div class="max-w-5xl mx-auto p-6">
            <form @submit.prevent="submit" class="space-y-8">

                <div class="bg-white dark:bg-[#161615] p-8 rounded-2xl border border-sidebar-border shadow-sm">
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h2 class="text-2xl font-bold">Edit Series</h2>
                            <p class="text-xs text-muted-foreground font-mono mt-1">UUID: {{ series.id }}</p>
                        </div>
                        <Link :href="route('series.show', series.id)"
                            class="text-sm font-bold text-orange-600 hover:underline">
                            View Live Page
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="block text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-2">Series
                                    Name</label>
                                <input v-model="form.name" type="text"
                                    class="w-full rounded-lg border-gray-300 dark:bg-black p-3 border focus:ring-2 focus:ring-orange-500 outline-none"
                                    placeholder="e.g. Mandala 1" />
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-white/5 rounded-lg border border-sidebar-border">
                                <input type="checkbox" v-model="form.custom" :checked="form.custom" />
                                <label for="custom" class="text-sm font-medium cursor-pointer">Mark as Custom /
                                    Community Created</label>
                            </div>
                        </div>

                        <div class="relative group">
                            <label
                                class="block text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-2">Series
                                Master Image</label>
                            <div
                                class="aspect-[4/3] w-full border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden flex flex-col items-center justify-center relative bg-white dark:bg-black">
                                <img v-if="seriesPreview" :src="seriesPreview"
                                    class="absolute inset-0 w-full h-full object-cover" />
                                <div v-if="!seriesPreview" class="text-center p-4">
                                    <p class="text-xs text-gray-400">Click to upload new group photo</p>
                                </div>
                                <div
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="text-white text-xs font-bold uppercase tracking-widest">Change
                                        Image</span>
                                </div>
                                <input type="file" @change="handleSeriesImage" accept="image/*"
                                    class="absolute inset-0 opacity-0 cursor-pointer" />
                            </div>
                            <div v-if="form.errors.image" class="text-red-500 text-xs mt-2">{{ form.errors.image }}
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-end justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold">Clipper Slots</h3>
                            <p class="text-sm text-muted-foreground">Modify individual clipper designs. If you don't
                                select a new file, the current image is kept.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div v-for="(clipper, index) in form.clippers" :key="index"
                            class="bg-white dark:bg-[#161615] p-5 rounded-2xl border border-sidebar-border shadow-sm flex flex-col group/slot relative">

                            <!-- Standard Series: Clear button (No shift) -->
                            <button v-if="!form.custom && clipperPreviews[index]" 
                                type="button"
                                @click="clearSlot(index)"
                                class="absolute -top-2 -right-2 p-1 bg-gray-500 text-white rounded-full opacity-0 group-hover/slot:opacity-100 transition-opacity z-10 shadow-lg"
                            >
                                <X class="w-3 h-3" />
                            </button>

                            <!-- Custom Series: Remove button (Always shift) -->
                            <button v-if="form.custom" 
                                type="button"
                                @click="removeSlot(index)"
                                class="absolute -top-2 -right-2 p-1 bg-red-600 text-white rounded-full opacity-0 group-hover/slot:opacity-100 transition-opacity z-10 shadow-lg"
                            >
                                <X class="w-3 h-3" />
                            </button>

                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-black text-gray-400">SLOT #{{ index + 1 }}</span>
                                <span v-if="clipper.image"
                                    class="text-[9px] bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full uppercase font-black">Changed</span>
                                <span v-else-if="clipperPreviews[index]"
                                    class="text-[9px] bg-gray-100 dark:bg-white/10 text-gray-500 px-2 py-0.5 rounded-full uppercase font-black">Existing</span>
                                <span v-else
                                    class="text-[9px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full uppercase font-black">Empty</span>
                            </div>

                            <div
                                class="w-full max-w-[140px] mx-auto aspect-[1/4] bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden relative mb-4">
                                <img v-if="clipperPreviews[index]" :src="clipperPreviews[index]!"
                                    class="w-full h-full object-cover" />
                                <div v-else
                                    class="flex flex-col items-center justify-center h-full text-gray-400 p-4 text-center">
                                    <span class="text-[10px] uppercase font-bold tracking-tighter">No Image
                                        Uploaded</span>
                                </div>
                                <div
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover/slot:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="text-white text-[10px] font-bold uppercase tracking-widest">Update
                                        Slot</span>
                                </div>
                                <input type="file" @change="handleClipperImage(index, $event)" accept="image/*"
                                    class="absolute inset-0 opacity-0 cursor-pointer" />
                            </div>

                            <div v-if="form.errors[`clippers.${index}.image`]"
                                class="text-red-500 text-[10px] text-center italic">
                                Invalid Image format
                            </div>
                        </div>

                        <!-- Add Slot Button (Custom Only) -->
                        <button v-if="form.custom" 
                            type="button"
                            @click="addSlot"
                            class="w-full max-w-[140px] mx-auto aspect-[1/4] border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl flex flex-col items-center justify-center bg-gray-50/50 dark:bg-white/5 hover:border-orange-500/50 hover:bg-orange-50/10 transition-all gap-2"
                        >
                            <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-500/10 text-orange-600">
                                <Plus class="w-6 h-6" />
                            </div>
                            <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Add Design Slot</span>
                        </button>
                    </div>
                    <div v-if="form.errors.clippers"
                        class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-bold">{{ form.errors.clippers }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-sidebar-border pt-8">
                    <Link :href="route('series.show', series.id)"
                        class="px-6 py-4 text-sm font-bold text-muted-foreground hover:text-foreground transition-colors">
                        Discard Changes
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-10 py-4 bg-orange-600 text-white rounded-xl font-black uppercase tracking-widest hover:bg-orange-700 disabled:opacity-50 transition-all shadow-lg shadow-orange-900/20">
                        {{ form.processing ? 'Saving...' : 'Update Series' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>

    <div v-if="isProcessing" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#161615] p-6 rounded-2xl border border-sidebar-border shadow-2xl flex flex-col items-center gap-4">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-500 border-t-transparent"></div>
            <div class="text-center">
                <p class="font-bold text-lg">Processing Image</p>
                <p class="text-xs text-muted-foreground uppercase tracking-widest">Converting HEIC to JPEG...</p>
            </div>
        </div>
    </div>

    <ImageCropper 
        v-model:open="cropperOpen"
        :image="cropperSrc"
        :aspect-ratio="cropperAspectRatio"
        :title="cropperTitle"
        @crop="onCropDone"
    />
</template>