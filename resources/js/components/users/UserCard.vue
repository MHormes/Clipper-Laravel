<script setup lang="ts">
import { Calendar, CheckCircle2, Package, Sparkles } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

interface DirectoryUser {
    id: string;
    name: string;
    created_at: string;
    collected_clippers_count: number;
    completed_series_count: number;
    contributions_count: number;
}

defineProps<{
    user: DirectoryUser;
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Link :href="route('users.show', user.id)" class="block rounded-2xl border border-border-color bg-component-background p-5 transition-all hover:border-primary/40 hover:shadow-sm hover:-translate-y-0.5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="truncate text-lg font-black uppercase tracking-tight text-primary-content">{{ user.name }}</h2>
                <p class="mt-1 flex items-center gap-1 text-xs font-bold text-muted-content uppercase tracking-wider">
                    <Calendar class="size-3.5" />
                    Joined {{ formatDate(user.created_at) }}
                </p>
            </div>
            <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary text-lg font-black">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-border-color bg-primary-background p-3">
                <p class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-content">
                    <Package class="size-3.5" />
                    Collected
                </p>
                <p class="mt-1 text-2xl font-black text-primary-content">{{ user.collected_clippers_count }}</p>
            </div>

            <div class="rounded-xl border border-border-color bg-primary-background p-3">
                <p class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-content">
                    <CheckCircle2 class="size-3.5" />
                    Completed
                </p>
                <p class="mt-1 text-2xl font-black text-primary-content">{{ user.completed_series_count }}</p>
            </div>

            <div class="rounded-xl border border-border-color bg-primary-background p-3">
                <p class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-content">
                    <Sparkles class="size-3.5" />
                    Contributions
                </p>
                <p class="mt-1 text-2xl font-black text-primary-content">{{ user.contributions_count }}</p>
            </div>
        </div>
    </Link>
</template>
