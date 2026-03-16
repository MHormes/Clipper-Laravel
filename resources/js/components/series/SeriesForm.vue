<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { Plus, X, AlertCircle, ImageIcon, Loader2 } from 'lucide-vue-next';
import ImageCropper from '@/components/ImageCropper.vue';
import { ensureJpg } from '@/util/imageSupport';

const props = defineProps<{
    initialData?: {
        id?: string;
        name: string;
        custom: boolean;
        image_data: string;
        clippers: Array<{ id: string; series_number: number; image_data: string }>;
    };
    submitLabel: string;
    mode?: 'create' | 'edit' | 'request' | 'clipper-request';
}>();

const emit = defineEmits(['submit']);

const seriesPreview = ref<string | null>(props.initialData?.image_data || null);
const clipperPreviews = ref<(string | null)[]>([]);
const isProcessing = ref(false);

const page = usePage<any>();
const isClipperRequest = computed(() => props.mode === 'clipper-request');
const isAdmin = computed(() => page.props.auth.is_admin);
const showAutoAddCheckbox = computed(() =>
    props.mode === 'request' ||
    props.mode === 'clipper-request' ||
    (isAdmin.value && (props.mode === 'create' || props.mode === 'edit'))
);
const autoAddLabel = computed(() =>
    isAdmin.value && (props.mode === 'create' || props.mode === 'edit')
        ? 'Add uploaded clippers to my collection automatically'
        : 'Add accepted clippers to my collection automatically'
);

const getInitialClippers = () => {
    const isCustom = props.initialData?.custom;
    const existingClippers = props.initialData?.clippers || [];

    if (!isCustom) {
        // Non-custom series ALWAYS have exactly 4 slots
        return Array.from({ length: 4 }, (_, i) => {
            const slotNum = i + 1;
            const existing = existingClippers.find(c => c.series_number === slotNum);
            return { id: existing?.id || null, image: null as File | null };
        });
    }

    const maxExistingSlot = existingClippers.length > 0
        ? Math.max(...existingClippers.map(c => c.series_number))
        : 0;

    // Custom series have at least 1 slot if they have no clippers yet
    const count = Math.max(1, maxExistingSlot);

    return Array.from({ length: count }, (_, i) => {
        const slotNum = i + 1;
        const existing = existingClippers.find(c => c.series_number === slotNum);
        return {
            id: existing?.id || null,
            image: null as File | null,
            series_number: slotNum
        };
    });
};

const form = useForm({
    name: props.initialData?.name || '',
    custom: props.initialData?.custom || false,
    image: null as File | null,
    clippers: [] as any[],
    deleted_ids: [] as string[],
    auto_add_to_collection: props.mode === 'request' || props.mode === 'clipper-request',
});

onMounted(() => {
    form.clippers = getInitialClippers();
    clipperPreviews.value = form.clippers.map(slot => {
        const slotNum = form.clippers.indexOf(slot) + 1;
        const existing = props.initialData?.clippers?.find(c => c.series_number === slotNum);
        return existing ? existing.image_data : null;
    });
});

// Watch custom toggle to handle slot logic
watch(() => form.custom, (isCustom) => {
    if (isCustom) {
        const filled = form.clippers.filter((c, i) => c.id || c.image || clipperPreviews.value[i]);
        form.clippers = filled.length > 0 ? filled : [{ id: null, image: null, series_number: 1 }];
        clipperPreviews.value = clipperPreviews.value.slice(0, form.clippers.length);
    } else {
        while (form.clippers.length < 4) {
            form.clippers.push({ id: null, image: null, series_number: form.clippers.length + 1 });
            clipperPreviews.value.push(null);
        }
        form.clippers = form.clippers.slice(0, 4);
        clipperPreviews.value = clipperPreviews.value.slice(0, 4);
    }
});

