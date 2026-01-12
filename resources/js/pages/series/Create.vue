<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { Plus, X } from 'lucide-vue-next';
import { watch } from 'vue';
import ImageCropper from '@/components/ImageCropper.vue';

// Previews for UX
const seriesPreview = ref<string | null>(null);
const clipperPreviews = ref<(string | null)[]>([null, null, null, null]);

const form = useForm({
    name: '',
    custom: false,
    image: null as File | null, // Main series image (Required)
    clippers: [
        { image: null as File | null }, // Slot 1
        { image: null as File | null }, // Slot 2
        { image: null as File | null }, // Slot 3
        { image: null as File | null }, // Slot 4
    ],
});

// Cropper State
const cropperOpen = ref(false);
const cropperSrc = ref<string | null>(null);
const cropperTitle = ref('');
const cropperAspectRatio = ref(1);
const cropperTarget = ref<{ type: 'series' | 'clipper', index?: number } | null>(null);

// Watch custom toggle to reset slots if needed
watch(() => form.custom, (isCustom) => {
    if (isCustom) {
        // Standard -> Custom: Sweep away empty slots and shift filled ones
        const filledClippers = [];
        const filledPreviews = [];
        
        for (let i = 0; i < form.clippers.length; i++) {
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
            form.clippers = [{ image: null }];
            clipperPreviews.value = [null];
        }
    } else {
        // Custom -> Standard: Pad or truncate to exactly 4 slots
        if (form.clippers.length > 4) {
            form.clippers = form.clippers.slice(0, 4);
            clipperPreviews.value = clipperPreviews.value.slice(0, 4);
        } else {
            while (form.clippers.length < 4) {
                form.clippers.push({ image: null });
                clipperPreviews.value.push(null);
            }
        }
    }
});

const clearSlot = (index: number) => {
    form.clippers[index].image = null;
    clipperPreviews.value[index] = null;
};

const addSlot = () => {
    form.clippers.push({ image: null });
    clipperPreviews.value.push(null);
};

const removeSlot = (index: number) => {
    form.clippers.splice(index, 1);
    clipperPreviews.value.splice(index, 1);
};

const handleSeriesImage = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        cropperSrc.value = URL.createObjectURL(file);
        cropperTitle.value = 'Crop Series Main Image';
        cropperAspectRatio.value = 4 / 3;
        cropperTarget.value = { type: 'series' };
        cropperOpen.value = true;
    }
    // Reset input so same file can be selected again
    (e.target as HTMLInputElement).value = '';
};

const handleClipperImage = (index: number, e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        cropperSrc.value = URL.createObjectURL(file);
        cropperTitle.value = `Crop Clipper #${index + 1}`;
        cropperAspectRatio.value = 1 / 4;
        cropperTarget.value = { type: 'clipper', index };
        cropperOpen.value = true;
    }
    // Reset input
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
    form.post(route('series.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Register New Series" />

    <AppLayout>
        <div class="max-w-5xl mx-auto p-6">
            <form @submit.prevent="submit" class="space-y-8">

                <div class="bg-white dark:bg-[#161615] p-8 rounded-2xl border border-sidebar-border shadow-sm">
                    <h2 class="text-2xl font-bold mb-6">Series Information</h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="block text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-2">Series
                                    Name</label>
                                <input v-model="form.name" type="text"
                                    class="w-full rounded-lg border-gray-300 dark:bg-black p-3 border focus:ring-2 focus:ring-orange-500 outline-none"
                                    placeholder="e.g. Mandala Series 1" />
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-white/5 rounded-lg">
                                <input type="checkbox" v-model="form.custom" id="custom"
                                    class="h-5 w-5 accent-orange-600" />
                                <label for="custom" class="text-sm font-medium">Mark as Custom / Community
                                    Created</label>
                            </div>
                        </div>

                        <div class="relative group">
                            <label
                                class="block text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-2">Series
                                Master Image (Required)</label>
                            <div
                                class="aspect-[4/3] w-full border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden flex flex-col items-center justify-center relative bg-white dark:bg-black">
                                <img v-if="seriesPreview" :src="seriesPreview"
                                    class="absolute inset-0 w-full h-full object-cover" />
                                <div v-else class="text-center p-4">
                                    <p class="text-xs text-gray-400">Click to upload group photo</p>
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
                    <h3 class="text-xl font-bold mb-4">Clipper Slots</h3>
                    <p class="text-sm text-muted-foreground mb-6">Upload images for the clippers included in this
                        series. Empty slots will be ignored.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div v-for="(clipper, index) in form.clippers" :key="index"
                            class="bg-white dark:bg-[#161615] p-5 rounded-2xl border border-sidebar-border shadow-sm flex flex-col relative group">
                            
                            <!-- Standard Series: Clear button (No shift) -->
                            <button v-if="!form.custom && clipperPreviews[index]" 
                                type="button"
                                @click="clearSlot(index)"
                                class="absolute -top-2 -right-2 p-1 bg-gray-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-lg"
                            >
                                <X class="w-3 h-3" />
                            </button>

                            <!-- Custom Series: Remove button (Always shift) -->
                            <button v-if="form.custom" 
                                type="button"
                                @click="removeSlot(index)"
                                class="absolute -top-2 -right-2 p-1 bg-red-600 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-lg"
                            >
                                <X class="w-3 h-3" />
                            </button>

                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-black text-gray-400">#{{ index + 1 }}</span>
                                <span v-if="clipper.image"
                                    class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full uppercase font-bold">Ready</span>
                            </div>

                            <div
                                class="aspect-[1/4] w-full bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden relative group mb-4">
                                <img v-if="clipperPreviews[index]" :src="clipperPreviews[index]!"
                                    class="w-full h-full object-cover" />
                                <div v-else
                                    class="flex flex-col items-center justify-center h-full text-gray-400 p-4 text-center">
                                    <span class="text-[10px] uppercase font-bold tracking-tighter">No Image</span>
                                </div>
                                <input type="file" @change="handleClipperImage(index, $event)" accept="image/*"
                                    class="absolute inset-0 opacity-0 cursor-pointer" />
                            </div>

                            <div v-if="form.errors[`clippers.${index}.image`]"
                                class="text-red-500 text-[10px] text-center">
                                Invalid Image File
                            </div>
                        </div>

                        <!-- Add Slot Button (Custom Only) -->
                        <button v-if="form.custom" 
                            type="button"
                            @click="addSlot"
                            class="aspect-[1/4] border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl flex flex-col items-center justify-center bg-gray-50/50 dark:bg-white/5 hover:border-orange-500/50 hover:bg-orange-50/10 transition-all gap-2"
                        >
                            <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-500/10 text-orange-600">
                                <Plus class="w-6 h-6" />
                            </div>
                            <span class="text-xs font-bold uppercase tracking-widest text-gray-500">Add Design Slot</span>
                        </button>
                    </div>
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

                <div class="flex items-center justify-between border-t border-sidebar-border pt-8">
                    <p class="text-xs text-muted-foreground italic">* Only slots with images will be added to the
                        database.</p>
                    <button type="submit" :disabled="form.processing"
                        class="px-10 py-4 bg-orange-600 text-white rounded-xl font-black uppercase tracking-widest hover:bg-orange-700 disabled:opacity-50 transition-all shadow-lg shadow-orange-900/20">
                        {{ form.processing ? 'Syncing...' : 'Add Series' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>

    <ImageCropper 
        v-model:open="cropperOpen"
        :image="cropperSrc"
        :aspect-ratio="cropperAspectRatio"
        :title="cropperTitle"
        @crop="onCropDone"
    />
</template>