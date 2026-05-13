<script setup lang="ts">
withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: '',
        description: '',
    },
);
</script>

<template>
    <div class="grid min-h-svh lg:grid-cols-2">
        <!-- ============================================== -->
        <!-- PANEL KIRI: Brand                              -->
        <!-- ============================================== -->
        <div class="relative hidden overflow-hidden bg-primary lg:block">
            <!-- Pattern grid -->
            <div class="absolute inset-0 opacity-10">
                <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern
                            id="grid-pattern"
                            width="40"
                            height="40"
                            patternUnits="userSpaceOnUse"
                        >
                            <path
                                d="M 40 0 L 0 0 0 40"
                                fill="none"
                                stroke="white"
                                stroke-width="0.5"
                            />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid-pattern)" />
                </svg>
            </div>

            <!-- Glow accents -->
            <div
                class="absolute -right-20 -top-20 h-72 w-72 rounded-full opacity-20 blur-3xl"
                style="background-color: var(--color-brand-copper);"
            />
            <div
                class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full opacity-20 blur-3xl"
                style="background-color: var(--color-brand-primary-light);"
            />

            <!-- Content -->
            <div class="relative flex h-full flex-col justify-between p-12 text-primary-foreground">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--color-bg-cream)] font-display text-2xl font-bold text-primary"
                    >
                        S
                    </div>
                    <div>
                        <div class="font-display text-xl font-semibold">Sampurasun</div>
                        <div class="text-xs opacity-75">Sistem Administrasi Manajemen Perencanaan tur Realisasi Anggaran nu Nyata</div>
                    </div>
                </div>

                <!-- Tagline -->
                <div class="space-y-6">
                    <h1 class="font-display text-4xl font-semibold leading-tight xl:text-5xl">
                        Pengadaan yang transparan, terdokumentasi, akuntabel.
                    </h1>
                    <p class="max-w-md text-base opacity-90">
                        Dari usulan hingga evaluasi — pantau setiap tahap pengadaan barang dan jasa
                         dalam satu sistem.
                    </p>

                    <!-- Stats -->
                    <div class="flex gap-8 pt-4">
                        <div>
                            <div
                                class="font-display text-3xl font-semibold"
                                style="color: var(--color-brand-copper-soft);"
                            >
                                6
                            </div>
                            <div class="text-xs uppercase tracking-wider opacity-75">
                                Tahap Workflow
                            </div>
                        </div>
                        <div>
                            <div
                                class="font-display text-3xl font-semibold"
                                style="color: var(--color-brand-copper-soft);"
                            >
                                7
                            </div>
                            <div class="text-xs uppercase tracking-wider opacity-75">
                                Role Pengguna
                            </div>
                        </div>
                        <div>
                            <div
                                class="font-display text-3xl font-semibold"
                                style="color: var(--color-brand-copper-soft);"
                            >
                                100%
                            </div>
                            <div class="text-xs uppercase tracking-wider opacity-75">
                                Audit Trail
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-xs opacity-60">
                    © {{ new Date().getFullYear() }} Sampurasun · SijaluCom
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- PANEL KANAN: Form                              -->
        <!-- ============================================== -->
        <div class="flex items-center justify-center bg-background p-6 sm:p-12">
            <div class="w-full max-w-md">
                <!-- Logo mobile (hanya tampil di lg ke bawah) -->
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary font-display text-lg font-bold text-primary-foreground"
                    >
                        EV
                    </div>
                    <div class="font-display text-lg font-semibold text-primary">
                        Sampurasun
                    </div>
                </div>

                <!-- Header -->
                <div class="mb-8">
                    <div class="text-eyebrow mb-2">Masuk Akun</div>
                    <h2 class="font-display text-3xl font-semibold tracking-tight">
                        {{ title || 'Selamat datang kembali' }}
                    </h2>
                    <p v-if="description" class="mt-2 text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <!-- Slot for form -->
                <slot />

                <!-- Help info -->
                <div class="mt-8 rounded-md border border-border bg-card p-4 text-xs text-muted-foreground">
                    <div class="mb-1 font-semibold text-foreground">Butuh bantuan akses?</div>
                    Hubungi Administrator
                </div>
            </div>
        </div>
    </div>
</template>