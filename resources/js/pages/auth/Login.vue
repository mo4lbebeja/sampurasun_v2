<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Selamat datang kembali',
        description: 'Masukkan kredensial yang diberikan oleh administrator unit kerja Anda.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    tahunOptions?: number[];
}>();
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="mb-4 rounded-md border border-[var(--color-brand-success-bg)] bg-[var(--color-brand-success-bg)] p-3 text-center text-sm font-medium text-[var(--color-brand-success)]"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-2">
            <Label for="email" class="text-sm font-semibold">Email Dinas</Label>
            <Input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                :tabindex="1"
                autocomplete="email"
                placeholder="nama@instansi.go.id"
                class="h-11"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label for="password" class="text-sm font-semibold">Password</Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-xs"
                    :tabindex="6"
                >
                    Lupa password?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="••••••••"
                class="h-11"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="tahun_anggaran" class="text-sm font-semibold">
                Tahun Anggaran
            </Label>

            <select
                id="tahun_anggaran"
                name="tahun_anggaran"
                required
                :tabindex="3"
                class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                :class="{ 'border-destructive': errors.tahun_anggaran }"
            >
                <option value="">Pilih Tahun Anggaran</option>
                <option
                    v-for="tahun in tahunOptions ?? []"
                    :key="tahun"
                    :value="tahun"
                >
                    {{ tahun }}
                </option>
            </select>

            <InputError :message="errors.tahun_anggaran" />
        </div>

        <Label for="remember" class="flex items-center gap-2.5 text-sm">
            <Checkbox id="remember" name="remember" :tabindex="4" />
            <span>Ingat saya</span>
        </Label>

        <Button
            type="submit"
            class="mt-2 h-11 w-full text-sm font-semibold"
            :tabindex="5"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            {{ processing ? 'Memproses...' : 'Masuk ke Sistem' }}
        </Button>

        <!-- Register di Nonaktifkan -->
        <!-- 
        <div
            v-if="canRegister"
            class="text-center text-sm text-muted-foreground"
        >
            Belum punya akun?
            <TextLink :href="register()" :tabindex="5">Daftar di sini</TextLink>
        </div> -->
    </Form>
</template>