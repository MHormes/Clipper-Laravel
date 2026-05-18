<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui';
import { Input } from '@/components/ui';
import { Label } from '@/components/ui';
import { Spinner } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Reset your password"
        description="No worries, it happens. Enter your email and we'll send you a reset link."
    >
        <Head title="Forgot password" />

        <div
            v-if="status"
            class="mb-6 rounded-xl bg-[var(--alert-success-bg)] p-4 text-center text-sm font-semibold text-success"
        >
            {{ status }}
        </div>

        <div class="space-y-6">
            <Form v-bind="email.form()" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-bold tracking-tight">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="name@example.com"
                        class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="mt-8 flex items-center justify-start">
                    <Button
                        class="w-full py-6 rounded-xl bg-[var(--primary)] text-secondary-content font-bold text-lg shadow-lg shadow-primary/20 hover:bg-[var(--primary)] hover:scale-[1.02] transition-all active:scale-[0.98]"
                        :disabled="processing"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                        Send Reset Link
                    </Button>
                </div>
            </Form>

            <div class="text-center text-sm font-medium text-muted-content">
                Remembered your password?
                <TextLink 
                    :href="login()" 
                    class="text-primary-content font-bold hover:text-[var(--primary)] transition-colors ml-1"
                >
                    Back to log in
                </TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
