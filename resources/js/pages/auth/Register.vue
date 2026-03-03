<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui';
import { Checkbox } from '@/components/ui';
import { Input } from '@/components/ui';
import { Label } from '@/components/ui';
import { Spinner } from '@/components/ui';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
</script>

<template>
    <AuthBase
        title="Start your collection"
        description="Join Clipper-MS to track and catalog your series"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="name" class="text-sm font-bold tracking-tight">Full Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="e.g. Jasper van den Berg"
                        class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background dark:border-secondary-content/10"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-bold tracking-tight">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="name@example.com"
                        class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background dark:border-secondary-content/10"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="password" class="text-sm font-bold tracking-tight">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="••••••••"
                            class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background dark:border-secondary-content/10"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="text-sm font-bold tracking-tight">Confirm</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="••••••••"
                            class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] bg-component-background dark:border-secondary-content/10"
                        />
                    </div>
                </div>
                <div class="col-span-full">
                    <InputError :message="errors.password" />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center">
                    <Label for="terms" class="flex items-center space-x-3 cursor-pointer group">
                        <Checkbox 
                            id="terms" 
                            name="terms" 
                            required
                            :tabindex="5" 
                            class="border-border-color data-[state=checked]:bg-[var(--primary)] data-[state=checked]:border-[var(--primary)]"
                        />
                        <span class="text-[11px] text-[var(--muted-content)] dark:text-[var(--muted-content)] leading-tight group-hover:text-primary-content dark:group-hover:text-secondary-content transition-colors">
                            By registering, you agree to our 
                            <TextLink :href="route('terms')" class="text-[var(--primary)] hover:underline">Terms</TextLink> and acknowledge our 
                            <TextLink :href="route('privacy')" class="text-[var(--primary)] hover:underline">Privacy Policy</TextLink>.
                        </span>
                    </Label>
                    <InputError :message="errors.terms" class="ml-2" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full py-6 rounded-xl bg-[var(--primary)] text-secondary-content font-bold text-lg shadow-lg shadow-primary/20 hover:bg-[var(--primary)] hover:scale-[1.02] transition-all active:scale-[0.98]"
                    tabindex="6"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm font-medium text-[var(--muted-content)] dark:text-[var(--muted-content)]">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="text-[var(--primary-content)] dark:text-[var(--primary-content)] font-bold hover:text-[var(--primary)] transition-colors"
                    :tabindex="6"
                >
                    Log in
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
