<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArrowLeft, UserPlus } from 'lucide-vue-next';
</script>

<template>
    <Head title="Following" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <div class="mb-8">
                <Link
                    :href="route('users.index')"
                    class="inline-flex items-center gap-2 text-sm font-bold text-muted-content hover:text-primary transition-colors"
                >
                    <ArrowLeft class="w-4 h-4" />
                    BACK TO FIND USERS
                </Link>
            </div>

            <div class="min-h-[600px] flex flex-col items-center justify-center bg-component-background rounded-3xl border border-dashed border-border-color shadow-sm p-12 text-center">
                <div class="relative mb-8">
                    <div class="absolute inset-0 bg-primary/20 blur-3xl rounded-full"></div>
                    <div class="relative p-8 rounded-full bg-muted-background text-primary">
                        <UserPlus class="w-20 h-20" />
                    </div>
                </div>

                <h1 class="text-4xl font-black uppercase tracking-tight mb-4 leading-tight text-primary-content">
                    Following <br/>
                    <span class="text-primary">In Development</span>
                </h1>

                <p class="text-muted-content text-lg max-w-md mx-auto mb-10">
                    We are building follower features so you can track collectors you care about. This section will be available soon.
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <Link
                        :href="route('users.index')"
                        class="px-8 py-4 bg-primary text-button-content hover:bg-primary hover:text-button-content! rounded-2xl font-black transition-all shadow-lg shadow-primary/20 active:scale-95"
                    >
                        RETURN TO FIND USERS
                    </Link>

                    <Link
                        :href="route('dashboard')"
                        class="px-8 py-4 bg-muted-background text-primary-content hover:bg-muted-background dark:hover:bg-secondary-content/10 rounded-2xl font-black transition-all"
                    >
                        RETURN TO DASHBOARD
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bg-primary\/20 {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.1); }
}
</style>
