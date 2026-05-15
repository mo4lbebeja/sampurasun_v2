<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Ambil 15 notifikasi terbaru untuk user yang login.
     * Dipakai oleh NotificationBell.vue via fetch.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type']    ?? null,
                'message'    => $n->data['message'] ?? 'Notifikasi baru',
                'url'        => $n->data['url']     ?? null,
                'no_usulan'  => $n->data['no_usulan'] ?? null,
                'read'       => ! is_null($n->read_at),
                'created_at' => $n->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['unread_count' => 0]);
    }
}