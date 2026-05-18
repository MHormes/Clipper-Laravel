<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui';
import { Checkbox } from '@/components/ui';
import { Input } from '@/components/ui';
import { Label } from '@/components/ui';
import { Spinner } from '@/components/ui';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Welcome back"
        description="Enter your credentials to access your collection"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-semibold text-success"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-bold tracking-tight">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="name@example.com"
                        class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-bold tracking-tight">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-xs font-medium text-[var(--primary)] hover:text-[var(--primary)] transition-colors"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Label for="remember" class="flex items-center space-x-3 cursor-pointer group">
                        <Checkbox 
                            id="remember" 
                            name="remember" 
                            :tabindex="3" 
                            class="border-border-color data-[state=checked]:bg-[var(--primary)] data-[state=checked]:border-[var(--primary)]"
                        />
                        <span class="text-sm text-muted-content group-hover:text-primary-content transition-colors">
                            Keep me logged in
                        </span>
                    </Label>
                </div>

                <div class="flex items-center">
                    <Label for="terms" class="flex items-center space-x-3 cursor-pointer group">
                        <Checkbox 
                            id="terms" 
                            name="terms" 
                            required
                            :tabindex="4" 
                            class="border-border-color data-[state=checked]:bg-[var(--primary)] data-[state=checked]:border-[var(--primary)]"
                        />
                        <span class="text-sm text-muted-content group-hover:text-primary-content transition-colors">
                            I agree to the <TextLink :href="route('terms')" class="text-[var(--primary)] hover:underline">Terms</TextLink> and <TextLink :href="route('privacy')" class="text-[var(--primary)] hover:underline">Privacy Policy</TextLink>
                        </span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full py-6 rounded-xl bg-[var(--primary)] text-secondary-content font-bold text-lg shadow-lg shadow-primary/20 hover:bg-[var(--primary)] hover:scale-[1.02] transition-all active:scale-[0.98]"
                    :tabindex="5"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                    Log in
                </Button>
            </div>

            <div
                class="text-center text-sm font-medium text-muted-content"
                v-if="canRegister"
            >
                Don't have an account?
                <TextLink 
                    :href="register()" 
                    :tabindex="5" 
                    class="text-primary-content font-bold hover:text-[var(--primary)] transition-colors"
                >
                    Sign up free
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
