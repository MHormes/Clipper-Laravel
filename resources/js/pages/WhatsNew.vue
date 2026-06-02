<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Version {
    number: string;
    date: string;
    title: string;
    changes: string[];
    isOpen: boolean;
}

const versions = ref<Version[]>([
    {
        number: '1.0',
        date: 'June 2026',
        title: 'Initial Release',
        changes: [
            'Clipper-ms.com has been released! We are no longer in beta!'
        ],
        isOpen: true
    }
]);

const toggleVersion = (index: number) => {
    versions.value[index].isOpen = !versions.value[index].isOpen;
};
</script>

<template>

    <Head title="What's New - Clipper-MS" />

    <div class="min-h-screen bg-primary-background text-primary-content">
        <nav class="border-b border-border-color">
            <div class="mx-auto max-w-4xl px-6 py-6 flex justify-between items-center">
                <Link href="/" class="text-sm font-bold flex items-center gap-2 hover:text-primary transition">
                    ← Back to Home
                </Link>
                <span class="text-xs font-mono opacity-50 text-right text-balance">VERSION HISTORY</span>
            </div>
        </nav>

        <main class="mx-auto max-w-3xl px-6 py-16 lg:py-24">
            <h1 class="text-4xl font-black tracking-tighter lg:text-5xl uppercase">What's New</h1>
            <p class="mt-4 text-muted-content font-medium">
                Keep track of the latest updates and improvements to Clipper-MS.
            </p>

            <div class="mt-12 space-y-4">
                <div v-for="(version, index) in versions" :key="version.number"
                    class="border border-border-color rounded-2xl overflow-hidden transition-all duration-200"
                    :class="{ 'ring-1 ring-primary/20': version.isOpen }">
                    <button @click="toggleVersion(index)"
                        class="w-full flex justify-between items-center p-6 bg-muted-background transition-colors text-left">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold uppercase tracking-widest text-primary mb-1">Version {{
                                version.number }}</span>
                            <span class="text-xl font-black uppercase tracking-tight">{{ version.title }}</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary transition-transform duration-200"
                            :class="{ 'rotate-180': version.isOpen }">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </button>
                    <div v-if="version.isOpen" class="p-6 border-t border-border-color bg-primary-background">
                        <p class="text-xs text-muted-content font-bold uppercase tracking-widest mb-6 opacity-60">Released: {{
                            version.date }}</p>
                        <ul class="space-y-4">
                            <li v-for="change in version.changes" :key="change" class="flex gap-3">
                                <span class="text-primary font-bold mt-0.5">•</span>
                                <span class="text-muted-content font-medium leading-relaxed">{{ change }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <footer class="mt-20 py-12 text-center border-t border-border-color">
            <p class="text-xs text-muted-content font-bold uppercase tracking-widest opacity-50">Clipper-MS is
                continuously evolving. Stay tuned for more.</p>
        </footer>
    </div>
</template>
