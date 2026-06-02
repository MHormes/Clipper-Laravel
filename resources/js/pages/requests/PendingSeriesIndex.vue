<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Clock, User as UserIcon, Layers, ClipboardList } from 'lucide-vue-next';

defineProps<{
    series: Array<{
        id: string;
        name: string;
        image_data: string;
        clippers_count: number;
        created_at: string;
        requester: { name: string };
    }>;
}>();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
</script>

<template>

    <Head title="My Pending Series Requests" />
    <AppLayout>
        <div class="max-w-7xl mx-auto p-4 md:p-6">
            <div class="flex items-center gap-4 mb-10">
                <div class="p-3 rounded-2xl bg-primary/10 text-primary">
                    <ClipboardList class="w-8 h-8" />
                </div>
                <div>
                    <h1 class="text-4xl font-black uppercase tracking-tighter text-primary-content">My Series Requests
                    </h1>
                    <p class="text-muted-content font-bold uppercase tracking-widest text-xs mt-1">Track your new series
                        submissions</p>
                </div>
            </div>

            <div v-if="series.length === 0"
                class="bg-component-background rounded-3xl p-20 border border-border-color text-center shadow-sm relative overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl pointer-events-none">
                </div>
                <div class="relative z-10">
                    <div class="bg-primary/10 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <Clock class="w-10 h-10 text-primary" />
                    </div>
                    <h2 class="text-2xl font-black uppercase tracking-tight mb-2">No Pending Series</h2>
                    <p class="text-muted-content font-medium max-w-md mx-auto uppercase text-xs tracking-widest">You
                        don't have any series requests waiting for approval.</p>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="item in series" :key="item.id"
                    class="bg-component-background rounded-3xl overflow-hidden border border-border-color hover:border-primary/50 transition-all group/card shadow-sm">
                    <div class="aspect-[4/3] relative overflow-hidden bg-media-bg">
                        <img :src="item.image_data"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-105" />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-60">
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-black uppercase tracking-tight mb-4 truncate text-primary-content">{{
                            item.name }}</h3>

                        <div class="space-y-3 mb-2">
                            <div
                                class="flex items-center gap-3 text-xs font-bold text-muted-content uppercase tracking-widest">
                                <div class="p-1.5 rounded-lg bg-primary/10">
                                    <UserIcon class="w-3.5 h-3.5 text-primary" />
                                </div>
                                <span>{{ item.requester.name }}</span>
                            </div>
                            <div
                                class="flex items-center gap-3 text-xs font-bold text-muted-content uppercase tracking-widest">
                                <div class="p-1.5 rounded-lg bg-primary/10">
                                    <Layers class="w-3.5 h-3.5 text-primary" />
                                </div>
                                <span>{{ item.clippers_count }} Clippers</span>
                            </div>
                            <div
                                class="flex items-center gap-3 text-xs font-bold text-muted-content uppercase tracking-widest">
                                <div class="p-1.5 rounded-lg bg-primary/10">
                                    <Clock class="w-3.5 h-3.5 text-primary" />
                                </div>
                                <span>{{ formatDate(item.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
