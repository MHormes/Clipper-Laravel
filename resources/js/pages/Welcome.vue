<script setup lang="ts">
import { login, register } from '@/routes';
import GlobalFooter from '@/components/GlobalFooter.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppLogo from '@/components/AppLogo.vue';

defineProps<{
    canRegister: boolean;
}>();

const stats = [
    { label: 'Total Series', value: '100+' },
    { label: 'Unique Clippers', value: '400+' },
    { label: 'Active Collectors', value: '20+' },
];
</script>

<template>
    <Head>
        <title>Clipper-MS | Track Your Clipper Lighter Collection</title>
    </Head>

    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] selection:bg-orange-500 selection:text-white">
        
        <nav class="fixed top-0 z-50 w-full border-b border-gray-100 bg-white/80 backdrop-blur-md dark:border-white/10 dark:bg-[#0a0a0a]/80">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="h-12 w-12"/>
                    <span class="text-xl font-black tracking-tighter">CLIPPER<span class="text-[#f53003]">-MS</span></span>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="login()" class="text-sm font-medium hover:text-[#f53003] transition">
                        Log in
                    </Link>
                    <Link v-if="canRegister" :href="register()"
                        class="rounded-full bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white transition hover:bg-black/80 dark:bg-[#eeeeec] dark:text-[#1C1C1A]">
                        Get Started
                    </Link>
                </div>
            </div>
        </nav>

        <header class="relative pt-32 pb-16 lg:pt-48 lg:pb-32">
            <div class="mx-auto max-w-7xl px-6 lg:flex lg:items-center lg:gap-12">
                <div class="flex-1 text-center lg:text-left">
                    <h1 class="text-6xl font-black tracking-tight lg:text-7xl">
                        Track Your <br/>
                        <span class="text-[#f53003]">Collection.</span>
                    </h1>
                    <p class="mt-8 text-xl leading-8 text-[#706f6c] dark:text-[#A1A09A] max-w-2xl mx-auto lg:mx-0">
                        The ultimate management system for Clipper enthusiasts. 
                        Discover, catalog, and complete your collection of legendary 4-design series with precision.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                        <Link v-if="canRegister" :href="register()"
                            class="rounded-full bg-[#f53003] px-8 py-4 text-lg font-bold text-white shadow-2xl shadow-orange-500/20 transition hover:scale-105 hover:bg-[#ff4433]">
                            Start Your Collection
                        </Link>
                    </div>
                </div>
                
                <div class="mt-16 flex-1 lg:mt-0 relative">
                    <div class="grid grid-cols-2 gap-4">
                        <img src="/images/hero.png" 
                             class="transform lg:-rotate-3 hover:rotate-0 transition duration-500" alt="Clipper 1" />
                        <img src="/images/hero_2.png" 
                             class="mt-12 transform lg:rotate-3 hover:rotate-0 transition duration-500" alt="Clipper 2" />
                    </div>
                </div>
            </div>
        </header>

        <section class="bg-white py-24 dark:bg-[#111110]">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div v-for="stat in stats" :key="stat.label" class="group rounded-3xl border border-gray-100 p-8 text-center transition hover:border-[#f53003]/30 dark:border-white/5">
                        <p class="text-5xl font-black text-[#f53003]">{{ stat.value }}</p>
                        <p class="mt-2 font-medium text-[#706f6c] dark:text-[#A1A09A]">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-24">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <div class="rounded-[3rem] bg-[#1b1b18] p-12 text-white dark:bg-[#1D0002]">
                    <h2 class="text-4xl font-bold">Ready to finish that set?</h2>
                    <p class="mt-4 text-gray-400">Join other collectors and get your collection organized today.</p>
                    <Link v-if="canRegister" :href="register()"
                        class="mt-8 inline-block rounded-full bg-white px-10 py-4 font-bold text-black transition hover:bg-gray-200">
                        Create Free Account
                    </Link>
                </div>
            </div>
        </section>

        <GlobalFooter />
    </div>
</template>