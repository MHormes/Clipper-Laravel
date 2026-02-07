<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Eye, Clock, User as UserIcon, Layers } from 'lucide-vue-next';

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
    <Head title="Pending Series Requests" />
    <AppLayout>
        <div class="max-w-7xl mx-auto p-6">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h1 class="text-4xl font-black uppercase tracking-tighter">Series Requests</h1>
                    <p class="text-muted-content font-bold uppercase tracking-widest text-xs mt-1">Review and approve new series submissions</p>
                </div>
            </div>

            <div v-if="series.length === 0" class="bg-white dark:bg-[#161615] rounded-3xl p-20 border border-border-color text-center shadow-sm">
                <div class="bg-orange-500/10 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <Clock class="w-10 h-10 text-orange-600" />
                </div>
                <h2 class="text-2xl font-black uppercase tracking-tight mb-2">All Caught Up!</h2>
                <p class="text-muted-content font-medium max-w-md mx-auto">There are no pending series requests at the moment. Check back later!</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="item in series" :key="item.id"
                     class="bg-white dark:bg-[#161615] rounded-3xl overflow-hidden border border-border-color hover:border-orange-500/50 transition-all group/card shadow-sm">
                    <div class="aspect-[4/3] relative overflow-hidden bg-gray-100 dark:bg-black">
                        <img :src="item.image_data" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-105" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-60"></div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-black uppercase tracking-tight mb-4 truncate">{{ item.name }}</h3>

                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-3 text-xs font-bold text-muted-content uppercase tracking-widest">
                                <UserIcon class="w-4 h-4 text-orange-600" />
                                <span>{{ item.requester.name }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs font-bold text-muted-content uppercase tracking-widest">
                                <Layers class="w-4 h-4 text-orange-600" />
                                <span>{{ item.clippers_count }} Clippers Requested</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs font-bold text-muted-content uppercase tracking-widest">
                                <Clock class="w-4 h-4 text-orange-600" />
                                <span>{{ formatDate(item.created_at) }}</span>
                            </div>
                        </div>

                        <Link :href="route('admin.requests.series.show', item.id)"
                              class="w-full py-4 bg-orange-600 text-white rounded-2xl font-black uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-2 hover:bg-orange-700 transition-all shadow-lg shadow-orange-900/20 active:scale-95">
                            <Eye class="w-4 h-4" /> Review Submission
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
