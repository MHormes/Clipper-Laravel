<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, X, AlertCircle, ImageIcon, Loader2 } from 'lucide-vue-next';
import ImageCropper from '@/components/ImageCropper.vue';
import { ensureJpg } from '@/util/imageSupport';

const props = defineProps<{
    initialData?: {
        id?: string;
        name: string;
        custom: boolean;
        image_data: string;
        clippers: Array<{ id: number; series_number: number; image_data: string }>;
    };
    submitLabel: string;
}>();

const emit = defineEmits(['submit']);

const seriesPreview = ref<string | null>(props.initialData?.image_data || null);
const clipperPreviews = ref<(string | null)[]>([]);
const isProcessing = ref(false);

const getInitialClippers = () => {
    const minSlots = props.initialData?.custom ? 0 : 4;
    const existingMax = props.initialData?.clippers?.length 
        ? Math.max(...props.initialData.clippers.map(c => c.series_number)) 
        : 0;
    const count = Math.max(minSlots, existingMax || (props.initialData ? 0 : 4));
    
    return Array.from({ length: count }, (_, i) => {
        const slotNum = i + 1;
        const existing = props.initialData?.clippers.find(c => c.series_number === slotNum);
        return { id: existing?.id || null, image: null as File | null };
    });
};

const form = useForm({
    name: props.initialData?.name || '',
    custom: props.initialData?.custom || false,
    image: null as File | null,
    clippers: [] as any[], 
    deleted_ids: [] as number[],
});

onMounted(() => {
    form.clippers = getInitialClippers();
    clipperPreviews.value = form.clippers.map(slot => {
        const existing = props.initialData?.clippers.find(c => c.id === slot.id);
        return existing ? existing.image_data : null;
    });
});

// Watch custom toggle to handle slot logic
watch(() => form.custom, (isCustom) => {
    if (isCustom) {
        const filled = form.clippers.filter((c, i) => c.id || c.image || clipperPreviews.value[i]);
        form.clippers = filled.length > 0 ? filled : [{ id: null, image: null }];
        clipperPreviews.value = clipperPreviews.value.slice(0, form.clippers.length);
    } else {
        while (form.clippers.length < 4) {
            form.clippers.push({ id: null, image: null });
            clipperPreviews.value.push(null);
        }
        form.clippers = form.clippers.slice(0, 4);
        clipperPreviews.value = clipperPreviews.value.slice(0, 4);
    }
});

const handleRemoveAction = (index: number) => {
    const clipper = form.clippers[index];
    if (clipper.id) form.deleted_ids.push(clipper.id);

    if (form.custom && form.clippers.length > 1) {
        form.clippers.splice(index, 1);
        clipperPreviews.value.splice(index, 1);
    } else {
        clipper.id = null;
        clipper.image = null;
        clipperPreviews.value[index] = null;
    }
};

const addSlot = () => {
    form.clippers.push({ id: null, image: null });
    clipperPreviews.value.push(null);
};

// Cropper State
const cropperOpen = ref(false);
const cropperSrc = ref<string | null>(null);
const cropperTitle = ref('');
const cropperAspectRatio = ref(1);
const cropperTarget = ref<{ type: 'series' | 'clipper', index?: number } | null>(null);

const handleFile = async (type: 'series' | 'clipper', index: number | null, e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;

    isProcessing.value = true;
    try {
        const processed = await ensureJpg(file);
        cropperSrc.value = URL.createObjectURL(processed as Blob);
        cropperTitle.value = type === 'series' ? 'Crop Series Cover' : `Crop Clipper #${(index ?? 0) + 1}`;
        cropperAspectRatio.value = type === 'series' ? 4/3 : 1/4;
        cropperTarget.value = { type, index: index ?? undefined };
        cropperOpen.value = true;
    } finally {
        isProcessing.value = false;
        (e.target as HTMLInputElement).value = '';
    }
};

const onCropDone = (blob: Blob) => {
    const file = new File([blob], 'cropped.jpg', { type: 'image/jpeg' });
    const url = URL.createObjectURL(blob);

    if (cropperTarget.value?.type === 'series') {
        form.image = file;
        seriesPreview.value = url;
    } else if (cropperTarget.value?.index !== undefined) {
        form.clippers[cropperTarget.value.index].image = file;
        clipperPreviews.value[cropperTarget.value.index] = url;
    }
};
</script>

