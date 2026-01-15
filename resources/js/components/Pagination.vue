<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
defineProps<{ links: Array<any> }>();
</script>

<template>
    <div v-if="links.length > 3" class="mt-12 mb-6 flex justify-center gap-2">
        <template v-for="(link, k) in links" :key="k">
            <span v-if="link.url === null" v-html="link.label" 
                class="px-5 py-2.5 rounded-xl text-sm border font-bold opacity-50 cursor-not-allowed bg-white dark:bg-black border-sidebar-border" />
            <Link v-else :href="link.url" v-html="link.label"
                class="px-5 py-2.5 rounded-xl text-sm border font-bold transition-all shadow-sm"
                :class="link.active ? 'bg-orange-600 text-white border-orange-600' : 'bg-white dark:bg-black border-sidebar-border hover:border-orange-500/50'" />
        </template>
    </div>
</template>