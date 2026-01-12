<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

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

const handleSeriesImage = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        form.image = file;
        seriesPreview.value = URL.createObjectURL(file);
    }
};

const handleClipperImage = (index: number, e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        form.clippers[index].image = file;
        clipperPreviews.value[index] = URL.createObjectURL(file);
    } else {
        form.clippers[index].image = null;
        clipperPreviews.value[index] = null;
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
                                class="aspect-video w-full border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden flex flex-col items-center justify-center relative">
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
                            class="bg-white dark:bg-[#161615] p-5 rounded-2xl border border-sidebar-border shadow-sm flex flex-col">

                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-black text-gray-400">#{{ index + 1 }}</span>
                                <span v-if="clipper.image"
                                    class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full uppercase font-bold">Ready</span>
                            </div>

                            <div
                                class="aspect-[3/4] w-full bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden relative group mb-4">
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
</template>