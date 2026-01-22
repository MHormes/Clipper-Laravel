<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import SeriesForm from '@/components/series/SeriesForm.vue';
import { route } from 'ziggy-js';
import { ChevronLeft } from 'lucide-vue-next';

const props = defineProps<{
    series: any;
}>();

const submit = (form: any) => {
    form.post(route('series.store-clipper-request', props.series.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="'Request Clippers - ' + series.name" />
    <AppLayout>
        <div class="max-w-5xl mx-auto p-6">
            <div class="mb-8">
                <Link :href="route('series.show', series.id)" class="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground hover:text-orange-600 transition-colors uppercase tracking-widest">
                    <ChevronLeft class="w-4 h-4" /> Back to {{ series.name }}
                </Link>
                <h1 class="text-3xl font-black mt-4 uppercase tracking-tighter">Request Clippers</h1>
                <p class="text-sm text-muted-foreground font-bold mt-1 uppercase tracking-widest">For {{ series.name }}</p>
            </div>

            <SeriesForm 
                mode="clipper-request"
                :initial-data="series"
                submit-label="Submit Clipper Request" 
                @submit="submit" 
            />
        </div>
    </AppLayout>
</template>
