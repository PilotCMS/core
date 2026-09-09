<?php

namespace Pilot\Core\Support\Cms;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pilot\Core\Models\Asset;
use RuntimeException;

class AssetUploader
{
    public function store(UploadedFile $file, int $spaceId, ?int $folderId = null): Asset
    {
        Storage::disk('public')->makeDirectory('assets');

        $path = $file->store('assets', 'public');

        if ($path === false) {
            throw new RuntimeException('Failed to store file: '.$file->getClientOriginalName());
        }

        [$width, $height] = $this->imageDimensions($file->getRealPath());

        $asset = Asset::create([
            'space_id' => $spaceId,
            'folder_id' => $folderId,
            'disk' => 'public',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'metadata' => [
                'client_original_name' => $file->getClientOriginalName(),
                'client_extension' => $file->getClientOriginalExtension(),
                'client_mime' => $file->getClientMimeType(),
            ],
        ]);

        app(AssetThumbnailer::class)->generate($asset);

        return $asset;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    protected function imageDimensions(string $path): array
    {
        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            return [null, null];
        }

        return [(int) $dimensions[0], (int) $dimensions[1]];
    }
}
