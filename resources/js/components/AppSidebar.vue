<script setup lang="ts">
import { index as usulanIndex, create as usulanCreate } from '@/routes/usulan';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    FileCheck,
    FilePlus,
    FileText,
    FolderGit2,
    LayoutGrid,
    ShoppingCart,
    Stamp,
    Star,
    Tag,
    TrendingUp,
    Wallet,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import { Users } from 'lucide-vue-next';

// Menu item dengan dukungan role filter
type RoleNavItem = NavItem & {
    roles?: string[];
};

const page = usePage();
const userRole = computed(() => page.props.auth.user?.role);
const isAdmin = computed(() => page.props.auth.user?.is_admin === true);

// Menu utama — Dashboard untuk semua role
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

// Menu workflow pengadaan — sebagian umum, sebagian khusus per role
const procurementNavItems: RoleNavItem[] = [
    {
        title: 'Daftar Usulan',
        href: usulanIndex(),
        icon: FileText,
        // tanpa roles → tampil untuk semua
    },
    {
        title: 'Buat Usulan',
        href: usulanCreate(),
        icon: FilePlus,
        roles: ['sarana_umum'],
    },
    {
        title: 'Approval',
        href: '/approval',
        icon: Stamp,
        roles: ['pptk'],
    },
    {
        title: 'Pengadaan',
        href: '/pengadaan',
        icon: ShoppingCart,
        roles: ['pejabat_pengadaan'],
    },
    {
        title: 'Dokumen UPBJ',
        href: '/dokumen',
        icon: FileCheck,
        roles: ['upbj'],
    },
    {
        title: 'Rekap Dokumen',
        href: '/dokumen/rekap',
        icon: FileText,
        roles: ['upbj', 'admin'],
    },
    {
        title: 'Pembayaran',
        href: '/pembayaran',
        icon: Wallet,
        roles: ['keuangan'],
    },
    {
        title: 'Evaluasi',
        href: '/evaluasi',
        icon: Star,
        roles: ['perencanaan'],
    },
    {
        title: 'Rekap Realisasi',
        href: '/realisasi',
        icon: TrendingUp, // atau LineChart, atau yang sesuai
        roles: ['admin', 'perencanaan', 'pptk', 'pejabat_pengadaan', 'keuangan'],
    },
];

// Master data — admin only
const masterDataNavItems: RoleNavItem[] = [
    {
        title: 'User',
        href: '/users',
        icon: Users,
        roles: ['admin'],
    },
    {
        title: 'Penyedia',
        href: '/penyedia',
        icon: Building2,
        roles: ['admin', 'pejabat_pengadaan'],
    },
    {
        title: 'Sub Kegiatan',
        href: '/sub-kegiatan',
        icon: Tag,
        roles: ['admin'],
    },
    {
        title: 'Anggaran',
        href: '/anggaran',
        icon: TrendingUp,
        roles: ['admin'],
    },
    {
        title: 'Kategori Barang',
        href: '/kategori',
        icon: Tag,
        roles: ['admin'],
    },
];

// Filter helper: tampilkan jika tanpa roles, atau user admin, atau role user match

const filterByRole = (items: RoleNavItem[]): NavItem[] =>
    items.filter(
        (item) =>
            !item.roles ||
            isAdmin.value ||
            item.roles.includes((userRole.value ?? '') as string),
    );

const visibleProcurementItems = computed(() => filterByRole(procurementNavItems));
const visibleMasterDataItems = computed(() => filterByRole(masterDataNavItems));

//const footerNavItems: NavItem[] = [
//    {
//        title: 'Repository',
//        href: 'https://github.com/laravel/vue-starter-kit',
//        icon: FolderGit2,
//    },
//    {
//        title: 'Documentation',
//        href: 'https://laravel.com/docs/starter-kits#vue',
//        icon: BookOpen,
//    },
//];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="px-3 py-2">
                <span
                    v-if="$page.props.tahunAnggaranAktif"
                    class="inline-flex rounded-md bg-primary/10 px-2 py-1 text-xs font-semibold text-primary"
                >
                    TAHUN ANGGARAN {{ $page.props.tahunAnggaranAktif }}
                </span>
            </div>
            <NavMain :items="mainNavItems" label="Utama" />

            <NavMain
                v-if="visibleProcurementItems.length"
                :items="visibleProcurementItems"
                label="Pengadaan"
            />

            <NavMain
                v-if="visibleMasterDataItems.length"
                :items="visibleMasterDataItems"
                label="Master Data"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>