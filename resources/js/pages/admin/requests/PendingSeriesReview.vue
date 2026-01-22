<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ChevronLeft, CheckCircle2, XCircle, AlertCircle, Info } from 'lucide-vue-next';

const props = defineProps<{
    series: any;
}>();

const selectedClippers = ref<string[]>(props.series.clippers.map((c: any) => c.id));
const allSelected = computed(() => selectedClippers.value.length === props.series.clippers.length);

const toggleAll = () => {
    if (allSelected.value) {
        selectedClippers.value = [];
    } else {
        selectedClippers.value = props.series.clippers.map((c: any) => c.id);
    }
};

const form = useForm({
    mode: 'full' as 'full' | 'partial',
    clipper_ids: [] as string[],
});

const acceptFull = () => {
    if (confirm('Are you sure you want to accept this series and ALL its clippers?')) {
        form.mode = 'full';
        form.post(route('admin.requests.series.accept', props.series.id));
    }
};

const acceptPartial = () => {
    if (selectedClippers.value.length === 0) {
        alert('Please select at least one clipper to accept.');
        return;
    }

    const message = selectedClippers.value.length === props.series.clippers.length 
        ? 'Are you sure you want to accept all clippers?' 
        : `Are you sure you want to accept ${selectedClippers.value.length} clippers? Unselected clippers will be PERMANENTLY DELETED.`;

    if (confirm(message)) {
        form.mode = 'partial';
        form.clipper_ids = selectedClippers.value;
        form.post(route('admin.requests.series.accept', props.series.id));
    }
};

const declineSeries = () => {
    if (confirm('Are you sure you want to DECLINE and PERMANENTLY DELETE this entire series request?')) {
        form.delete(route('admin.requests.series.decline', props.series.id));
    }
};
</script>

<template>
    <Head :title="'Review - ' + series.name" />
    <AppLayout>
        <div class="max-w-6xl mx-auto p-6">
            <div class="mb-10">
                <Link :href="route('admin.requests.series.index')" class="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground hover:text-orange-600 transition-colors uppercase tracking-widest">
                    <ChevronLeft class="w-4 h-4" /> Back to Requests
                </Link>
                <div class="flex items-center justify-between mt-4">
                    <h1 class="text-4xl font-black uppercase tracking-tighter">Review Series</h1>
                    <div class="flex items-center gap-3">
                        <button @click="declineSeries" class="px-6 py-3 bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white rounded-xl font-bold uppercase tracking-widest text-xs transition-all border border-red-600/20">
                            Decline Request
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Series Info -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white dark:bg-[#161615] rounded-3xl border border-sidebar-border p-6 shadow-sm">
                        <h2 class="text-xs font-black uppercase tracking-[0.2em] text-orange-600 mb-6 flex items-center gap-2">
                             Series Overview
                        </h2>
                        
                        <div class="aspect-[4/3] rounded-2xl overflow-hidden mb-6 bg-gray-100 dark:bg-black border border-sidebar-border">
                            <img :src="series.image_data" class="w-full h-full object-cover" />
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Series Name</label>
                                <p class="text-lg font-black uppercase tracking-tight">{{ series.name }}</p>
                            </div>
                            <div class="pt-4 border-t border-sidebar-border">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Requested By</label>
                                <p class="font-bold text-sidebar-foreground">{{ series.requester.name }}</p>
                                <p class="text-[10px] font-mono text-muted-foreground">{{ series.requester.email }}</p>
                            </div>
                            <div class="pt-4 border-t border-sidebar-border flex items-center justify-between">
                                <label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Custom Series</label>
                                <span :class="series.custom ? 'bg-orange-500/10 text-orange-600' : 'bg-blue-500/10 text-blue-600'" 
                                      class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest">
                                    {{ series.custom ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-600/5 border border-blue-600/20 rounded-3xl p-6">
                        <div class="flex items-start gap-3">
                            <Info class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                            <div class="text-xs text-blue-800 dark:text-blue-200 font-bold uppercase tracking-widest leading-loose">
                                As an admin, you can selectively approve clippers. Unselected items will be deleted.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Clippers Review -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-[#161615] rounded-3xl border border-sidebar-border shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-sidebar-border flex items-center justify-between bg-gray-50/50 dark:bg-black/20">
                            <h2 class="text-xs font-black uppercase tracking-[0.2em] text-orange-600">
                                Clippers ({{ selectedClippers.length }} / {{ series.clippers.length }} Selected)
                            </h2>
                            <button @click="toggleAll" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:text-orange-600 transition-colors">
                                {{ allSelected ? 'Deselect All' : 'Select All' }}
                            </button>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
                                <div v-for="clipper in series.clippers" :key="clipper.id" 
                                     @click="selectedClippers.includes(clipper.id) ? selectedClippers = selectedClippers.filter(id => id !== clipper.id) : selectedClippers.push(clipper.id)"
                                     class="relative group cursor-pointer">
                                    <div :class="selectedClippers.includes(clipper.id) ? 'border-orange-500 ring-2 ring-orange-500/20' : 'border-sidebar-border opacity-60 hover:opacity-100'"
                                         class="aspect-[1/4] rounded-xl overflow-hidden border-2 transition-all relative bg-gray-50 dark:bg-black">
                                        <img :src="clipper.image_data" class="w-full h-full object-cover" />
                                        
                                        <div v-if="selectedClippers.includes(clipper.id)" 
                                             class="absolute top-2 right-2 bg-orange-600 text-white rounded-full p-1 shadow-lg">
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                        </div>
                                        <div v-else class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <p class="text-[10px] font-black uppercase tracking-tighter text-muted-foreground">Slot #{{ clipper.series_number }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-8 border-t border-sidebar-border bg-gray-50/50 dark:bg-black/20 flex flex-col sm:flex-row gap-4">
                            <button @click="acceptFull" class="flex-1 py-4 bg-orange-600 text-white rounded-2xl font-black uppercase tracking-[0.2em] text-xs hover:bg-orange-700 transition-all shadow-lg shadow-orange-900/20 active:scale-95 flex items-center justify-center gap-2">
                                <CheckCircle2 class="w-4 h-4" /> Accept All
                            </button>
                            <button @click="acceptPartial" 
                                    :disabled="selectedClippers.length === 0"
                                    class="flex-1 py-4 bg-white dark:bg-black text-orange-600 border border-orange-600/30 rounded-2xl font-black uppercase tracking-[0.2em] text-xs hover:bg-orange-600 hover:text-white transition-all disabled:opacity-50 disabled:hover:bg-transparent disabled:hover:text-orange-600 flex items-center justify-center gap-2">
                                Accept Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
