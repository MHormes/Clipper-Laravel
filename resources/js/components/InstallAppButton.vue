<script setup lang="ts">
import { useInstallPrompt } from '@/composables/useInstallPrompt';
import { Button } from '@/components/ui';
import { Download } from '@lucide/vue';

interface Props {
    fullWidth?: boolean;
    label?: string;
}

withDefaults(defineProps<Props>(), {
    fullWidth: false,
    label: 'Download mobile app',
});

const { canInstall, promptInstall } = useInstallPrompt();
</script>

<template>
    <Button
        v-if="canInstall"
        type="button"
        variant="ghost"
        class="text-primary-content hover:bg-muted-background"
        :class="fullWidth ? 'flex w-full justify-start rounded-md px-3 py-2 text-sm font-normal' : 'flex w-full justify-center rounded-md px-0 py-2'"
        @click="promptInstall"
    >
        <Download class="size-4" />
        <span :class="fullWidth ? 'truncate' : 'hidden sm:inline'">{{ label }}</span>
    </Button>
</template>
