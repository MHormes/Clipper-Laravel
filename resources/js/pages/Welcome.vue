<script setup lang="ts">
import { login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canRegister: boolean;
}>();
</script>

<template>

    <Head title="Welcome to Clipper MS" />

    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <header class="mb-12 w-full max-w-4xl flex justify-end">
            <nav class="flex gap-4">
                <Link :href="login()" class="px-4 py-2 text-sm font-medium hover:opacity-70 transition">
                    Log in
                </Link>
                <Link v-if="canRegister" :href="register()"
                    class="rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white dark:bg-[#eeeeec] dark:text-[#1C1C1A]">
                    Register
                </Link>
            </nav>
        </header>

        <main class="w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl lg:flex dark:bg-[#161615]">
            <div class="flex-1 p-10 lg:p-20 flex flex-col justify-center">
                <h1 class="text-5xl font-black tracking-tighter">Clipper MS</h1>
                <p class="mt-6 text-xl leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                    The ultimate management system for Clipper enthusiasts.
                    Discover, catalog, and complete your collection of legendary 4-design series.
                </p>

                <div class="mt-10 space-y-6">
                    <div
                        class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/30 dark:bg-orange-900/10">
                        <p class="text-sm font-semibold text-orange-700 dark:text-orange-400">
                            System Access Requirement:
                            <span class="font-normal">Please note that you will be required to log in upon startup to
                                access the database.</span>
                        </p>
                    </div>

                    <Link v-if="canRegister" :href="register()"
                        class="inline-block rounded-xl bg-[#f53003] px-10 py-4 text-lg font-bold text-white shadow-lg transition hover:scale-105 hover:bg-[#ff4433]">
                        Start Your Collection
                    </Link>
                </div>
            </div>

            <div class="relative hidden bg-[#fff2f2] lg:block lg:w-5/12 dark:bg-[#1D0002]">
                <img src="/images/hero.png" alt="Clipper Series Display"
                    class="h-full w-full object-cover grayscale-[20%] hover:grayscale-0 transition duration-700"
                    @error="(e) => (e.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1595113316349-9fa4ee24f884?auto=format&fit=crop&q=80&w=800'">
            </div>
        </main>
    </div>
</template>