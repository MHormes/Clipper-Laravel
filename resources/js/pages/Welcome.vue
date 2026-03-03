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

    <div class="min-h-screen bg-primary-background text-primary-content selection:bg-primary selection:text-button-content">
        
        <nav class="fixed top-0 z-50 w-full border-b border-border-color bg-primary-background/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="h-12 w-12"/>
                    <span class="text-xl font-black tracking-tighter">CLIPPER<span class="text-primary">-MS</span></span>
                </div>
                <div class="flex items-center gap-4">
                    <Link :href="login()" class="text-sm font-medium hover:text-primary transition">
                        Log in
                    </Link>
                    <Link v-if="canRegister" :href="register()"
                        class="rounded-full bg-primary px-5 py-2 text-sm font-black text-button-content transition hover:scale-105 shadow-lg shadow-primary/20">
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
                        <span class="text-primary">Collection.</span>
                    </h1>
                    <p class="mt-8 text-xl leading-8 text-muted-content max-w-2xl mx-auto lg:mx-0">
                        The ultimate management system for Clipper enthusiasts. 
                        Discover, catalog, and complete your collection of legendary 4-design series with precision.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                        <Link v-if="canRegister" :href="register()"
                            class="rounded-full bg-primary px-8 py-4 text-lg font-bold text-button-content shadow-2xl shadow-primary/20 transition hover:scale-105">
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

        <section class="bg-primary-background py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div v-for="stat in stats" :key="stat.label" class="group rounded-3xl border border-border-color bg-component-background p-8 text-center transition hover:border-primary/30">
                        <p class="text-5xl font-black text-primary">{{ stat.value }}</p>
                        <p class="mt-2 font-bold uppercase tracking-widest text-[10px] text-muted-content opacity-70">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-24">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <div class="rounded-[3rem] bg-[var(--primary-content)] p-12 text-button-content shadow-2xl relative overflow-hidden dark:bg-[#1D0002]">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl pointer-events-none"></div>
                    
                    <h2 class="text-4xl font-black uppercase tracking-tight relative z-10">Ready to finish that set?</h2>
                    <p class="mt-4 text-button-content/80 font-medium relative z-10">Join other collectors and get your collection organized today.</p>
                    <Link v-if="canRegister" :href="register()"
                        class="mt-8 inline-block rounded-full bg-primary px-10 py-4 font-black uppercase tracking-widest text-xs text-button-content transition hover:scale-105 shadow-xl shadow-black/20 relative z-10">
                        Create Free Account
                    </Link>
                </div>
            </div>
        </section>

        <GlobalFooter />
    </div>
</template>
