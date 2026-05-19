<script setup lang="ts">
import { computed, ref } from 'vue';

// ── Types ──────────────────────────────────────────────────────────

type BulanData = {
    bulan:              number;
    label:              string;
    realisasi:          number;   // pembayaran formal lunas
    realisasiLangsung:  number;   // belanja langsung dibayar
    komitmen:           number;   // kontrak belum lunas
};

// ── Props ──────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        data:  BulanData[];
        tahun: number;
    }>(),
    {
        data:  () => [],
        tahun: new Date().getFullYear(),
    },
);

// ── Tooltip state ──────────────────────────────────────────────────

const tooltip = ref<{
    visible:           boolean;
    x:                 number;
    y:                 number;
    label:             string;
    realisasi:         number;
    realisasiLangsung: number;
    komitmen:          number;
}>({
    visible: false, x: 0, y: 0, label: '',
    realisasi: 0, realisasiLangsung: 0, komitmen: 0,
});

const showTooltip = (e: MouseEvent, item: BulanData) => {
    const svgEl  = (e.currentTarget as SVGElement).closest('svg')!;
    const svgRect = svgEl.getBoundingClientRect();
    const elRect  = (e.currentTarget as SVGElement).getBoundingClientRect();
    tooltip.value = {
        visible:           true,
        x:                 elRect.left - svgRect.left + elRect.width / 2,
        y:                 elRect.top  - svgRect.top,
        label:             item.label,
        realisasi:         item.realisasi,
        realisasiLangsung: item.realisasiLangsung ?? 0,
        komitmen:          item.komitmen,
    };
};

const hideTooltip = () => { tooltip.value.visible = false; };

// ── Format helpers ─────────────────────────────────────────────────

const fmt = (val: number): string => {
    if (val >= 1_000_000_000) return `${(val / 1_000_000_000).toFixed(1)} M`;
    if (val >= 1_000_000)     return `${(val / 1_000_000).toFixed(0)} Jt`;
    if (val >= 1_000)         return `${(val / 1_000).toFixed(0)} Rb`;
    return String(Math.round(val));
};

const fmtFull = (val: number): string =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
    }).format(val);

// ── SVG chart computations ─────────────────────────────────────────

const SVG_W   = 760;
const SVG_H   = 260;
const PAD_L   = 72;
const PAD_R   = 16;
const PAD_T   = 20;
const PAD_B   = 36;
const CHART_W = SVG_W - PAD_L - PAD_R;
const CHART_H = SVG_H - PAD_T - PAD_B;

const maxVal = computed(() => {
    const all = props.data.flatMap(d => [
        d.realisasi,
        d.realisasiLangsung ?? 0,
        d.komitmen,
    ]);
    return Math.max(...all, 1);
});

const yMax = computed(() => {
    const raw = maxVal.value;
    const mag = Math.pow(10, Math.floor(Math.log10(raw)));
    return Math.ceil(raw / mag) * mag;
});

const yTicks = computed(() => {
    const ticks = [];
    for (let i = 0; i <= 4; i++) ticks.push((yMax.value / 4) * i);
    return ticks;
});

const toY = (val: number): number =>
    PAD_T + CHART_H - (val / yMax.value) * CHART_H;

// 3 bar per bulan — lebih rapat
const MONTH_W = CHART_W / 12;
const BAR_W   = Math.floor(MONTH_W * 0.22);
const GAP     = 1.5;

const bars = computed(() =>
    props.data.map((d, i) => {
        const cx = PAD_L + i * MONTH_W + MONTH_W / 2;
        // 3 bar: realisasi (hijau) | belanja langsung (oranye) | komitmen (biru)
        const totalW = BAR_W * 3 + GAP * 2;
        const x1 = cx - totalW / 2;

        const hR  = Math.max((d.realisasi          / yMax.value) * CHART_H, 0);
        const hBL = Math.max(((d.realisasiLangsung ?? 0) / yMax.value) * CHART_H, 0);
        const hK  = Math.max((d.komitmen            / yMax.value) * CHART_H, 0);

        return {
            ...d,
            realisasiLangsung: d.realisasiLangsung ?? 0,
            // Bar realisasi formal (hijau)
            rx: x1,
            ry: PAD_T + CHART_H - hR,
            rh: hR,
            // Bar belanja langsung (oranye)
            bx: x1 + BAR_W + GAP,
            by: PAD_T + CHART_H - hBL,
            bh: hBL,
            // Bar komitmen (biru)
            kx: x1 + (BAR_W + GAP) * 2,
            ky: PAD_T + CHART_H - hK,
            kh: hK,
            // Label X
            lx: cx,
            ly: SVG_H - 8,
        };
    })
);

// Kumulatif realisasi total (formal + langsung)
const linePoints = computed(() => {
    let cum = 0;
    return props.data.map((d, i) => {
        cum += (d.realisasi ?? 0) + (d.realisasiLangsung ?? 0);
        const cx = PAD_L + i * MONTH_W + MONTH_W / 2;
        const cy = toY(Math.min(cum, yMax.value));
        return `${cx},${cy}`;
    }).join(' ');
});

