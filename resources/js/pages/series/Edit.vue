<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import SeriesForm from '@/components/series/SeriesForm.vue';
import { route } from 'ziggy-js';

const props = defineProps<{ series: any }>();

const submit = (form: any) => {
    console.log(form)
    form.transform((data: any) => ({
        ...data,
        _method: 'PUT',
    })).post(route('series.update', props.series.id), {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors: Record<string, string>) => {
            console.error("Server-side validation failed:", errors);
        }
    });
};
</script>

<template>
    <Head :title="'Edit ' + series.name" />
    <AppLayout>
        <div class="max-w-5xl mx-auto p-6">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tighter">Edit Series</h1>
                    <p class="text-[10px] text-muted-foreground font-mono">{{ series.id }}</p>
                </div>
                <Link :href="route('series.show', series.id)" class="text-sm font-bold text-orange-600 hover:underline">View Live Page</Link>
            </div>
            
            <SeriesForm 
                mode="edit"
                :initial-data="series" 
                submit-label="Save Changes" 
                @submit="submit" 
            />
        </div>
    </AppLayout>
</template>