<?php

namespace App\Http\Controllers\Pemilik;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        $path = $request->file('qris_image')->store('qris', 'public');
        if (! $path) {
            throw ValidationException::withMessages(['qris_image' => 'Gambar QRIS gagal disimpan.']);
        }
        try {
            $oldImage = $this->replaceQris($path);
        } catch (\Throwable $exception) {
            $this->deleteImage($path);
            throw $exception;
        }
        $this->deleteImage($oldImage);

        return redirect()->route('pemilik.settings.qris')
            ->with('success', 'Gambar QRIS berhasil diperbarui.');
    }

    /**
     * Delete QRIS image.
     */
    public function deleteQris()
    {
        $oldImage = $this->replaceQris(null);
        $this->deleteImage($oldImage);

        return redirect()->route('pemilik.settings.qris')
            ->with('success', 'Gambar QRIS berhasil dihapus.');
    }

    private function replaceQris(?string $path): ?string
    {
        return DB::transaction(function () use ($path) {
            $setting = ShopSetting::where('key', 'qris_image')->lockForUpdate()->first();
            $previous = $setting?->value;
            if ($setting) {
                $setting->update(['value' => $path]);
            } else {
                ShopSetting::setValue('qris_image', $path);
            }

            return $previous;
        }, 3);
    }

    private function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
