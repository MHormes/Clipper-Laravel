<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import SeriesForm from '@/components/series/SeriesForm.vue';
import { route } from 'ziggy-js';

const submit = (form: any) => {
    form.post(route('series.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Register New Series" />
    <AppLayout>
        <div class="max-w-5xl mx-auto p-6">
            <h1 class="text-3xl font-black mb-8 uppercase tracking-tighter">New Series</h1>
            <SeriesForm submit-label="Create Series" @submit="submit" />
        </div>
    </AppLayout>
</template>