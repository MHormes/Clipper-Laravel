<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Map as MapIcon, ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Map View" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <div class="mb-8">
                <Link 
                    :href="route('collection.index')" 
                    class="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground hover:text-orange-600 transition-colors"
                >
                    <ArrowLeft class="w-4 h-4" />
                    BACK TO COLLECTION
                </Link>
            </div>

            <div class="min-h-[600px] flex flex-col items-center justify-center bg-white dark:bg-[#161615] rounded-3xl border border-dashed border-sidebar-border shadow-sm p-12 text-center">
                
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-orange-500/20 blur-3xl rounded-full"></div>
                    <div class="relative p-8 rounded-full bg-gray-50 dark:bg-white/5 text-orange-600">
                        <MapIcon class="w-20 h-20" />
                    </div>
                </div>

                <h1 class="text-4xl font-black uppercase tracking-tight mb-4 leading-tight">
                    Map View <br/>
                    <span class="text-orange-600">In Development</span>
                </h1>
                
                <p class="text-muted-foreground text-lg max-w-md mx-auto mb-10">
                    We're working on an interactive map to help you visualize your collection's origin. Check back soon for the official release!
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <Link 
                        :href="route('dashboard')"
                        class="px-8 py-4 bg-orange-600 text-white hover:bg-orange-700 rounded-2xl font-black transition-all shadow-lg shadow-orange-900/20 active:scale-95"
                    >
                        RETURN TO DASHBOARD
                    </Link>
                    
                    <Link 
                        :href="route('series.index')"
                        class="px-8 py-4 bg-gray-100 dark:bg-white/5 text-foreground hover:bg-gray-200 dark:hover:bg-white/10 rounded-2xl font-black transition-all"
                    >
                        BROWSE CATALOG
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Optional: Subtle pulse animation for the icon glow */
.bg-orange-500\/20 {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.1); }
}
</style>