<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-gray-100 py-12 dark:border-white/5">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex flex-col md:flex-row justify-between items-start gap-8">
                <div class="max-w-md">
                    <p class="text-sm font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">
                        CLIPPER<span class="text-[#f53003]">-MS</span>
                    </p>
                    <p class="text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                        © {{ currentYear }} Clipper-MS. All rights reserved.
                    </p>
                    <p class="mt-4 text-xs leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                        Clipper-MS is a fan-made project and is not affiliated with, endorsed by, or in any way officially connected with Clipper® or Flamagas S.A.
                    </p>
                </div>
                
                <div class="flex flex-wrap gap-8 text-sm font-medium">
                    <div class="flex flex-col gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#1b1b18]/50 dark:text-[#EDEDEC]/50">Legal</span>
                        <Link :href="route('terms')" class="hover:text-[#f53003] transition">Terms of Use</Link>
                        <Link :href="route('privacy')" class="hover:text-[#f53003] transition">Privacy Policy</Link>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#1b1b18]/50 dark:text-[#EDEDEC]/50">Community</span>
                        <a href="https://buymeacoffee.com/mhormes" target="_blank" rel="noopener noreferrer" class="hover:text-[#f53003] transition">Buy me a Coffee</a>
                        <a href="https://github.com/mhormes" target="_blank" rel="noopener noreferrer" class="hover:text-[#f53003] transition">GitHub / Support</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