const lineDots = computed(() => {
    let cum = 0;
    return props.data.map((d, i) => {
        cum += (d.realisasi ?? 0) + (d.realisasiLangsung ?? 0);
        return {
            cx: PAD_L + i * MONTH_W + MONTH_W / 2,
            cy: toY(Math.min(cum, yMax.value)),
            label: d.label,
        };
    });
});

// ── Summary stats ──────────────────────────────────────────────────

const totalRealisasi  = computed(() =>
    props.data.reduce((s, d) => s + (d.realisasi ?? 0) + (d.realisasiLangsung ?? 0), 0)
);
const totalKomitmen   = computed(() =>
    props.data.reduce((s, d) => s + (d.komitmen ?? 0), 0)
);
const bulanTertinggi  = computed(() => {
    const sorted = [...props.data].sort((a, b) =>
        (b.realisasi + (b.realisasiLangsung ?? 0)) -
        (a.realisasi + (a.realisasiLangsung ?? 0))
    );
    const top = sorted[0];
    return top && (top.realisasi + (top.realisasiLangsung ?? 0)) > 0 ? top : null;
});

const adaData = computed(() =>
    props.data.some(d => d.realisasi > 0 || (d.realisasiLangsung ?? 0) > 0 || d.komitmen > 0)
);

// Tooltip safe X agar tidak keluar batas
const tipX = computed(() => Math.min(Math.max(tooltip.value.x - 80, 4), SVG_W - 164));
const tipH  = 95;
</script>

