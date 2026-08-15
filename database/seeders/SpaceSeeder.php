<?php

namespace Pilot\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Pilot\Core\Models\Asset;
use Pilot\Core\Models\AssetFolder;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\DatasourceEntry;
use Pilot\Core\Models\Locale;
use Pilot\Core\Models\Space;

class SpaceSeeder extends Seeder
{
    public function run(?User $admin = null): void
    {
        $admin ??= User::role('Admin')->oldest()->first();

        if ($admin === null) {
            throw new \RuntimeException('Create an administrator with php artisan pilot:install before seeding a space.');
        }

        $space = Space::firstOrCreate(
            ['slug' => 'main'],
            ['name' => 'Main Space']
        );

        // Create locales
        $en = Locale::firstOrCreate(
            ['space_id' => $space->id, 'code' => 'en'],
            ['name' => 'English', 'is_default' => true]
        );

        Locale::firstOrCreate(
            ['space_id' => $space->id, 'code' => 'es'],
            ['name' => 'Spanish', 'is_default' => false]
        );

        // Create folders
        $homeFolder = Content::firstOrCreate(
            [
                'space_id' => $space->id,
                'slug' => 'home',
            ],
            [
                'type' => 'folder',
                'name' => 'Home',
                'parent_id' => null,
                'status' => 'published',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $productsFolder = Content::firstOrCreate(
            [
                'space_id' => $space->id,
                'slug' => 'products',
            ],
            [
                'type' => 'folder',
                'name' => 'Products',
                'parent_id' => null,
                'status' => 'published',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $aboutFolder = Content::firstOrCreate(
            [
                'space_id' => $space->id,
                'slug' => 'about',
            ],
            [
                'type' => 'folder',
                'name' => 'About',
                'parent_id' => null,
                'status' => 'published',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $blogFolder = Content::firstOrCreate(
            [
                'space_id' => $space->id,
                'slug' => 'blog',
            ],
            [
                'type' => 'folder',
                'name' => 'Blog',
                'parent_id' => null,
                'status' => 'published',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        // Create pages
        $pages = [
            [
                'parent' => $homeFolder,
                'slug' => 'homepage',
                'name' => 'Homepage',
                'status' => 'published',
            ],
            [
                'parent' => $homeFolder,
                'slug' => 'contact',
                'name' => 'Contact',
                'status' => 'draft',
            ],
            [
                'parent' => $productsFolder,
                'slug' => 'product-1',
                'name' => 'Product 1',
                'status' => 'published',
            ],
            [
                'parent' => $productsFolder,
                'slug' => 'product-2',
                'name' => 'Product 2',
                'status' => 'published',
            ],
            [
                'parent' => $aboutFolder,
                'slug' => 'about-us',
                'name' => 'About Us',
                'status' => 'published',
            ],
            [
                'parent' => $aboutFolder,
                'slug' => 'team',
                'name' => 'Team',
                'status' => 'draft',
            ],
            [
                'parent' => $blogFolder,
                'slug' => 'post-1',
                'name' => 'Blog Post 1',
                'status' => 'published',
            ],
            [
                'parent' => $blogFolder,
                'slug' => 'post-2',
                'name' => 'Blog Post 2',
                'status' => 'published',
            ],
            [
                'parent' => $blogFolder,
                'slug' => 'post-3',
                'name' => 'Blog Post 3',
                'status' => 'draft',
            ],
            [
                'parent' => null,
                'slug' => 'standalone',
                'name' => 'Standalone Page',
                'status' => 'draft',
            ],
        ];

        foreach ($pages as $pageData) {
            $page = Content::firstOrCreate(
                [
                    'space_id' => $space->id,
                    'slug' => $pageData['slug'],
                ],
                [
                    'type' => 'page',
                    'name' => $pageData['name'],
                    'parent_id' => $pageData['parent']?->id,
                    'status' => $pageData['status'],
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                    'published_at' => $pageData['status'] === 'published' ? now() : null,
                ]
            );

            // Add some blocks to homepage
            if ($pageData['slug'] === 'homepage' && $page->blocks()->count() === 0) {
                $hero = Block::create([
                    'content_id' => $page->id,
                    'type' => 'hero',
                    'position' => 0,
                    'data' => [
                        'title' => ['en' => 'Welcome to Pilot'],
                        'subtitle' => ['en' => 'Build amazing content with drag-and-drop'],
                    ],
                ]);

                Block::create([
                    'content_id' => $page->id,
                    'type' => 'richtext',
                    'position' => 1,
                    'data' => [
                        'content' => ['en' => '<p>This is a sample rich text block to demonstrate the content editor.</p>'],
                    ],
                ]);
            }
        }

        // Create asset folder
        $imagesFolder = AssetFolder::firstOrCreate(
            [
                'space_id' => $space->id,
                'name' => 'Images',
            ],
            [
                'parent_id' => null,
            ]
        );

        // Create datasources
        $themesDatasource = Datasource::firstOrCreate(
            [
                'space_id' => $space->id,
                'slug' => 'themes',
            ],
            ['name' => 'Themes']
        );

        $themesEntries = [
            ['key' => 'light', 'value' => ['en' => 'Light'], 'order' => 0],
            ['key' => 'dark', 'value' => ['en' => 'Dark'], 'order' => 1],
            ['key' => 'auto', 'value' => ['en' => 'Auto'], 'order' => 2],
        ];

        foreach ($themesEntries as $entry) {
            DatasourceEntry::firstOrCreate(
                [
                    'datasource_id' => $themesDatasource->id,
                    'key' => $entry['key'],
                ],
                $entry
            );
        }

        $ctaStylesDatasource = Datasource::firstOrCreate(
            [
                'space_id' => $space->id,
                'slug' => 'cta-styles',
            ],
            ['name' => 'CTA Styles']
        );

        $ctaEntries = [
            ['key' => 'primary', 'value' => ['en' => 'Primary'], 'order' => 0],
            ['key' => 'secondary', 'value' => ['en' => 'Secondary'], 'order' => 1],
            ['key' => 'outline', 'value' => ['en' => 'Outline'], 'order' => 2],
        ];

        foreach ($ctaEntries as $entry) {
            DatasourceEntry::firstOrCreate(
                [
                    'datasource_id' => $ctaStylesDatasource->id,
                    'key' => $entry['key'],
                ],
                $entry
            );
        }
    }
}