const handleRemoveAction = (index: number) => {
    const clipper = form.clippers[index];
    const isEditMode = props.mode === 'edit';

    // Admin + Edit Mode logic: Enhanced control
    if (isAdmin.value && isEditMode) {
        if (clipper.id) {
            // It's an existing clipper
            if (clipperPreviews.value[index] === null) {
                // Secondary click: remove slot entirely if it's a custom series
                if (form.custom && form.clippers.length > 1) {
                    form.clippers.splice(index, 1);
                    clipperPreviews.value.splice(index, 1);
                }
            } else {
                // Primary click: clear the image but keep the slot (re-upload preserves ID/collections)
                if (!form.deleted_ids.includes(clipper.id.toString())) {
                    form.deleted_ids.push(clipper.id.toString());
                }
                clipper.image = null;
                clipperPreviews.value[index] = null;
            }
        } else {
            // It's a brand new slot
            if (form.custom && form.clippers.length > 1) {
                form.clippers.splice(index, 1);
                clipperPreviews.value.splice(index, 1);
            } else {
                clipper.image = null;
                clipperPreviews.value[index] = null;
            }
        }
        return;
    }

    // Default Restricted Logic (Requests and Non-Admins)
    if (form.custom && form.clippers.length > 1 && !isClipperRequest.value) {
        form.clippers.splice(index, 1);
        clipperPreviews.value.splice(index, 1);
    } else {
        const original = props.initialData?.clippers.find(c => c.series_number === clipper.series_number);

        if (isClipperRequest.value && (original || clipper.id)) {
            // Revert to the original image rather than deleting
            clipper.id = original?.id || clipper.id;
            clipper.image = null;
            clipperPreviews.value[index] = original?.image_data || null;
        } else if (isClipperRequest.value && form.custom) {
            form.clippers.splice(index, 1);
            clipperPreviews.value.splice(index, 1);

            // Re-index temporary slot markers
            form.clippers.forEach((c, i) => {
                if (!c.id) c.series_number = i + 1;
            });
        } else {
            clipper.id = null;
            clipper.image = null;
            clipperPreviews.value[index] = null;
        }
    }
};

const addSlot = () => {
    form.clippers.push({ id: null, image: null, series_number: form.clippers.length + 1 });
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
        cropperAspectRatio.value = type === 'series' ? 4 / 3 : 1 / 4;
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
        const index = cropperTarget.value.index;
        const clipper = form.clippers[index];

        clipper.image = file;

        // If we are re-uploading to a slot that was previously marked for deletion,
        // remove it from deleted_ids so the backend performs an UPDATE instead.
        if (clipper.id && form.deleted_ids.includes(clipper.id.toString())) {
            form.deleted_ids = form.deleted_ids.filter(id => id !== clipper.id.toString());
        }

        if (!clipper.series_number) {
            clipper.series_number = index + 1;
        }
        clipperPreviews.value[index] = url;
    }
};

const getSlotError = (index: number) => {
    const errorKeyPrefix = `clippers.${index}`;
    return (form.errors as any)[`${errorKeyPrefix}.image`] ||
        (form.errors as any)[`${errorKeyPrefix}.series_number`] ||
        (form.errors as any)[errorKeyPrefix];
};
</script>

