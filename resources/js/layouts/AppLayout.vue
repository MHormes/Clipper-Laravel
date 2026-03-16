<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalFooter from '@/components/GlobalFooter.vue';
import InstallAppButton from '@/components/InstallAppButton.vue';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui';
import type { AppPageProps, BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<AppPageProps>();
const isOpen = page.props.sidebarOpen;
</script>

<template>
    <SidebarProvider :default-open="isOpen">
        <AppSidebar />
        <SidebarInset class="overflow-x-hidden">
            <header
                class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-border-color px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
            >
                <div class="flex items-center gap-2">
                    <SidebarTrigger class="-ml-1" />
                    <template v-if="breadcrumbs && breadcrumbs.length > 0">
                        <Breadcrumbs :breadcrumbs="breadcrumbs" />
                    </template>
                </div>
                <InstallAppButton />
            </header>
            <slot />
            <GlobalFooter />
        </SidebarInset>
    </SidebarProvider>
</template>
