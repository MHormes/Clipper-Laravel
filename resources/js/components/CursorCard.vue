<script setup lang="ts">
import { ref } from 'vue';

const props = withDefaults(defineProps<{
    className?: string;
    lightColor?: string;
    lightSize?: string;
}>(), {
    className: '',
    lightColor: 'bg-primary/10',
    lightSize: 'w-48 h-48'
});

const lightX = ref('100%');
const lightY = ref('0%');
const isHovering = ref(false);

const handleMouseMove = (e: MouseEvent) => {
    isHovering.value = true;
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    lightX.value = `${e.clientX - rect.left}px`;
    lightY.value = `${e.clientY - rect.top}px`;
};

const handleMouseLeave = () => {
    isHovering.value = false;
};
</script>

<template>
    <div 
        @mousemove="handleMouseMove"
        @mouseleave="handleMouseLeave"
        :class="['group relative overflow-hidden transition-all', className]"
    >
        <!-- Light Shade Effect -->
        <div 
            :class="['absolute rounded-full blur-3xl pointer-events-none transition-opacity duration-500 ease-out', lightColor, lightSize, isHovering ? 'opacity-100' : 'opacity-0']"
            :style="{ left: lightX, top: lightY, transform: 'translate(-50%, -50%)' }"
        ></div>

        <slot />
    </div>
</template>
