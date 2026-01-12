<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

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

// Previews for UX: Initialize with existing storage paths
const seriesPreview = ref<string | null>(`/storage/${props.series.image_data}`);
const clipperPreviews = ref<(string | null)[]>([
    props.series.clippers.find(c => c.series_number === 1) ? `/storage/${props.series.clippers.find(c => c.series_number === 1)?.image_data}` : null,
    props.series.clippers.find(c => c.series_number === 2) ? `/storage/${props.series.clippers.find(c => c.series_number === 2)?.image_data}` : null,
    props.series.clippers.find(c => c.series_number === 3) ? `/storage/${props.series.clippers.find(c => c.series_number === 3)?.image_data}` : null,
    props.series.clippers.find(c => c.series_number === 4) ? `/storage/${props.series.clippers.find(c => c.series_number === 4)?.image_data}` : null,
]);

const form = useForm({
    _method: 'PUT', // Spoofing PUT for multipart/form-data support
    name: props.series.name,
    custom: Boolean(props.series.custom),
    image: null as File | null,
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
    }
};

const submit = () => {
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
                                    placeholder="e.g. Mandala Series 1" />
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
                                class="aspect-video w-full border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden flex flex-col items-center justify-center relative bg-gray-50 dark:bg-black">
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
                            class="bg-white dark:bg-[#161615] p-5 rounded-2xl border border-sidebar-border shadow-sm flex flex-col group/slot">

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
                                class="aspect-[3/4] w-full bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden relative mb-4">
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
</template>