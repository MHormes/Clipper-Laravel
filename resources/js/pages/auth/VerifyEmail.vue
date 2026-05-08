<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui';
import { Spinner } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head, usePage } from '@inertiajs/vue3';
import type { AppPageProps } from '@/types';

const props = defineProps<{
    status?: string;
    cooldownSeconds: number;
}>();

const page = usePage<AppPageProps>();
const remainingSeconds = ref(props.cooldownSeconds);

let countdown: ReturnType<typeof setInterval> | null = null;

const countdownLabel = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60);
    const seconds = remainingSeconds.value % 60;

    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

const startCountdown = () => {
    if (countdown) {
        clearInterval(countdown);
    }

    if (remainingSeconds.value <= 0) {
        countdown = null;
        return;
    }

    countdown = setInterval(() => {
        if (remainingSeconds.value <= 0) {
            if (countdown) {
                clearInterval(countdown);
                countdown = null;
            }

            return;
        }

        remainingSeconds.value -= 1;
    }, 1000);
};

watch(
    () => props.cooldownSeconds,
    (seconds) => {
        remainingSeconds.value = seconds;
        startCountdown();
    },
    { immediate: true },
);

onMounted(startCountdown);
onBeforeUnmount(() => {
    if (countdown) {
        clearInterval(countdown);
    }
});
</script>

<template>
    <AuthLayout
        title="Check your inbox"
        description="Your account is locked until you verify your email address."
    >
        <Head title="Email verification" />

        <div class="mb-6 rounded-xl border border-border-color bg-component-background p-4 text-center">
            <p class="text-sm text-primary-content">
                Verification email for
                <span class="font-bold">{{ page.props.auth.user.email }}</span>
            </p>
        </div>

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-6 rounded-xl bg-success p-4 text-center text-sm font-semibold text-success dark:bg-success/10 dark:text-success"
        >
            A fresh verification link has been sent to your email address.
        </div>

        <div
            v-else-if="remainingSeconds > 0"
            class="mb-6 rounded-xl bg-warning/15 p-4 text-center text-sm font-semibold text-warning"
        >
            You can request another email in {{ countdownLabel }}.
        </div>

        <Form
            v-bind="send.form()"
            class="flex flex-col gap-6"
            v-slot="{ processing }"
        >
            <Button 
                type="submit"
                :disabled="processing || remainingSeconds > 0"
                class="w-full py-6 rounded-xl bg-[var(--primary)] text-secondary-content font-bold text-lg shadow-lg shadow-primary/20 hover:bg-[var(--primary)] hover:scale-[1.02] transition-all active:scale-[0.98]"
            >
                <Spinner v-if="processing" class="mr-2" />
                {{ remainingSeconds > 0 ? `Resend available in ${countdownLabel}` : 'Resend Verification Email' }}
            </Button>

            <div class="text-center">
                <TextLink
                    :href="logout()"
                    as="button"
                    class="text-sm font-bold text-[var(--muted-content)] hover:text-[var(--primary-content)] dark:text-[var(--muted-content)] dark:hover:text-secondary-content transition-colors"
                >
                    Log out of this account
                </TextLink>
            </div>
        </Form>
    </AuthLayout>
</template>