<template>
    <form @submit.prevent="emit('submit', form)" class="space-y-12">
        <div class="bg-white dark:bg-[#161615] p-8 rounded-2xl border border-sidebar-border shadow-sm">
            <h2 class="text-xl font-black uppercase tracking-widest mb-6 text-orange-600">Series Information</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="space-y-8">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-muted-foreground mb-3">Display Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Mandala 1" 
                               class="w-full rounded-xl border-gray-200 dark:border-white/10 dark:bg-black p-4 text-lg font-bold outline-none focus:ring-2 focus:ring-orange-500 transition-all" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs font-bold mt-2 flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" /> {{ form.errors.name }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4 p-5 bg-orange-500/5 dark:bg-white/5 rounded-2xl border border-orange-500/20">
                        <input type="checkbox" v-model="form.custom" id="custom" class="h-6 w-6 accent-orange-600 rounded-lg" />
                        <label for="custom" class="text-sm font-bold cursor-pointer select-none">Community Created / Custom Series</label>
                    </div>
                </div>

                <div>
                    <div class="aspect-[4/3] w-full border-2 border-dashed border-gray-200 dark:border-white/10 rounded-2xl overflow-hidden relative bg-gray-50 dark:bg-black group/master">
                        <img v-if="seriesPreview" :src="seriesPreview" class="absolute inset-0 w-full h-full object-cover" />
                        <div v-else class="flex flex-col items-center justify-center h-full text-center">
                            <ImageIcon class="w-10 h-10 text-gray-300 mb-3" />
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Click to upload cover</p>
                        </div>
                        <input type="file" @change="handleFile('series', null, $event)" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                    </div>
                    <p v-if="form.errors.image" class="text-red-500 text-xs font-bold mt-2">{{ form.errors.image }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="flex items-end justify-between">
                <h3 class="text-xl font-black uppercase tracking-widest text-orange-600">The Collection</h3>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                <div v-for="(clipper, index) in form.clippers" :key="index" class="relative group/slot flex flex-col items-center">
                    
                    <button v-if="(form.custom && (form.clippers.length > 1 || clipperPreviews[index])) || (!form.custom && clipperPreviews[index])" 
                            type="button" @click="handleRemoveAction(index)" 
                            class="absolute -top-2 -right-2 p-1.5 bg-red-600 text-white rounded-full opacity-0 group-hover/slot:opacity-100 z-20 shadow-xl transition-all hover:scale-110">
                        <X class="w-3.5 h-3.5" />
                    </button>

                    <div class="w-full aspect-[1/4] bg-gray-50 dark:bg-black rounded-xl overflow-hidden relative border-2 border-sidebar-border group-hover/slot:border-orange-500/50 transition-all cursor-pointer shadow-sm">
                        <img v-if="clipperPreviews[index]" :src="clipperPreviews[index]!" class="absolute inset-0 w-full h-full object-cover" />
                        <div v-else class="absolute inset-0 flex flex-col items-center justify-center p-2 text-center">
                            <Plus class="w-5 h-5 text-gray-300 mb-1" />
                            <span class="text-[8px] uppercase font-black text-gray-400">Add</span>
                        </div>
                        <input type="file" @change="handleFile('clipper', index, $event)" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                    </div>

                    <div class="mt-2 text-[10px] font-black text-muted-foreground uppercase tracking-tighter flex items-center gap-1">
                        Slot #{{ index + 1 }}
                    </div>

                    <p v-if="form.errors[`clippers.${index}.image`]" class="text-[9px] text-red-500 font-bold mt-1 text-center leading-tight">
                        Invalid Image
                    </p>
                </div>

                <button v-if="form.custom" type="button" @click="addSlot" 
                        class="w-full aspect-[1/4] border-2 border-dashed border-sidebar-border rounded-xl flex flex-col items-center justify-center hover:bg-orange-500/5 hover:border-orange-500/50 transition-all group/add">
                    <Plus class="w-6 h-6 text-gray-300 group-hover/add:text-orange-500 transition-colors" />
                    <span class="text-[9px] font-black uppercase text-gray-400 group-hover/add:text-orange-500 mt-2">Add Slot</span>
                </button>
            </div>
            
            <div v-if="form.errors.clippers" class="p-4 bg-red-500/10 border border-red-500/20 text-red-500 rounded-xl flex items-center gap-3">
                <AlertCircle class="w-5 h-5 shrink-0" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ form.errors.clippers }}</p>
            </div>
        </div>

        <div class="flex items-center justify-end border-t border-sidebar-border pt-10 gap-6">
            <button type="submit" :disabled="form.processing" 
                    class="px-12 py-5 bg-orange-600 text-white rounded-2xl font-black uppercase tracking-[0.2em] hover:bg-orange-700 disabled:opacity-50 shadow-xl shadow-orange-900/20 transition-all active:scale-95">
                {{ form.processing ? 'Syncing...' : props.submitLabel }}
            </button>
        </div>
    </form>

    <div v-if="isProcessing" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-md">
        <div class="bg-white dark:bg-[#161615] p-8 rounded-3xl border border-white/10 shadow-2xl flex flex-col items-center text-center">
            <Loader2 class="w-12 h-12 text-orange-600 animate-spin mb-4" />
            <h3 class="text-xl font-black uppercase tracking-widest mb-1">Optimizing</h3>
            <p class="text-xs text-muted-foreground uppercase tracking-widest">Preparing image for the database...</p>
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