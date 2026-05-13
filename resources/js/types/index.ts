export * from './auth';
export * from './navigation';
export * from './ui';
export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;

    // EV-Pengadaan fields
    nip?: string | null;
    jabatan?: string | null;
    role?: string | null;
    role_label?: string | null;
    unit_kerja?: string | null;
    is_admin?: boolean;
}