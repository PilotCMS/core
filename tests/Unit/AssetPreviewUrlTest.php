<?php

namespace Pilot\Core\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Pilot\Core\Contracts\AssetImageUrlGenerator;
use Pilot\Core\Models\Asset;
use Pilot\Core\Support\Cms\LocalAssetImageUrlGenerator;

require_once __DIR__.'/../../src/Models/Asset.php';

class AssetPreviewUrlTest extends TestCase
{
    private Container $previousContainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $app = new Container;
        Container::setInstance($app);
        $app->instance('config', new Repository([
            'app' => ['url' => 'https://pilot.test'],
            'filesystems' => ['disks' => [
                'public' => ['driver' => 'local', 'root' => '/tmp/pilot-preview-tests', 'url' => 'https://pilot.test/storage'],
                'spaces' => ['driver' => 'local', 'root' => '/tmp/pilot-preview-tests', 'url' => 'https://media.example.com'],
            ]],
        ]));
        $routes = new RouteCollection;
        $routes->add((new Route('GET', 'assets/{asset}/{filename}', fn () => ''))->name('pilot.assets.image'));
        $app->instance('url', new UrlGenerator($routes, Request::create('https://pilot.test')));
        $app->instance('filesystem', new FilesystemManager($app));
        $app->bind(AssetImageUrlGenerator::class, LocalAssetImageUrlGenerator::class);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousContainer);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    private function asset(array $attributes = []): Asset
    {
        $asset = new Asset([
            'disk' => 'spaces', 'path' => 'assets/photo.jpg', 'filename' => 'photo.jpg',
            'mime' => 'image/jpeg', 'checksum' => 'original-checksum',
            ...$attributes,
        ]);
        $asset->setAttribute('id', 42);

        return $asset;
    }

    public function test_owned_images_use_bounded_versioned_jit_previews(): void
    {
        foreach (['public', 'spaces'] as $disk) {
            $asset = $this->asset(['disk' => $disk]);
            $url = $asset->thumbnailUrl();
            $this->assertSame('/assets/42/photo.jpg', parse_url($url, PHP_URL_PATH));
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame(['size' => '640x480', 'v' => $asset->imageCacheVersion(), 'fit' => 'cover', 'format' => 'webp', 'quality' => '75'], $query);
            $this->assertSame($url, $asset->thumbnailRelativeUrl());
            $this->assertStringEndsWith('/assets/photo.jpg', $asset->url());
        }
    }

    public function test_raster_previews_do_not_depend_on_legacy_generated_thumbnails(): void
    {
        $asset = $this->asset(['thumbnail_path' => 'assets/thumbnails/42.webp']);
        $this->assertStringStartsWith('/assets/42/photo.jpg?', $asset->thumbnailUrl());
    }

    public function test_focal_and_source_changes_invalidate_preview_urls(): void
    {
        $asset = $this->asset();
        $original = $asset->thumbnailUrl();
        $asset->focal_x = 75;
        $this->assertNotSame($original, $asset->thumbnailUrl());
        $focused = $asset->thumbnailUrl();
        $asset->checksum = 'replacement-checksum';
        $this->assertNotSame($focused, $asset->thumbnailUrl());
    }

    public function test_svg_and_video_posters_keep_their_existing_delivery(): void
    {
        $svg = $this->asset(['mime' => 'image/svg+xml', 'path' => 'assets/logo.svg']);
        $this->assertSame('https://media.example.com/assets/logo.svg', $svg->thumbnailUrl());
        $video = $this->asset(['mime' => 'video/mp4', 'thumbnail_path' => 'assets/poster.jpg']);
        $this->assertSame('https://media.example.com/assets/poster.jpg', $video->thumbnailUrl());
    }

    public function test_external_images_keep_their_provider_specific_preview(): void
    {
        $asset = $this->asset(['disk' => 'external', 'path' => 'https://images.unsplash.com/photo-test?w=2200']);
        $this->assertStringContainsString('w=640', $asset->thumbnailUrl());
        $this->assertStringContainsString('h=480', $asset->thumbnailUrl());
        $this->assertSame('https://images.unsplash.com/photo-test?w=2200', $asset->url());
    }

    public function test_preview_generation_uses_the_configured_image_driver(): void
    {
        app()->instance(AssetImageUrlGenerator::class, new class implements AssetImageUrlGenerator
        {
            public function url(Asset $asset, array $options = []): string
            {
                return 'https://images.example.com/'.$asset->id.'?'.http_build_query($options);
            }
        });
        $this->assertSame('https://images.example.com/42?fit=cover&format=webp&quality=75&width=640&height=480', $this->asset()->thumbnailUrl());
    }
}
