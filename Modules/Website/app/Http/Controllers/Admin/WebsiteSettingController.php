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
            'favicon_path'           => 'nullable',
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
            'popup_redirect_url'     => 'nullable|url|max:255',
            'is_running_text_active' => 'boolean',
            'running_text_content'   => 'nullable|string',
        ]);

        $settings = WebsiteSetting::first();

        // 1. Logo Website (AVIF + simpan asli di website/settings/branding)
        if ($request->hasFile('logo_path')) {
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
        } elseif (!$request->filled('logo_path') && !$request->hasFile('logo_path')) {
            unset($validated['logo_path']);
        }

        // 2. Favicon Website
        if ($request->hasFile('favicon_path')) {
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
        } elseif (!$request->filled('favicon_path') && !$request->hasFile('favicon_path')) {
            unset($validated['favicon_path']);
        }

        // 3. Popup Image (AVIF + simpan asli di website/settings/popup)
        if ($request->hasFile('popup_image_path')) {
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
        } elseif (!$request->filled('popup_image_path') && !$request->hasFile('popup_image_path')) {
            unset($validated['popup_image_path']);
        }

        // 4. Hero Sliders: simpan sebagai array of slider objects (Eloquent casts ke JSON otomatis)
        if (isset($validated['sliders']) && is_array($validated['sliders'])) {
            $cleanedSliders = [];
            foreach ($validated['sliders'] as $slide) {
                if (is_string($slide) && trim($slide) !== '') {
                    $cleanedSliders[] = [
                        'image_url' => $this->mediaService->cleanPath($slide),
                        'title'     => '',
                        'subtitle'  => '',
                        'cta_link'  => '/informasi/kejuruan',
                        'cta_text'  => 'Daftar Pelatihan',
                    ];
                } elseif (is_array($slide)) {
                    $imageUrl = $slide['image_url'] ?? $slide['image'] ?? '';
                    if (!empty($imageUrl) && is_string($imageUrl)) {
                        $cleanedSliders[] = [
                            'image_url' => $this->mediaService->cleanPath($imageUrl),
                            'title'     => (string) ($slide['title'] ?? ''),
                            'subtitle'  => (string) ($slide['subtitle'] ?? ''),
                            'cta_link'  => (string) ($slide['cta_link'] ?? ''),
                            'cta_text'  => (string) ($slide['cta_text'] ?? 'Daftar Pelatihan'),
                        ];
                    }
                }
            }
            $validated['sliders'] = $cleanedSliders;
        } elseif ($request->has('sliders') && empty($validated['sliders'])) {
            $validated['sliders'] = [];
        }

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
