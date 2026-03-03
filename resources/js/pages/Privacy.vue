<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Privacy Policy - Clipper-MS" />

    <div class="min-h-screen bg-primary-background text-primary-content">
        <nav class="border-b border-border-color">
            <div class="mx-auto max-w-4xl px-6 py-6 flex justify-between items-center">
                <Link href="/" class="text-sm font-bold flex items-center gap-2 hover:text-primary transition">
                    ← Back to Home
                </Link>
                <span class="text-xs font-mono opacity-50 text-right text-balance">GDPR COMPLIANT / NL</span>
            </div>
        </nav>

        <main class="mx-auto max-w-3xl px-6 py-16 lg:py-24">
            <h1 class="text-4xl font-black tracking-tighter lg:text-5xl uppercase">Privacy Policy</h1>
            <p class="mt-4 text-muted-content font-medium">
                Last updated: January 2026 • Clipper-MS (Netherlands)
            </p>

            <div class="mt-12 space-y-16">
                <section>
                    <div class="space-y-1">
                        <h2 class="text-xl font-black uppercase tracking-tight">1. Data We Collect</h2>
                        <div class="h-1 w-12 bg-primary rounded-full"></div>
                    </div>
                    <p class="mt-6 leading-relaxed text-muted-content font-medium">
                        We follow the principle of <strong>data minimization</strong>. We only process the following:
                    </p>
                    <ul class="mt-4 space-y-4 text-muted-content font-medium">
                        <li class="flex gap-3">
                            <span class="text-primary font-bold">01.</span>
                            <span><strong>Account Credentials:</strong> Your email and a hashed version of your password.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-primary font-bold">02.</span>
                            <span><strong>Collection Data:</strong> Any information you explicitly enter about your collection, including <strong>purchase locations</strong> and <strong>personal notes</strong>.</span>
                        </li>
                    </ul>
                    <p class="mt-6 text-sm italic border-l-2 border-primary pl-4 text-muted-content/80">
                        Note: We do not use background GPS tracking. Location data is only saved when you manually type it into the "Location Bought" field.
                    </p>
                </section>

                <section>
                    <div class="space-y-1">
                        <h2 class="text-xl font-black uppercase tracking-tight">2. Functional Cookies Only</h2>
                        <div class="h-1 w-12 bg-primary rounded-full"></div>
                    </div>
                    <p class="mt-6 leading-relaxed text-muted-content font-medium">
                        We do not use tracking pixels, marketing cookies, or third-party analytics. 
                        Our cookies are strictly <strong>functional</strong>, used solely to maintain your 
                        authenticated session so you don't have to log in every time you refresh the page.
                    </p>
                </section>

                <section>
                    <div class="space-y-1">
                        <h2 class="text-xl font-black uppercase tracking-tight">3. Data Sovereignty & Security</h2>
                        <div class="h-1 w-12 bg-primary rounded-full"></div>
                    </div>
                    <p class="mt-6 leading-relaxed text-muted-content font-medium">
                        Your data is stored on secure servers located within the European Economic Area (EEA). 
                        We never sell or share your data with third parties.
                    </p>
                </section>

                <section class="rounded-3xl bg-warning/10 p-8 border border-warning/20 relative overflow-hidden text-warning shadow-sm">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-warning/20 rounded-full -mr-32 -mt-32 blur-3xl pointer-events-none"></div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-black uppercase tracking-tight">4. Your GDPR Rights</h2>
                        <ul class="mt-4 space-y-2 list-disc list-inside font-bold opacity-90">
                            <li>Right to access your stored data.</li>
                            <li>Right to be forgotten (complete account deletion).</li>
                            <li>Right to data portability.</li>
                        </ul>
                        <p class="mt-6 text-sm font-medium opacity-80">
                            To exercise these rights, simply delete your account in settings or contact us via our support channel.
                        </p>
                    </div>
                </section>
            </div>
        </main>

        <footer class="mt-20 py-12 text-center border-t border-border-color">
            <p class="text-xs text-muted-content font-bold uppercase tracking-widest opacity-50">Clipper-MS operates under the jurisdiction of the Netherlands.</p>
        </footer>
    </div>
</template>
