<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-border-color py-12">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col md:flex-row justify-between items-start gap-8">
                <div class="max-w-md">
                    <p class="text-sm font-bold text-[var(--primary-content)] dark:text-[var(--primary-content)] mb-2">
                        CLIPPER<span class="text-[var(--primary)]">-MS</span>
                    </p>
                    <p class="text-xs leading-relaxed text-[var(--muted-content)] dark:text-[var(--muted-content)]">
                        © {{ currentYear }} Clipper-MS. All rights reserved.
                    </p>
                    <p class="mt-4 text-xs leading-relaxed text-[var(--muted-content)] dark:text-[var(--muted-content)]">
                        Clipper-MS is a fan-made project and is not affiliated with, endorsed by, or in any way officially connected with Clipper® or Flamagas S.A.
                    </p>
                </div>
                
                <div class="flex flex-wrap gap-8 text-sm font-medium">
                    <div class="flex flex-col gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--primary-content)]/50 dark:text-[var(--primary-content)]/50">Legal</span>
                        <Link :href="route('terms')" class="hover:text-[var(--primary)] transition">Terms of Use</Link>
                        <Link :href="route('privacy')" class="hover:text-[var(--primary)] transition">Privacy Policy</Link>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--primary-content)]/50 dark:text-[var(--primary-content)]/50">Community</span>
                        <a href="https://buymeacoffee.com/mhormes" target="_blank" rel="noopener noreferrer" class="hover:text-[var(--primary)] transition">Buy me a Coffee</a>
                        <a href="https://github.com/mhormes" target="_blank" rel="noopener noreferrer" class="hover:text-[var(--primary)] transition">GitHub / Support</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