<template>
    <div class="space-y-4">

        <!-- Summary cards -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-md bg-secondary/40 px-4 py-3">
                <div class="text-eyebrow">Total Realisasi {{ tahun }}</div>
                <div class="mt-1 font-mono text-sm font-semibold" style="color:var(--color-brand-success);">
                    {{ fmtFull(totalRealisasi) }}
                </div>
                <div class="mt-0.5 text-xs text-muted-foreground">formal + belanja langsung</div>
            </div>
            <div class="rounded-md bg-secondary/40 px-4 py-3">
                <div class="text-eyebrow">Total Komitmen {{ tahun }}</div>
                <div class="mt-1 font-mono text-sm font-semibold text-blue-700 dark:text-blue-400">
                    {{ fmtFull(totalKomitmen) }}
                </div>
            </div>
            <div class="rounded-md bg-secondary/40 px-4 py-3">
                <div class="text-eyebrow">Realisasi Tertinggi</div>
                <div class="mt-1 text-sm font-semibold">
                    {{ bulanTertinggi
                        ? `${bulanTertinggi.label} — ${fmtFull(bulanTertinggi.realisasi + (bulanTertinggi.realisasiLangsung ?? 0))}`
                        : '—' }}
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-5 text-xs text-muted-foreground">
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm" style="background:#1B4F2A;" />
                Realisasi Formal (Lunas)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm" style="background:rgba(249,115,22,0.8);" />
                Belanja Langsung
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm" style="background:rgba(21,101,192,0.6);" />
                Komitmen (Kontrak)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-0.5 w-6" style="background:#F9A825;" />
                Kumulatif Realisasi
            </span>
        </div>

        <!-- Empty state -->
        <div
            v-if="!adaData"
            class="flex h-48 items-center justify-center rounded-lg border border-dashed border-border text-sm text-muted-foreground"
        >
            Belum ada data untuk tahun {{ tahun }}
        </div>

        <!-- SVG Chart -->
        <div v-else class="relative w-full overflow-x-auto">
            <svg
                :viewBox="`0 0 ${SVG_W} ${SVG_H}`"
                class="w-full"
                style="min-width:480px;"
                @mouseleave="hideTooltip"
            >
                <!-- Grid lines Y -->
                <g>
                    <line v-for="tick in yTicks" :key="tick"
                        :x1="PAD_L" :y1="toY(tick)"
                        :x2="SVG_W - PAD_R" :y2="toY(tick)"
                        stroke="#E5E7EB" stroke-width="1" />
                    <text v-for="tick in yTicks" :key="'t' + tick"
                        :x="PAD_L - 6" :y="toY(tick) + 4"
                        text-anchor="end" font-size="10" fill="#9CA3AF" font-family="Arial,sans-serif">
                        {{ fmt(tick) }}
                    </text>
                </g>

                <!-- Baseline -->
                <line :x1="PAD_L" :y1="PAD_T + CHART_H"
                    :x2="SVG_W - PAD_R" :y2="PAD_T + CHART_H"
                    stroke="#D1D5DB" stroke-width="1.5" />

                <!-- Bars + label X -->
                <g v-for="b in bars" :key="b.bulan">

                    <!-- Bar realisasi formal (hijau) -->
                    <rect v-if="b.rh > 0"
                        :x="b.rx" :y="b.ry" :width="BAR_W" :height="b.rh"
                        fill="#1B4F2A" rx="2"
                        class="cursor-pointer transition-opacity hover:opacity-75"
                        @mouseenter="showTooltip($event, b)"
                        @mouseleave="hideTooltip" />
                    <rect v-else
                        :x="b.rx" :y="PAD_T + CHART_H - 2"
                        :width="BAR_W" height="2" fill="#E5E7EB" rx="1" />

                    <!-- Bar belanja langsung (oranye) -->
                    <rect v-if="b.bh > 0"
                        :x="b.bx" :y="b.by" :width="BAR_W" :height="b.bh"
                        fill="rgba(249,115,22,0.85)" stroke="#EA580C" stroke-width="0.8" rx="2"
                        class="cursor-pointer transition-opacity hover:opacity-75"
                        @mouseenter="showTooltip($event, b)"
                        @mouseleave="hideTooltip" />
                    <rect v-else
                        :x="b.bx" :y="PAD_T + CHART_H - 2"
                        :width="BAR_W" height="2" fill="#E5E7EB" rx="1" />

                    <!-- Bar komitmen (biru) -->
                    <rect v-if="b.kh > 0"
                        :x="b.kx" :y="b.ky" :width="BAR_W" :height="b.kh"
                        fill="rgba(21,101,192,0.55)" stroke="#1565C0" stroke-width="0.8" rx="2"
                        class="cursor-pointer transition-opacity hover:opacity-75"
                        @mouseenter="showTooltip($event, b)"
                        @mouseleave="hideTooltip" />

                    <!-- Label bulan -->
                    <text :x="b.lx" :y="b.ly"
                        text-anchor="middle" font-size="10.5" fill="#6B7280" font-family="Arial,sans-serif">
                        {{ b.label }}
                    </text>
                </g>

                <!-- Garis kumulatif -->
                <polyline :points="linePoints"
                    fill="none" stroke="#F9A825" stroke-width="2"
                    stroke-linejoin="round" stroke-linecap="round" />

                <!-- Dots kumulatif -->
                <g v-for="d in lineDots" :key="'dot' + d.label">
                    <circle :cx="d.cx" :cy="d.cy" r="3.5"
                        fill="#F9A825" stroke="white" stroke-width="1.5" />
                </g>

                <!-- Tooltip -->
                <g v-if="tooltip.visible">
                    <rect
                        :x="tipX" :y="tooltip.y - tipH - 8"
                        width="160" :height="tipH"
                        rx="6" fill="white"
                        stroke="#E5E7EB" stroke-width="1"
                        filter="drop-shadow(0 2px 4px rgba(0,0,0,0.12))"
                    />
                    <!-- Judul -->
                    <text :x="tipX + 80" :y="tooltip.y - tipH + 14"
                        text-anchor="middle" font-size="12" font-weight="bold"
                        fill="#111827" font-family="Arial,sans-serif">
                        {{ tooltip.label }}
                    </text>
                    <!-- Realisasi Formal -->
                    <text :x="tipX + 8" :y="tooltip.y - tipH + 30"
                        font-size="10" fill="#1B4F2A" font-family="Arial,sans-serif">
                        ● Formal
                    </text>
                    <text :x="tipX + 152" :y="tooltip.y - tipH + 30"
                        text-anchor="end" font-size="10" font-weight="bold"
                        fill="#1B4F2A" font-family="Arial,sans-serif">
                        {{ fmt(tooltip.realisasi) }}
                    </text>
                    <!-- Belanja Langsung -->
                    <text :x="tipX + 8" :y="tooltip.y - tipH + 46"
                        font-size="10" fill="#EA580C" font-family="Arial,sans-serif">
                        ● Langsung
                    </text>
                    <text :x="tipX + 152" :y="tooltip.y - tipH + 46"
                        text-anchor="end" font-size="10" font-weight="bold"
                        fill="#EA580C" font-family="Arial,sans-serif">
                        {{ fmt(tooltip.realisasiLangsung) }}
                    </text>
                    <!-- Komitmen -->
                    <text :x="tipX + 8" :y="tooltip.y - tipH + 62"
                        font-size="10" fill="#1565C0" font-family="Arial,sans-serif">
                        ● Komitmen
                    </text>
                    <text :x="tipX + 152" :y="tooltip.y - tipH + 62"
                        text-anchor="end" font-size="10" font-weight="bold"
                        fill="#1565C0" font-family="Arial,sans-serif">
                        {{ fmt(tooltip.komitmen) }}
                    </text>
                    <!-- Total realisasi -->
                    <line :x1="tipX + 8" :y1="tooltip.y - tipH + 70"
                        :x2="tipX + 152" :y2="tooltip.y - tipH + 70"
                        stroke="#E5E7EB" stroke-width="1" />
                    <text :x="tipX + 8" :y="tooltip.y - tipH + 84"
                        font-size="10" fill="#374151" font-family="Arial,sans-serif">
                        Total Realisasi
                    </text>
                    <text :x="tipX + 152" :y="tooltip.y - tipH + 84"
                        text-anchor="end" font-size="10" font-weight="bold"
                        fill="#374151" font-family="Arial,sans-serif">
                        {{ fmt(tooltip.realisasi + tooltip.realisasiLangsung) }}
                    </text>
                </g>

            </svg>
        </div>

    </div>
</template>