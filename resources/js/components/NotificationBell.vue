<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, ExternalLink } from 'lucide-vue-next';

// ── Types ──────────────────────────────────────────────────────────

type Notif = {
    id:         string;
    type:       string | null;
    message:    string;
    url:        string | null;
    no_usulan:  string | null;
    read:       boolean;
    created_at: string;
};

// ── State ──────────────────────────────────────────────────────────

const page         = usePage();
const open         = ref(false);
const loading      = ref(false);
const notifications = ref<Notif[]>([]);

// Baca unread count dari shared props (sudah diisi HandleInertiaRequests)
const unreadCount = computed(() =>
    (page.props as Record<string, unknown>).notifUnreadCount as number ?? 0
);

// ── Fetch notifikasi ───────────────────────────────────────────────

const fetchNotifications = async () => {
    if (loading.value) return;
    loading.value = true;
    try {
        const res  = await fetch('/notifikasi', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        notifications.value = data.notifications ?? [];
    } catch {
        notifications.value = [];
    } finally {
        loading.value = false;
    }
};

// ── Toggle dropdown ────────────────────────────────────────────────

const toggle = () => {
    open.value = !open.value;
    if (open.value) fetchNotifications();
};

const close = () => { open.value = false; };

// ── Mark satu notif sebagai dibaca + navigasi ─────────────────────

const handleClick = async (notif: Notif) => {
    close();
    if (!notif.read) {
        await fetch(`/notifikasi/${notif.id}/read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
                ),
            },
        });
        // Update unread count via Inertia reload shared props
        router.reload({ only: [] });
    }
    if (notif.url) router.visit(notif.url);
};

// ── Mark semua dibaca ──────────────────────────────────────────────

const markAllRead = async () => {
    await fetch('/notifikasi/read-all', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
            ),
        },
    });
    notifications.value = notifications.value.map(n => ({ ...n, read: true }));
    router.reload({ only: [] });
};

// ── Icon dan label per jenis notifikasi ───────────────────────────

const typeLabel = (type: string | null): string => {
    const map: Record<string, string> = {
        'usulan.submit'    : 'Usulan Baru',
        'approval.approve' : 'Usulan Disetujui',
        'approval.reject'  : 'Usulan Ditolak',
        'approval.revise'  : 'Usulan Perlu Revisi',
        'pengadaan.kontrak': 'Kontrak Baru',
        'dokumen.complete' : 'Dokumen Lengkap',
        'pembayaran.lunas' : 'Pembayaran Lunas',
    };
    return map[type ?? ''] ?? 'Notifikasi';
};

const typeColor = (type: string | null): string => {
    if (type?.includes('reject'))   return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
    if (type?.includes('approve'))  return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
    if (type?.includes('revise'))   return 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300';
    if (type?.includes('pembayaran')) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300';
    return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300';
};
</script>

<template>
    <!-- Wrapper posisi relative untuk dropdown -->
    <div class="relative">

        <!-- Bell button -->
        <button
            type="button"
            class="relative inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground focus:outline-none"
            :aria-label="`${unreadCount} notifikasi belum dibaca`"
            @click="toggle"
        >
            <Bell class="h-5 w-5" />

            <!-- Badge unread count -->
            <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Overlay untuk close saat klik luar -->
        <div
            v-if="open"
            class="fixed inset-0 z-40"
            @click="close"
        />

        <!-- Dropdown panel -->
        <div
            v-if="open"
            class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-xl border border-border bg-card shadow-xl"
        >
            <!-- Header dropdown -->
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <div>
                    <span class="text-sm font-semibold">Notifikasi</span>
                    <span
                        v-if="unreadCount > 0"
                        class="ml-2 rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300"
                    >
                        {{ unreadCount }} baru
                    </span>
                </div>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="flex items-center gap-1 text-xs text-primary hover:underline"
                    @click="markAllRead"
                >
                    <CheckCheck class="h-3.5 w-3.5" />
                    Tandai semua dibaca
                </button>
            </div>

            <!-- List notifikasi -->
            <div class="max-h-[420px] overflow-y-auto">

                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center py-10">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-border border-t-primary" />
                </div>

                <!-- Empty -->
                <div
                    v-else-if="notifications.length === 0"
                    class="flex flex-col items-center justify-center py-10 text-center"
                >
                    <Bell class="mb-2 h-8 w-8 text-muted-foreground/40" />
                    <p class="text-sm text-muted-foreground">Belum ada notifikasi</p>
                </div>

                <!-- Notif items -->
                <template v-else>
                    <button
                        v-for="notif in notifications"
                        :key="notif.id"
                        type="button"
                        class="flex w-full items-start gap-3 border-b border-border px-4 py-3 text-left transition last:border-b-0 hover:bg-muted/40"
                        :class="{ 'bg-blue-50/50 dark:bg-blue-950/20': !notif.read }"
                        @click="handleClick(notif)"
                    >
                        <!-- Dot unread -->
                        <div class="mt-1.5 flex-shrink-0">
                            <div
                                class="h-2 w-2 rounded-full"
                                :class="notif.read ? 'bg-transparent' : 'bg-blue-500'"
                            />
                        </div>

                        <!-- Konten -->
                        <div class="min-w-0 flex-1">
                            <!-- Badge jenis -->
                            <span
                                class="mb-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                :class="typeColor(notif.type)"
                            >
                                {{ typeLabel(notif.type) }}
                            </span>

                            <!-- Pesan -->
                            <p class="text-xs leading-snug text-foreground">
                                {{ notif.message }}
                            </p>

                            <!-- Waktu -->
                            <p class="mt-1 text-[11px] text-muted-foreground">
                                {{ notif.created_at }}
                            </p>
                        </div>

                        <!-- Link icon -->
                        <ExternalLink
                            v-if="notif.url"
                            class="mt-1 h-3.5 w-3.5 flex-shrink-0 text-muted-foreground"
                        />
                    </button>
                </template>
            </div>

            <!-- Footer -->
            <div class="border-t border-border bg-muted/20 px-4 py-2.5 text-center">
                <span class="text-xs text-muted-foreground">
                    Menampilkan 15 notifikasi terbaru
                </span>
            </div>
        </div>
    </div>
</template>