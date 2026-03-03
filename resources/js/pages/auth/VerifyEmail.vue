<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui';
import { Spinner } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Check your inbox"
        description="We've sent a verification link to your email. Please click it to activate your collection."
    >
        <Head title="Email verification" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-6 rounded-xl bg-success p-4 text-center text-sm font-semibold text-success dark:bg-success/10 dark:text-success"
        >
            A fresh verification link has been sent to your email address.
        </div>

        <Form
            v-bind="send.form()"
            class="flex flex-col gap-6"
            v-slot="{ processing }"
        >
            <Button 
                type="submit"
                :disabled="processing" 
                class="w-full py-6 rounded-xl bg-[var(--primary)] text-secondary-content font-bold text-lg shadow-lg shadow-primary/20 hover:bg-[var(--primary)] hover:scale-[1.02] transition-all active:scale-[0.98]"
            >
                <Spinner v-if="processing" class="mr-2" />
                Resend Verification Email
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
