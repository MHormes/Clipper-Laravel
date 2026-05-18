<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance Settings" />

        <h1 class="sr-only">Appearance Settings</h1>

        <SettingsLayout>
            <div class="space-y-8 p-1">
                <div class="space-y-1">
                    <HeadingSmall
                        title="Appearance"
                        description="Customize how Clipper-MS looks on your device"
                        class="text-xl font-black tracking-tight"
                    />
                    <div class="h-1 w-12 bg-[var(--primary)] rounded-full mt-2"></div>
                </div>

                <div class="rounded-2xl border border-border-color  p-6 shadow-sm  bg-component-background">
                    <div class="max-w-xl">
                        <p class="mb-6 text-sm text-muted-content">
                            Switch between light and dark modes, or let the app follow your system preference.
                        </p>
                        
                        <AppearanceTabs />
                    </div>
                </div>

                <div class="flex items-start gap-4 rounded-xl bg-[var(--alert-primary-bg)] p-4 border border-[var(--alert-primary-border)]">
                    <div class="text-[var(--primary)] mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    </div>
                    <p class="text-xs leading-relaxed text-primary/80">
                        <strong>GDPR Note:</strong> Your appearance preference is stored locally to ensure a consistent experience without unnecessary tracking.
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
