<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import UserCard from '@/components/users/UserCard.vue';
import { Head, router } from '@inertiajs/vue3';
import { Search, Users, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface DirectoryUser {
    id: string;
    name: string;
    created_at: string;
    collected_clippers_count: number;
    completed_series_count: number;
    contributions_count: number;
}

const props = defineProps<{
    users: {
        data: DirectoryUser[];
        links: Array<any>;
        total: number;
    };
    filters?: {
        search?: string;
    };
}>();

const search = ref(props.filters?.search ?? '');
const hasSearch = computed(() => search.value.trim().length > 0);

watch(
    () => props.filters,
    (newFilters) => {
        search.value = newFilters?.search ?? '';
    },
    { deep: true }
);

let timeout: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (timeout) {
        clearTimeout(timeout);
    }

    timeout = setTimeout(() => {
        router.get(
            route('users.index'),
            { search: search.value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});
</script>

<template>

    <Head title="Users" />

    <AppLayout>
        <div class="mx-auto w-full max-w-7xl p-6">
            <div class="mb-8 grid grid-cols-1 items-center gap-6 lg:grid-cols-[1fr,auto]">
                <div class="flex items-center gap-4">
                    <div class="rounded-2xl bg-primary/10 p-3 text-primary">
                        <Users class="size-8" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black uppercase tracking-tight">Find Users</h1>
                        <p class="text-sm text-muted-content">Search collectors and compare collection progress.</p>
                    </div>
                </div>

                <div class="relative w-full lg:w-96">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-content" />
                    <input v-model="search" type="text" placeholder="Search by username..."
                        class="w-full pl-10 pr-10 py-2.5 bg-primary-background border border-border-color rounded-xl focus:ring-2 focus:ring-primary text-sm shadow-sm" />
                    <button v-if="search" @click="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-content p-1">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div v-if="hasSearch" class="mb-6 flex items-center gap-2 px-1">
                <span class="text-[10px] font-black uppercase tracking-widest text-muted-content">
                    Showing <span class="text-primary-content">{{ users.total }}</span> users
                </span>
                <div class="ml-2 h-px flex-1 bg-border-color/30"></div>
            </div>

            <div v-if="users.data.length > 0" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <UserCard v-for="user in users.data" :key="user.id" :user="user" />
            </div>

            <div v-else
                class="flex w-full flex-col items-center justify-center rounded-3xl border border-dashed border-border-color bg-component-background py-24">
                <Users class="mb-5 size-14 text-muted-content" />
                <h2 class="text-2xl font-black uppercase tracking-tight text-primary-content text-center">
                    {{ hasSearch ? 'No users found' : 'Start typing to see results' }}
                </h2>
                <p class="mt-2 text-sm text-muted-content text-center">
                    {{ hasSearch ? 'Try a broader username search.' : 'Search by username to find collectors.' }}
                </p>
            </div>

            <Pagination :links="users.links" />
        </div>
    </AppLayout>
</template>
