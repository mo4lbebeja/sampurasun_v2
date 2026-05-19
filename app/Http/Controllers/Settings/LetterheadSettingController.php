<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LetterheadSettingController extends Controller
{
    public function edit(): Response
    {
        $kopSurat = AppSetting::getValue('kop_surat_image');

        return Inertia::render('settings/KopSurat', [
            'kopSurat' => $kopSurat ? Storage::url($kopSurat) : null,
             'thresholdNominal' => (float) (\App\Models\AppSetting::getValue('threshold_reimburse') ?? 1000000),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'kop_surat' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldPath = AppSetting::getValue('kop_surat_image');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('kop_surat')->store('settings', 'public');

        AppSetting::setValue('kop_surat_image', $path);

        return back()->with('success', 'Kop surat berhasil diperbarui.');
    }

    public function updateThreshold(Request $request): RedirectResponse
    {
        $request->validate([
            'nominal' => ['required', 'numeric', 'min:0'],
        ]);

        \App\Models\AppSetting::setValue('threshold_reimburse', (string) $request->input('nominal'));

        return back()->with('success', 'Threshold berhasil diperbarui.');
    }
}