<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Sampurasun" />

    <div
        class="relative flex min-h-screen flex-col overflow-hidden bg-slate-50 text-slate-800"
    >
        <!-- Background -->
        <div
            class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,#c7d2fe,transparent_30%),radial-gradient(circle_at_bottom_left,#dbeafe,transparent_30%)]"
        />

        <!-- Navbar -->
        <header
            class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-5"
        >
            <div>
                <h1 class="text-xl font-bold tracking-tight text-indigo-600">
                    SAMPURASUN
                </h1>

                <p class="text-xs text-slate-500">
                    Sistem Monitoring Pengadaan
                </p>
            </div>

            <nav class="flex items-center gap-3">
                <template v-if="$page.props.auth.user">
                    <Link
                        :href="dashboard()"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow transition hover:bg-indigo-700"
                    >
                        Dashboard
                    </Link>
                </template>

                <template v-else>
                    <!-- Login & Register di Nonaktifkan 
                        <Link
                            :href="login()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium transition hover:bg-slate-100"
                        >
                            Login
                        </Link>
                    
                    
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow transition hover:bg-indigo-700"
                        >
                            Register
                        </Link>
                    -->
                </template>
            </nav>
        </header>

        <!-- Main -->
        <main
            class="mx-auto flex w-full max-w-7xl flex-1 flex-col justify-center px-6"
        >
            <!-- Hero -->
            <section class="py-8 text-center">
                <div
                    class="mb-4 inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1 text-xs font-semibold text-indigo-700"
                >
                    Digital Procurement Platform
                </div>

                <h2
                    class="mx-auto max-w-4xl text-3xl font-black leading-tight tracking-tight text-slate-900 md:text-5xl"
                >
                    Pengadaan Lebih
                    <span class="text-indigo-600">
                        Transparan
                    </span>
                    dan Efisien
                </h2>

                <p
                    class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-slate-600 md:text-base"
                >
                    Platform monitoring pengadaan, dokumen,
                    pembayaran, dan evaluasi secara
                    terintegrasi dalam satu sistem.
                </p>

                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <Link
                        :href="$page.props.auth.user ? dashboard() : login()"
                        class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-indigo-700"
                    >
                        {{
                            $page.props.auth.user
                                ? 'Buka Dashboard'
                                : 'Masuk Sistem'
                        }}
                    </Link>
                    
                    <!-- Lihat Features dinonaktifkan
                    <a
                        href="#fitur"
                        class="rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold transition hover:bg-slate-100"
                    >
                        Lihat Fitur
                    </a>
                    -->
                </div>
            </section>

            <!-- Features -->
            <section
                id="fitur"
                class="grid gap-4 pb-8 md:grid-cols-2 xl:grid-cols-4"
            >
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-100 text-lg"
                    >
                        📄
                    </div>

                    <h3 class="mb-2 text-base font-bold">
                        Manajemen Dokumen
                    </h3>

                    <p class="text-sm leading-relaxed text-slate-600">
                        Pengelolaan dokumen pengadaan lebih cepat
                        dan terdokumentasi.
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-lg"
                    >
                        📊
                    </div>

                    <h3 class="mb-2 text-base font-bold">
                        Monitoring Real-time
                    </h3>

                    <p class="text-sm leading-relaxed text-slate-600">
                        Pantau seluruh proses pengadaan secara
                        transparan.
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-lg"
                    >
                        💰
                    </div>

                    <h3 class="mb-2 text-base font-bold">
                        Anggaran & Pembayaran
                    </h3>

                    <p class="text-sm leading-relaxed text-slate-600">
                        Integrasi realisasi anggaran dan pembayaran.
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-100 text-lg"
                    >
                        🏢
                    </div>

                    <h3 class="mb-2 text-base font-bold">
                        Evaluasi
                    </h3>

                    <p class="text-sm leading-relaxed text-slate-600">
                        Penilaian Pengadaan terhadap Penyedia lebih objektif dan terukur.
                    </p>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-500"
        >
            © 2026 sampurasun, SijaluCom
        </footer>
    </div>
</template>