<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
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
            class="mb-6 rounded-xl bg-green-50 p-4 text-center text-sm font-semibold text-green-700 dark:bg-green-900/10 dark:text-green-400"
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
                class="w-full py-6 rounded-xl bg-[#f53003] text-white font-bold text-lg shadow-lg shadow-orange-500/20 hover:bg-[#ff4433] hover:scale-[1.02] transition-all active:scale-[0.98]"
            >
                <Spinner v-if="processing" class="mr-2" />
                Resend Verification Email
            </Button>

            <div class="text-center">
                <TextLink
                    :href="logout()"
                    as="button"
                    class="text-sm font-bold text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white transition-colors"
                >
                    Log out of this account
                </TextLink>
            </div>
        </Form>
    </AuthLayout>
</template>