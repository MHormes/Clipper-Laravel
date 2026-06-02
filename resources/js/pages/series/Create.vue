<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import SeriesForm from '@/components/series/SeriesForm.vue';
import { route } from 'ziggy-js';
import { computed } from 'vue';

const page = usePage();
const isAdmin = computed(() => page.props.auth.is_admin);

const submit = (form: any) => {
    form.post(route('series.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>

    <Head :title="isAdmin ? 'Register New Series' : 'Request New Series'" />
    <AppLayout>
        <div class="max-w-5xl mx-auto p-4 md:p-6">
            <h1 class="text-3xl font-black mb-8 uppercase tracking-tighter">
                {{ isAdmin ? 'New Series' : 'Request Series' }}
            </h1>
            <SeriesForm :mode="isAdmin ? 'create' : 'request'"
                :submit-label="isAdmin ? 'Create Series' : 'Submit Request'" @submit="submit" />
        </div>
    </AppLayout>
</template>
