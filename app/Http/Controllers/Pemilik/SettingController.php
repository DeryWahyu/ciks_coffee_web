<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Display QRIS settings page.
     */
    public function qris()
    {
        $qrisImage = ShopSetting::getValue('qris_image');
        return view('pemilik.settings.qris', compact('qrisImage'));
    }

    /**
     * Upload/update QRIS image.
     */
    public function updateQris(Request $request)
    {
        $request->validate([
            'qris_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'qris_image.required' => 'Gambar QRIS wajib dipilih.',
            'qris_image.image' => 'File harus berupa gambar.',
            'qris_image.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'qris_image.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        // Delete old image if exists
        $oldImage = ShopSetting::getValue('qris_image');
        if ($oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        // Store new image
        $path = $request->file('qris_image')->store('qris', 'public');
        ShopSetting::setValue('qris_image', $path);

        return redirect()->route('pemilik.settings.qris')
            ->with('success', 'Gambar QRIS berhasil diperbarui.');
    }

    /**
     * Delete QRIS image.
     */
    public function deleteQris()
    {
        $oldImage = ShopSetting::getValue('qris_image');
        if ($oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }
        ShopSetting::setValue('qris_image', null);

        return redirect()->route('pemilik.settings.qris')
            ->with('success', 'Gambar QRIS berhasil dihapus.');
    }
}
