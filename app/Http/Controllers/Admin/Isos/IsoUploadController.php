<?php

namespace App\Http\Controllers\Admin\Isos;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Services\Images\ImageInspector;
use App\Services\Images\ImageSourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage as Filesystem;
use Illuminate\Support\Str;

/**
 * Takes an ISO the operator has nowhere else to host.
 *
 * Stored under its own hash so uploading the same disc twice costs one file,
 * and comes back described -- the hash and the size are properties of the file,
 * and typing either by hand only invites them to be wrong.
 */
class IsoUploadController
{
    public function __construct(
        private ImageInspector $inspector,
        private ImageSourceResolver $resolver,
    ) {}

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:iso'],
        ]);

        $upload = $request->file('file');
        $sha256 = $this->inspector->sha256OfFile($upload->getRealPath());
        $path = "iso-{$sha256}.iso";

        $disk = Filesystem::disk($this->resolver->diskName());

        if (! $disk->exists($path)) {
            $disk->putFileAs('', $upload, $path);
        }

        Audit::record(
            AuditEvent::ADMIN_ISO_UPLOADED,
            properties: ['sha256' => $sha256, 'size' => $disk->size($path)],
        );

        return [
            'path' => $path,
            'sha256' => $sha256,
            'size' => $disk->size($path),
            // What it will be called on a node, defaulted from what the
            // operator uploaded so the name in Proxmox stays recognisable.
            'file_name' => Str::of($upload->getClientOriginalName())->basename()->toString(),
        ];
    }
}
