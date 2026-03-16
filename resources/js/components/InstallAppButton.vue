<script setup lang="ts">
import { useInstallPrompt } from '@/composables/useInstallPrompt';
import { Button } from '@/components/ui';
import { Download } from 'lucide-vue-next';

const { canInstall, promptInstall } = useInstallPrompt();
</script>

<template>
    <Button
        v-if="canInstall"
        type="button"
        variant="outline"
        size="sm"
        class="border-border-color bg-component-background text-primary-content hover:bg-muted-background"
        @click="promptInstall"
    >
        <Download class="size-4" />
        <span class="hidden sm:inline">Install app</span>
    </Button>
</template>