<template>
    <form @submit.prevent="emit('submit', form)" class="space-y-12">
        <!-- Series Info Section - Hidden in clipper-request mode -->
        <div v-if="!isClipperRequest"
            class="bg-component-background p-8 rounded-2xl border border-border-color shadow-sm relative overflow-hidden">
            <!-- Background Accent -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>

            <div class="relative z-10 flex items-center gap-3 mb-8">
                <div class="p-2 rounded-lg bg-primary/10 text-primary">
                    <ImageIcon class="w-5 h-5" />
                </div>
                <h2 class="text-xl font-black uppercase tracking-widest text-primary-content">Series Information</h2>
            </div>

            <div v-if="props.mode === 'request'"
                class="relative z-10 mb-8 p-4 bg-primary/5 border border-primary/10 rounded-2xl flex items-start gap-4">
                <div class="p-2 rounded-lg bg-primary/10">
                    <AlertCircle class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <h4 class="text-sm font-black uppercase tracking-widest text-primary-content">Requesting a New Series</h4>
                    <p class="text-xs text-muted-content font-bold mt-1 leading-relaxed">
                        Your request will be reviewed by an administrator. Once approved, the series will appear in the
                        catalog for everyone to see.
                    </p>
                </div>
            </div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="space-y-8">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-muted-content mb-3 opacity-70">Display
                            Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Mandala 1" :disabled="isClipperRequest"
                            class="w-full rounded-xl border-border-color dark:border-secondary-content/10 dark:bg-primary-background/50 p-4 text-lg font-bold outline-none focus:ring-2 focus:ring-primary transition-all disabled:opacity-50 disabled:bg-muted-background" />
                        <p v-if="form.errors.name" class="text-error text-xs font-bold mt-2 flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" /> {{ form.errors.name }}
                        </p>
                    </div>
                    <div
                        class="flex items-center gap-4 p-5 bg-muted-background/30 rounded-2xl border border-border-color/50">
                        <input type="checkbox" v-model="form.custom" id="custom" :disabled="isClipperRequest"
                            class="h-6 w-6 accent-primary rounded-lg disabled:opacity-50" />
                        <label for="custom" class="text-sm font-bold cursor-pointer select-none">Community Created /
                            Custom Series</label>
                        <p v-if="form.errors.custom" class="text-error text-[10px] font-bold mt-1">{{
                            form.errors.custom }}</p>
                    </div>
                </div>

                <div>
                    <div
                        class="aspect-[4/3] w-full border-2 border-dashed border-border-color rounded-2xl overflow-hidden relative bg-muted-background/50 group/master transition-colors hover:border-primary/50">
                        <img v-if="seriesPreview" :src="seriesPreview"
                            class="absolute inset-0 w-full h-full object-cover" />
                        <div v-else class="flex flex-col items-center justify-center h-full text-center">
                            <div class="p-4 rounded-full bg-muted-background mb-3">
                                <ImageIcon class="w-10 h-10 text-muted-content opacity-20" />
                            </div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-content opacity-40">Click to upload cover</p>
                        </div>
                        <input type="file" @change="handleFile('series', null, $event)" accept="image/*"
                            class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                    </div>
                    <p v-if="form.errors.image" class="text-error text-xs font-bold mt-2">{{ form.errors.image }}</p>
                </div>
            </div>
        </div>

        <!-- Clipper Request Instructions -->
        <div v-if="isClipperRequest"
            class="bg-component-background p-8 rounded-2xl border border-border-color shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
            
            <div class="relative z-10 flex items-center gap-3 mb-6">
                <div class="p-2 rounded-lg bg-primary/10 text-primary">
                    <Plus class="w-5 h-5" />
                </div>
                <h2 class="text-xl font-black uppercase tracking-widest text-primary-content">Request Clippers</h2>
            </div>

            <div class="relative z-10 p-4 bg-primary/5 border border-primary/10 rounded-2xl flex items-start gap-4">
                <div class="p-2 rounded-lg bg-primary/10">
                    <AlertCircle class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <h4 class="text-sm font-black uppercase tracking-widest text-primary-content">Adding to Existing Series</h4>
                    <p class="text-xs text-muted-content font-bold mt-1 leading-relaxed">
                        Specify which slots you want to add or replace clippers for. Administrators will review each
                        clipper before they are added to the system.
                    </p>
                </div>
            </div>

            <div
                v-if="showAutoAddCheckbox"
                class="relative z-10 mt-6 flex items-center gap-4 rounded-2xl border border-border-color/50 bg-muted-background/30 p-5"
            >
                <input
                    id="auto_add_to_collection_clipper_request"
                    v-model="form.auto_add_to_collection"
                    type="checkbox"
                    class="h-6 w-6 rounded-lg accent-primary"
                />
                <label for="auto_add_to_collection_clipper_request" class="text-sm font-bold cursor-pointer select-none">
                    {{ autoAddLabel }}
                </label>
            </div>
        </div>

        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-info/10 text-info">
                    <Plus class="w-5 h-5" />
                </div>
                <h3 class="text-xl font-black uppercase tracking-widest text-primary-content">The Collection</h3>
            </div>

            <div class="grid grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                <div v-for="(clipper, index) in form.clippers" :key="index"
                    class="relative group/slot flex flex-col items-center">

                    <button
                        v-if="((form.custom && (form.clippers.length > 1 || clipperPreviews[index])) || (!form.custom && clipperPreviews[index])) && (!isClipperRequest || (isClipperRequest && !clipper.id))"
                        type="button" @click="handleRemoveAction(index)"
                        class="absolute -top-2 -right-2 p-1.5 bg-error text-button-content rounded-full opacity-0 group-hover/slot:opacity-100 z-20 shadow-xl transition-all hover:scale-110">
                        <X class="w-3.5 h-3.5" />
                    </button>

                    <div
                        class="w-full aspect-[1/4] bg-component-background rounded-xl overflow-hidden relative border border-border-color group-hover/slot:border-primary/50 transition-all cursor-pointer shadow-sm">
                        <img v-if="clipperPreviews[index]" :src="clipperPreviews[index]!"
                            class="absolute inset-0 w-full h-full object-cover" />
                        <div v-else class="absolute inset-0 flex flex-col items-center justify-center p-2 text-center bg-muted-background/30">
                            <Plus class="w-5 h-5 text-muted-content opacity-30 mb-1" />
                            <span class="text-[8px] uppercase font-black text-muted-content opacity-40">Add</span>
                        </div>
                        <input v-if="!clipperPreviews[index] || (isAdmin && props.mode === 'edit')" type="file"
                            @change="handleFile('clipper', index, $event)" accept="image/*"
                            class="absolute inset-0 opacity-0 cursor-pointer z-10" />
                    </div>

                    <div
                        class="mt-2 text-[10px] font-black text-muted-content uppercase tracking-tighter flex items-center gap-1 opacity-50">
                        Slot #{{ index + 1 }}
                    </div>

                    <p v-if="getSlotError(index)"
                        class="text-[9px] text-error font-bold mt-1 text-center leading-tight">
                        {{ getSlotError(index) }}
                    </p>
                </div>

                <button v-if="form.custom" type="button" @click="addSlot"
                    class="w-full aspect-[1/4] border-2 border-dashed border-border-color rounded-xl flex flex-col items-center justify-center hover:bg-primary hover:text-button-content! /5 hover:border-primary/50 transition-all group/add bg-muted-background/10">
                    <Plus class="w-6 h-6 text-muted-content group-hover/add:text-primary transition-colors opacity-30" />
                    <span class="text-[9px] font-black uppercase text-muted-content group-hover/add:text-primary mt-2 opacity-40">Add
                        Slot</span>
                </button>
            </div>

            <div v-if="form.errors.clippers"
                class="p-4 bg-error/10 border border-error/20 text-error rounded-xl flex items-center gap-3">
                <AlertCircle class="w-5 h-5 shrink-0" />
                <p class="text-sm font-bold uppercase tracking-tight">{{ form.errors.clippers }}</p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-center justify-between border-t border-border-color pt-10 gap-8">
            <div class="flex items-start gap-4 max-w-lg">
                <AlertCircle class="w-5 h-5 text-primary mt-1 shrink-0" />
                <div>
                    <div
                        v-if="showAutoAddCheckbox && !isClipperRequest"
                        class="mb-4 flex items-center gap-4 rounded-2xl border border-border-color/50 bg-muted-background/30 p-5 text-sm font-bold"
                    >
                        <input
                            id="auto_add_to_collection_request"
                            v-model="form.auto_add_to_collection"
                            type="checkbox"
                            class="h-6 w-6 rounded-lg accent-primary"
                        />
                        <label for="auto_add_to_collection_request" class="cursor-pointer select-none">
                            {{ autoAddLabel }}
                        </label>
                    </div>
                    <p class="text-xs text-muted-content font-bold leading-relaxed">
                        By submitting images, you state that you are the owner of the images or have explicit permission to
                        use them. Unauthorized use of copyrighted material is prohibited.
                    </p>
                </div>
            </div>
            <button type="submit" :disabled="form.processing"
                class="w-full md:w-auto px-12 py-5 bg-primary text-button-content rounded-xl font-bold hover:bg-primary hover:text-button-content!  disabled:opacity-50 shadow-xl transition-all active:scale-95">
                {{ props.submitLabel }}
            </button>
        </div>
    </form>

    <div v-if="isProcessing"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-md">
        <div
            class="bg-component-background p-8 rounded-3xl border border-border-color shadow-2xl flex flex-col items-center text-center">
            <Loader2 class="w-12 h-12 text-primary animate-spin mb-4" />
            <h3 class="text-xl font-black uppercase tracking-widest mb-1 text-primary-content">Optimizing</h3>
            <p class="text-xs text-muted-content uppercase tracking-widest font-bold">Preparing image for the database...</p>
        </div>
    </div>

    <ImageCropper v-model:open="cropperOpen" :image="cropperSrc" :aspect-ratio="cropperAspectRatio"
        :title="cropperTitle" @crop="onCropDone" />
</template>
