<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui';
import { Input } from '@/components/ui';
import { Label } from '@/components/ui';
import { Spinner } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthLayout
        title="Security Check"
        description="For your security, please confirm your password to continue."
    >
        <Head title="Confirm password" />

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label for="password" class="text-sm font-bold tracking-tight">Your Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        autofocus
                        placeholder="••••••••"
                        class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background"
                    />

                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="w-full py-6 rounded-xl bg-[var(--primary)] text-secondary-content font-bold text-lg shadow-lg shadow-primary/20 hover:bg-[var(--primary)] hover:scale-[1.02] transition-all active:scale-[0.98]"
                        :disabled="processing"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                        Confirm Password
                    </Button>
                </div>
            </div>
        </Form>
    </AuthLayout>
</template>
