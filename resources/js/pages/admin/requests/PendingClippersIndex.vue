<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Clock, Check, X, Layers } from 'lucide-vue-next';

defineProps<{
    groupedClippers: Record<string, Array<{
        id: string;
        series_id: string;
        series_number: number;
        image_data: string;
        created_at: string;
        requester: { name: string };
        series: { name: string; image_data: string };
    }>>;
}>();

const form = useForm({});

const acceptClipper = (id: string) => {
    if (confirm('Accept this clipper?')) {
        form.post(route('admin.requests.clippers.accept', id));
    }
};

const declineClipper = (id: string) => {
    if (confirm('Decline and delete this clipper request?')) {
        form.delete(route('admin.requests.clippers.decline', id));
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric'
    });
};
</script>

<template>
    <Head title="Pending Clipper Requests" />
    <AppLayout>
        <div class="max-w-7xl mx-auto p-6">
            <div class="mb-10">
                <h1 class="text-4xl font-black uppercase tracking-tighter">Clipper Requests</h1>
                <p class="text-muted-content font-bold uppercase tracking-widest text-xs mt-1">Review additions to existing series</p>
            </div>

            <div v-if="Object.keys(groupedClippers).length === 0" class="bg-white dark:bg-[#161615] rounded-3xl p-20 border border-border-color text-center shadow-sm">
                <div class="bg-orange-500/10 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <Clock class="w-10 h-10 text-orange-600" />
                </div>
                <h2 class="text-2xl font-black uppercase tracking-tight mb-2">No Pending Clippers</h2>
                <p class="text-muted-content font-medium max-w-md mx-auto">All clipper requests have been processed. Great job!</p>
            </div>

            <div v-else class="space-y-12">
                <div v-for="(clippers, seriesId) in groupedClippers" :key="seriesId" class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl overflow-hidden border border-border-color shadow-sm">
                            <img :src="clippers[0].series.image_data" class="w-full h-full object-cover" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-tight leading-none">{{ clippers[0].series.name }}</h3>
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-content mt-1">{{ clippers.length }} Pending Requests</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                        <div v-for="clipper in clippers" :key="clipper.id"
                             class="bg-white dark:bg-[#161615] rounded-2xl border border-border-color overflow-hidden group/item shadow-sm hover:border-orange-500/30 transition-all">

                            <div class="aspect-[1/4] relative bg-gray-50 dark:bg-black overflow-hidden border-b border-border-color">
                                <img :src="clipper.image_data" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover/item:scale-110" />

                                <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 to-transparent flex justify-center gap-2 translate-y-full group-hover/item:translate-y-0 transition-transform">
                                    <button @click="acceptClipper(clipper.id)" class="p-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors shadow-lg">
                                        <Check class="w-4 h-4" />
                                    </button>
                                    <button @click="declineClipper(clipper.id)" class="p-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors shadow-lg">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-orange-600">Slot #{{ clipper.series_number }}</span>
                                    <span class="text-[8px] font-bold text-muted-content">{{ formatDate(clipper.created_at) }}</span>
                                </div>
                                <p class="text-[10px] font-bold text-muted-content truncate uppercase tracking-tight">
                                    {{ clipper.requester.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
