<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Privacy Policy - Clipper MS" />

    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <nav class="border-b border-gray-100 dark:border-white/10">
            <div class="mx-auto max-w-4xl px-6 py-6 flex justify-between items-center">
                <Link href="/" class="text-sm font-bold flex items-center gap-2 hover:text-[#f53003] transition">
                    ← Back to Home
                </Link>
                <span class="text-xs font-mono opacity-50 text-right text-balance">GDPR COMPLIANT / NL</span>
            </div>
        </nav>

        <main class="mx-auto max-w-3xl px-6 py-16 lg:py-24">
            <h1 class="text-4xl font-black tracking-tighter lg:text-5xl">Privacy Policy</h1>
            <p class="mt-4 text-[#706f6c] dark:text-[#A1A09A]">
                Last updated: January 2026 • Clipper MS (Netherlands)
            </p>

            <div class="mt-12 space-y-12 prose prose-orange dark:prose-invert">
                <section>
                    <h2 class="text-xl font-bold">1. Data We Collect</h2>
                    <p class="mt-4 leading-relaxed">
                        We follow the principle of <strong>data minimization</strong>. We only process the following:
                    </p>
                    <ul class="mt-4 space-y-4 text-[#706f6c] dark:text-[#A1A09A]">
                        <li class="flex gap-3">
                            <span class="text-[#f53003] font-bold">01.</span>
                            <span><strong>Account Credentials:</strong> Your email and a hashed version of your password.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-[#f53003] font-bold">02.</span>
                            <span><strong>Collection Data:</strong> Any information you explicitly enter about your collection, including <strong>purchase locations</strong> and <strong>personal notes</strong>.</span>
                        </li>
                    </ul>
                    <p class="mt-6 text-sm italic border-l-2 border-orange-500 pl-4">
                        Note: We do not use background GPS tracking. Location data is only saved when you manually type it into the "Location Bought" field.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold">2. Functional Cookies Only</h2>
                    <p class="mt-4 leading-relaxed">
                        We do not use tracking pixels, marketing cookies, or third-party analytics. 
                        Our cookies are strictly <strong>functional</strong>, used solely to maintain your 
                        authenticated session so you don't have to log in every time you refresh the page.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold">3. Data Sovereignty & Security</h2>
                    <p class="mt-4 leading-relaxed">
                        Your data is stored on secure servers located within the European Economic Area (EEA). 
                        We never sell or share your data with third parties.
                    </p>
                </section>

                <section class="rounded-3xl bg-orange-50 p-8 dark:bg-orange-950/20">
                    <h2 class="text-xl font-bold text-orange-800 dark:text-orange-400">4. Your GDPR Rights</h2>
                    <ul class="mt-4 space-y-2 list-disc list-inside text-orange-900/80 dark:text-orange-300/80">
                        <li>Right to access your stored data.</li>
                        <li>Right to be forgotten (complete account deletion).</li>
                        <li>Right to data portability.</li>
                    </ul>
                    <p class="mt-6 text-sm">
                        To exercise these rights, simply delete your account in settings or contact us via our support channel.
                    </p>
                </section>
            </div>
        </main>

        <footer class="mt-20 py-12 text-center border-t border-gray-100 dark:border-white/10">
            <p class="text-xs text-[#706f6c]">Clipper MS operates under the jurisdiction of the Netherlands.</p>
        </footer>
    </div>
</template>