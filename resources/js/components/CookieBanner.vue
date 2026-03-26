<script setup lang="ts">
import { useConsent } from '@/composables/useConsent';

const { hasDecided, accept, decline } = useConsent();
</script>

<template>
    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-y-full"
        enter-to-class="translate-y-0"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-y-0"
        leave-to-class="translate-y-full"
    >
        <div
            v-if="!hasDecided"
            class="fixed bottom-0 left-0 right-0 z-50 border-t border-border-color bg-component-background shadow-lg"
            role="dialog"
            aria-live="polite"
            aria-label="Cookie consent"
        >
            <div class="mx-auto max-w-5xl px-6 py-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-primary-content">
                        We use cookies
                    </p>
                    <p class="mt-1 text-xs text-muted-content leading-relaxed">
                        In addition to functional cookies, we use Google Analytics to gain anonymous insights into
                        how Clipper-MS is used. Your consent is required under the AVG/GDPR.
                        <a href="/privacy" class="underline text-primary hover:opacity-80 transition ml-1">
                            Read our privacy policy
                        </a>
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <button
                        type="button"
                        class="px-4 py-2 text-xs font-semibold rounded-lg border border-border-color text-black bg-white hover:bg-gray-100 transition"
                        @click="decline"
                    >
                        Decline
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-primary text-button-content hover:opacity-90 transition"
                        @click="accept"
                    >
                        Accept
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
