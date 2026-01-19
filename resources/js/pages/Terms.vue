<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Terms of Use - Clipper-MS" />

    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <nav class="border-b border-gray-100 dark:border-white/10">
            <div class="mx-auto max-w-4xl px-6 py-6 flex justify-between items-center">
                <Link href="/" class="text-sm font-bold flex items-center gap-2 hover:text-[#f53003] transition">
                    ← Back to Home
                </Link>
                <span class="text-xs font-mono opacity-50 uppercase tracking-widest">Legal Framework / NL</span>
            </div>
        </nav>

        <main class="mx-auto max-w-3xl px-6 py-16">
            <h1 class="text-4xl font-black tracking-tighter lg:text-5xl">Terms of Use</h1>
            <p class="mt-4 text-[#706f6c] dark:text-[#A1A09A]">Effective Date: January 2026</p>

            <div class="mt-12 space-y-12">
                <section>
                    <h2 class="text-xl font-bold border-b border-gray-100 pb-2 dark:border-white/10">1. Account Security</h2>
                    <p class="mt-4 leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                        To use Clipper-MS, you must create an account. You are responsible for maintaining the confidentiality of your password. We are not liable for any loss resulting from unauthorized access to your account.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold border-b border-gray-100 pb-2 dark:border-white/10">2. Acceptable Use & Behavior</h2>
                    <p class="mt-4 leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                        By using this platform, you agree <strong>not</strong> to:
                    </p>
                    <ul class="mt-4 space-y-3 list-disc list-inside text-[#706f6c] dark:text-[#A1A09A]">
                        <li>Use the service for any illegal purpose under Dutch or International law.</li>
                        <li>Attempt to "scrape," crawl, or extract mass data from our clipper database.</li>
                        <li>Upload or enter offensive, hateful, or copyrighted material into the "notes" or "location" fields.</li>
                        <li>Attempt to circumvent any security features or rate limits of the system.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold border-b border-gray-100 pb-2 dark:border-white/10">3. "As-Is" Service</h2>
                    <p class="mt-4 leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                        Clipper-MS is provided "as is." While we strive for 100% uptime and data integrity, we do not guarantee that the service will be error-free or that your collection data will be permanently available. We recommend keeping personal backups of important collection data.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold border-b border-gray-100 pb-2 dark:border-white/10">4. Termination of Use</h2>
                    <p class="mt-4 leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                        We reserve the right to suspend or terminate accounts that violate these terms or disrupt the service for other users without prior notice.
                    </p>
                </section>

                <section class="rounded-3xl bg-[#1b1b18] p-8 text-white dark:bg-[#161615]">
                    <h2 class="text-xl font-bold">5. Governing Law</h2>
                    <p class="mt-4 text-gray-400 text-sm">
                        These terms are governed by and construed in accordance with the laws of <strong>The Netherlands</strong>. Any disputes relating to these terms shall be subject to the exclusive jurisdiction of the Dutch courts.
                    </p>
                </section>
            </div>
        </main>

        <footer class="mt-20 py-12 text-center border-t border-gray-100 dark:border-white/10">
            <p class="text-xs text-[#706f6c]">Clipper-MS — Built for collectors, by collectors.</p>
        </footer>   
    </div>
</template>