<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Catat satu entri activity log.
     *
     * Dipanggil di setiap titik transisi status workflow:
     * usulan.submit, approval.approve, pengadaan.start, pengadaan.kontrak,
     * pengadaan.cancel, dokumen.complete, pembayaran.lunas, evaluasi.submit
     *
     * Dibungkus try-catch agar logging tidak pernah membreak main flow.
     */
    public static function log(
        int    $userId,
        string $action,
        string $description,
        ?int   $usulanId    = null,
        ?string $subjectType = null,
        ?int   $subjectId   = null,
        array  $properties  = [],
        ?Request $request   = null,
    ): void {
        try {
            ActivityLog::create([
                'user_id'      => $userId,
                'action'       => $action,
                'description'  => $description,
                'usulan_id'    => $usulanId,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'properties'   => empty($properties) ? null : $properties,
                'ip_address'   => $request?->ip(),
                'user_agent'   => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Jangan sampai error logging membreak request utama.
            // Log ke Laravel log channel saja.
            logger()->error('ActivityLogger gagal menulis: ' . $e->getMessage(), [
                'action'     => $action,
                'user_id'    => $userId,
                'usulan_id'  => $usulanId,
            ]);
        }
    }

    /**
     * Shortcut: log dari dalam request yang ada auth()->user().
     * Dipakai di controller agar tidak perlu pass userId manual.
     */
    public static function fromRequest(
        Request $request,
        string  $action,
        string  $description,
        ?int    $usulanId    = null,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        array   $properties  = [],
    ): void {
        static::log(
            userId:      $request->user()->id,
            action:      $action,
            description: $description,
            usulanId:    $usulanId,
            subjectType: $subjectType,
            subjectId:   $subjectId,
            properties:  $properties,
            request:     $request,
        );
    }
}