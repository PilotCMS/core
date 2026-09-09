# Image transformations

Pilot can resize and crop stored image assets on demand. The public endpoint uses the asset record so crops automatically respect the focal point selected in the asset library, falling back to the center of the image.

```text
/assets/42/hero.jpg?size=400x400
```

The first request without a `v` parameter redirects to a versioned URL that can be cached immutably. Applications should normally generate that URL from the asset model:

```php
$url = $asset->imageUrl(400, 400);
$url = $asset->imageUrl(800, 450, [
    'fit' => 'cover',
    'format' => 'auto',
    'quality' => 82,
]);
```

`$asset->deliveryUrl()` returns the transformable base URL for locally stored raster images and the ordinary asset URL for other files. Pilot's asset picker and MCP media tools use this delivery URL, so a stored image value can be requested as-is or have `?size=400x400` appended directly.

## Parameters

| Parameter | Values | Default |
| --- | --- | --- |
| `size` | `{width}x{height}` | Required |
| `fit` | `cover`, `contain` | `cover` |
| `format` | `auto`, `avif`, `webp`, `jpeg`, `png` | `auto` |
| `quality` | `40`–`95` | `80` |

`cover` fills the requested aspect ratio and crops around the asset focal point. `contain` preserves the complete image within the requested bounds. Pilot does not enlarge source images by default.

`auto` negotiates AVIF or WebP from the request's `Accept` header and otherwise preserves PNG or returns JPEG. Generated variants are cached on the asset's configured filesystem disk under `assets/transforms`.

## Configuration

The following environment variables control the public route and processing bounds:

```dotenv
PILOT_IMAGE_ROUTES=true
PILOT_IMAGE_DRIVER=local
PILOT_IMAGE_MAX_WIDTH=4096
PILOT_IMAGE_MAX_HEIGHT=4096
PILOT_IMAGE_MAX_PIXELS=16777216
PILOT_IMAGE_QUALITY=80
PILOT_IMAGE_ALLOW_UPSCALE=false
PILOT_IMAGE_CACHE_CONTROL="public, max-age=31536000, immutable"
```

The URL generator is resolved through `Pilot\Core\Contracts\AssetImageUrlGenerator`. A deployment can register another configured driver, such as an edge image service, without changing application templates.

## Laravel frontends

The `pilot/laravel` adapter exposes helpers for requesting a single variant or a responsive set while preserving legacy and third-party URLs:

```php
pilotImage($image, 800, 450);
pilotImageSrcset($image, 1280, 720, [480, 768, 1024, 1280]);
```

Set `PILOT_CMS_URL` or `PILOT_ASSET_URL` when the frontend runs on a different origin from Pilot. The adapter's packaged image and hero components request transformed variants automatically.
