<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Website\Models\WebsiteSetting;
use Modules\Website\Services\MediaService;

class WebsiteSettingController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(): Response
    {
        $settings = WebsiteSetting::first() ?? new WebsiteSetting;

        return Inertia::render('Website::Admin/WebsiteSetting/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'website_name'           => 'required|string|max:255',
            'logo_path'              => 'nullable',
            'remove_logo_path'       => 'nullable|boolean',
            'favicon_path'           => 'nullable',
            'remove_favicon_path'    => 'nullable|boolean',
            'sliders'                => 'nullable|array',
            'email'                  => 'nullable|email|max:255',
            'whatsapp_number'        => 'nullable|string|max:20',
            'phone_number'           => 'nullable|string|max:20',
            'address'                => 'nullable|string',
            'google_maps_embed'      => 'nullable|string',
            'facebook_url'           => 'nullable|url|max:255',
            'instagram_url'          => 'nullable|url|max:255',
            'youtube_url'            => 'nullable|url|max:255',
            'tiktok_url'             => 'nullable|url|max:255',
            'is_popup_active'        => 'boolean',
            'popup_image_path'       => 'nullable',
            'remove_popup_image_path'=> 'nullable|boolean',
            'popup_redirect_url'     => 'nullable|url|max:255',
            'is_running_text_active' => 'boolean',
            'running_text_content'   => 'nullable|string',
        ], [
            'logo_path.file'         => 'Logo harus berupa file gambar yang valid.',
            'logo_path.mimes'        => 'Format logo harus PNG, JPG, JPEG, WEBP, atau SVG.',
            'logo_path.max'          => 'Ukuran file logo tidak boleh melebihi 10MB.',
            'favicon_path.file'      => 'Favicon harus berupa file gambar/ikon yang valid.',
            'favicon_path.mimes'     => 'Format favicon harus ICO, PNG, JPG, JPEG, atau WEBP.',
            'favicon_path.max'       => 'Ukuran favicon tidak boleh melebihi 5MB.',
            'popup_image_path.file'  => 'Banner pop-up harus berupa file gambar yang valid.',
            'popup_image_path.mimes' => 'Format banner pop-up harus PNG, JPG, JPEG, atau WEBP.',
            'popup_image_path.max'   => 'Ukuran file pop-up tidak boleh melebihi 10MB.',
        ]);

        $settings = WebsiteSetting::first();

        // 1. Logo Website (AVIF + simpan asli di website/settings/branding)
        if ($request->boolean('remove_logo_path')) {
            if ($settings?->logo_path) {
                $this->mediaService->deleteMedia($settings->logo_path);
            }
            $validated['logo_path'] = null;
        } elseif ($request->hasFile('logo_path')) {
            $request->validate([
                'logo_path' => 'file|mimes:jpeg,png,jpg,webp,svg|max:10240',
            ], [
                'logo_path.mimes' => 'Format logo harus PNG, JPG, JPEG, WEBP, atau SVG.',
                'logo_path.max'   => 'Ukuran file logo tidak boleh melebihi 10MB.',
            ]);

            if ($settings?->logo_path) {
                $this->mediaService->deleteMedia($settings->logo_path);
            }
            $validated['logo_path'] = $this->mediaService->storeImage(
                $request->file('logo_path'),
                'website/settings/branding',
                'logo'
            );
        } elseif ($request->filled('logo_path') && is_string($request->input('logo_path'))) {
            $validated['logo_path'] = $this->mediaService->cleanPath($request->input('logo_path'));
        } else {
            unset($validated['logo_path']);
        }

        // 2. Favicon Website
        if ($request->boolean('remove_favicon_path')) {
            if ($settings?->favicon_path) {
                $this->mediaService->deleteMedia($settings->favicon_path);
            }
            $validated['favicon_path'] = null;
        } elseif ($request->hasFile('favicon_path')) {
            $request->validate([
                'favicon_path' => 'file|mimes:ico,png,jpg,jpeg,webp|max:5120',
            ], [
                'favicon_path.mimes' => 'Format favicon harus ICO, PNG, JPG, JPEG, atau WEBP.',
                'favicon_path.max'   => 'Ukuran favicon tidak boleh melebihi 5MB.',
            ]);

            if ($settings?->favicon_path) {
                $this->mediaService->deleteMedia($settings->favicon_path);
            }
            $file = $request->file('favicon_path');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = 'favicon-' . time() . '-' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
            $savedPath = $file->storeAs('website/settings/branding', $filename, 'public');
            $validated['favicon_path'] = $savedPath;

            // Salin ke public/favicon.ico dan public/favicon.png untuk direct browser request fallback
            try {
                @copy($file->getRealPath(), public_path('favicon.ico'));
                @copy($file->getRealPath(), public_path('favicon.png'));
            } catch (\Throwable $e) {
                // Ignore fallback copy error
            }
        } elseif ($request->filled('favicon_path') && is_string($request->input('favicon_path'))) {
            $validated['favicon_path'] = $this->mediaService->cleanPath($request->input('favicon_path'));
        } else {
            unset($validated['favicon_path']);
        }

        // 3. Popup Image (AVIF + simpan asli di website/settings/popup)
        if ($request->boolean('remove_popup_image_path')) {
            if ($settings?->popup_image_path) {
                $this->mediaService->deleteMedia($settings->popup_image_path);
            }
            $validated['popup_image_path'] = null;
        } elseif ($request->hasFile('popup_image_path')) {
            $request->validate([
                'popup_image_path' => 'file|mimes:jpeg,png,jpg,webp|max:10240',
            ], [
                'popup_image_path.mimes' => 'Format banner pop-up harus PNG, JPG, JPEG, atau WEBP.',
                'popup_image_path.max'   => 'Ukuran file pop-up tidak boleh melebihi 10MB.',
            ]);

            if ($settings?->popup_image_path) {
                $this->mediaService->deleteMedia($settings->popup_image_path);
            }
            $validated['popup_image_path'] = $this->mediaService->storeImage(
                $request->file('popup_image_path'),
                'website/settings/popup',
                'popup'
            );
        } elseif ($request->filled('popup_image_path') && is_string($request->input('popup_image_path'))) {
            $validated['popup_image_path'] = $this->mediaService->cleanPath($request->input('popup_image_path'));
        } else {
            unset($validated['popup_image_path']);
        }

        // 4. Hero Sliders: simpan sebagai array of slider objects (Eloquent casts ke JSON otomatis)
        if (isset($validated['sliders']) && is_array($validated['sliders'])) {
            $cleanedSliders = [];
            $newSliderImages = [];
            foreach ($validated['sliders'] as $slide) {
                if (is_string($slide) && trim($slide) !== '') {
                    $cleaned = $this->mediaService->cleanPath($slide);
                    $newSliderImages[] = $cleaned;
                    $cleanedSliders[] = [
                        'image_url' => $cleaned,
                        'title'     => '',
                        'subtitle'  => '',
                        'cta_link'  => '/informasi/kejuruan',
                        'cta_text'  => 'Daftar Pelatihan',
                    ];
                } elseif (is_array($slide)) {
                    $imageUrl = $slide['image_url'] ?? $slide['image'] ?? '';
                    if (! empty($imageUrl) && is_string($imageUrl)) {
                        $cleaned = $this->mediaService->cleanPath($imageUrl);
                        $newSliderImages[] = $cleaned;
                        $cleanedSliders[] = [
                            'image_url' => $cleaned,
                            'title'     => (string) ($slide['title'] ?? ''),
                            'subtitle'  => (string) ($slide['subtitle'] ?? ''),
                            'cta_link'  => (string) ($slide['cta_link'] ?? ''),
                            'cta_text'  => (string) ($slide['cta_text'] ?? 'Daftar Pelatihan'),
                        ];
                    }
                }
            }
            $validated['sliders'] = $cleanedSliders;

            // Hapus file slider lama dari storage jika telah dihapus dari array sliders
            if ($settings) {
                $oldSliders = $settings->sliders;
                if (is_string($oldSliders)) {
                    $oldSliders = json_decode($oldSliders, true) ?? [];
                }
                $oldSliderImages = [];
                if (is_array($oldSliders)) {
                    foreach ($oldSliders as $oldSlide) {
                        $p = is_array($oldSlide) ? ($oldSlide['image_url'] ?? $oldSlide['image'] ?? null) : $oldSlide;
                        if (! empty($p) && is_string($p)) {
                            $oldSliderImages[] = $this->mediaService->cleanPath($p);
                        }
                    }
                }
                $removedSliders = array_diff(array_unique($oldSliderImages), array_unique($newSliderImages));
                foreach ($removedSliders as $removedImage) {
                    $this->mediaService->deleteMedia($removedImage);
                }
            }
        } elseif ($request->has('sliders') && empty($validated['sliders'])) {
            // Jika dikosongkan seluruhnya, hapus semua slider sebelumnya
            if ($settings && ! empty($settings->sliders)) {
                $oldSliders = is_string($settings->sliders) ? json_decode($settings->sliders, true) : $settings->sliders;
                if (is_array($oldSliders)) {
                    foreach ($oldSliders as $oldSlide) {
                        $p = is_array($oldSlide) ? ($oldSlide['image_url'] ?? $oldSlide['image'] ?? null) : $oldSlide;
                        if (! empty($p) && is_string($p)) {
                            $this->mediaService->deleteMedia($this->mediaService->cleanPath($p));
                        }
                    }
                }
            }
            $validated['sliders'] = [];
        }

        unset($validated['remove_logo_path'], $validated['remove_favicon_path'], $validated['remove_popup_image_path']);

        if ($settings) {
            $settings->update($validated);
        } else {
            WebsiteSetting::create($validated);
        }

        return back()->with('success', 'Pengaturan website berhasil disimpan.');
    }

    /**
     * Endpoint API upload media umum untuk foto fasilitas, alumni, kejuruan, slider, dll.
     * Mengonversi gambar ke AVIF (atau video ke AV1) dan menyimpan file asli.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file'   => 'required|file|max:20480', // 20MB
            'folder' => 'nullable|string|max:255',
            'name'   => 'nullable|string|max:255',
        ], [
            'file.required' => 'File tidak boleh kosong.',
            'file.file'     => 'Unggahan harus berupa file yang valid.',
            'file.max'      => 'Ukuran file tidak boleh melebihi 20MB.',
        ]);

        $folder = $request->input('folder', 'website/uploads');
        if (!str_starts_with($folder, 'website/')) {
            $folder = 'website/' . ltrim($folder, '/');
        }

        $path = $this->mediaService->storeMedia(
            $request->file('file'),
            $folder,
            $request->input('name')
        );

        return response()->json([
            'path' => $path,
            'url'  => $this->mediaService->getUrl($path),
        ]);
    }
}
