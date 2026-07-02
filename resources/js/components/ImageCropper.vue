<script setup lang="ts">
import { ref, watch } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import { X, Check, RotateCcw, RotateCw } from '@lucide/vue';

const props = defineProps<{
    open: boolean;
    image: string | null;
    aspectRatio: number; // e.g. 16/9 or 3/4
    title: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'crop', value: Blob): void;
    (e: 'cancel'): void;
}>();

const cropperRef = ref<any>(null);
const rotation = ref(0);
const lastRotation = ref(0);

watch(() => props.open, () => {
    if (props.open) {
        reset();
    }
})

const handleCancel = () => {
    emit('update:open', false);
    emit('cancel');
};

const handleAccept = () => {
    if (cropperRef.value) {
        const { canvas } = cropperRef.value.getResult();
        if (canvas) {
            canvas.toBlob((blob: Blob) => {
                emit('crop', blob);
                emit('update:open', false);
            }, 'image/jpeg', 0.9);
        }
    }
};

const rotateLeft = () => {
    rotation.value = (rotation.value - 90);
    handleRotationSlide();
};

const rotateRight = () => {
    rotation.value = (rotation.value + 90);
    handleRotationSlide();
};

const handleRotationSlide = () => {
    if (cropperRef.value) {
        const delta = rotation.value - lastRotation.value;
        cropperRef.value.rotate(delta);
        lastRotation.value = rotation.value;
    }
};

const reset = () => {
    if (cropperRef.value) {
        cropperRef.value.reset();
        rotation.value = 0;
        lastRotation.value = 0;
    }
};
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="handleCancel"></div>

        <!-- Modal Content -->
        <div class="relative bg-primary-background w-full max-w-4xl rounded-3xl shadow-2xl border border-border-color overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-border-color flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black uppercase tracking-tight">{{ title }}</h3>
                    <p class="text-[10px] text-muted-content font-bold uppercase tracking-widest mt-0.5">Adjust the crop to fit the required aspect ratio</p>
                </div>
                <button @click="handleCancel" class="p-2 hover:bg-[var(--hover-overlay)] rounded-full transition-colors">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- Cropper Area -->
            <div class="flex-1 bg-media-bg p-4 sm:p-8 overflow-hidden flex items-center justify-center min-h-[400px]">
                <Cropper
                    ref="cropperRef"
                    class="max-w-full max-h-full"
                    :src="image"
                    :stencil-props="{
                        aspectRatio: aspectRatio,
                    }"
                    image-restriction="stencil"
                    :auto-zoom="true"
                    :canvas="true"
                />
            </div>

            <!-- Footer -->
            <div class="grid grid-cols-1 md:flex md:items-center md:justify-between px-6 py-4 border-t border-border-color bg-muted-background/50">
                <div class="flex flex-wrap items-center gap-y-3">
                    <div class="flex items-center gap-1">
                        <button
                            @click="reset"
                            class="flex items-center gap-2 px-3 py-2 text-xs font-bold uppercase tracking-wider text-muted-content hover:text-primary-content transition-colors"
                            title="Reset"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            Reset
                        </button>

                        <div class="w-px h-4 bg-border-color mx-2"></div>

                        <button
                            @click="rotateLeft"
                            class="p-2 text-muted-content hover:text-primary-content transition-colors"
                            title="Rotate Left"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </button>

                        <button
                            @click="rotateRight"
                            class="p-2 text-muted-content hover:text-primary-content transition-colors"
                            title="Rotate Right"
                        >
                            <RotateCw class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="hidden md:block w-px h-4 bg-border-color mx-2"></div>

                    <div class="flex items-center gap-3 px-2 w-full md:w-auto">
                        <span class="text-[10px] font-bold text-muted-content w-8 uppercase tracking-tighter">Rotate</span>
                        <input
                            type="range"
                            v-model.number="rotation"
                            min="-180"
                            max="180"
                            step="1"
                            @input="handleRotationSlide"
                            class="flex-1 md:w-32 h-1.5 bg-muted-background rounded-lg appearance-none cursor-pointer accent-primary"
                        />
                        <span class="text-[10px] font-mono font-bold text-primary w-8 text-right">{{ rotation }}°</span>
                    </div>
                </div>

                <div class="mt-4 md:mt-0 flex gap-3">
                    <button
                        @click="handleCancel"
                        class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest border border-border-color hover:bg-[var(--hover-overlay)] transition-all text-muted-content"
                    >
                        Deny / Cancel
                    </button>
                    <button
                        @click="handleAccept"
                        class="px-8 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-primary text-button-content hover:bg-primary hover:text-button-content!  shadow-lg shadow-primary/20 transition-all flex items-center gap-2"
                    >
                        <Check class="w-4 h-4" />
                        Accept Crop
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.vue-advanced-cropper {
    background: transparent;
}
.vue-simple-handler {
    background: var(--primary) !important;
}
.vue-simple-line {
    border-color: var(--primary) !important;
}
</style>
