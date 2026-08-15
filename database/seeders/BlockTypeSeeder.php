<?php

namespace Pilot\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Pilot\Core\Models\BlockType;

class BlockTypeSeeder extends Seeder
{
    public function run(): void
    {
        $blockTypes = [
            [
                'key' => 'section',
                'name' => 'Section',
                'icon' => 'rectangle-stack',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'text',
                            'key' => 'background_color',
                            'label' => 'Background Color',
                            'default' => '#ffffff',
                        ],
                        [
                            'type' => 'number',
                            'key' => 'padding',
                            'label' => 'Padding',
                            'default' => 20,
                        ],
                    ],
                    'can_contain_blocks' => true,
                ],
            ],
            [
                'key' => 'hero',
                'name' => 'Hero',
                'icon' => 'photo',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'text',
                            'key' => 'title',
                            'label' => 'Title',
                            'translatable' => true,
                        ],
                        [
                            'type' => 'textarea',
                            'key' => 'subtitle',
                            'label' => 'Subtitle',
                            'translatable' => true,
                        ],
                        [
                            'type' => 'image',
                            'key' => 'background_image',
                            'label' => 'Background Image',
                        ],
                    ],
                    'can_contain_blocks' => false,
                ],
            ],
            [
                'key' => 'richtext',
                'name' => 'Rich Text',
                'icon' => 'document-text',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'richtext',
                            'key' => 'content',
                            'label' => 'Content',
                            'translatable' => true,
                        ],
                    ],
                    'can_contain_blocks' => false,
                ],
            ],
            [
                'key' => 'image',
                'name' => 'Image',
                'icon' => 'photo',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'image',
                            'key' => 'image',
                            'label' => 'Image',
                        ],
                        [
                            'type' => 'text',
                            'key' => 'alt',
                            'label' => 'Alt Text',
                            'translatable' => true,
                        ],
                    ],
                    'can_contain_blocks' => false,
                ],
            ],
            [
                'key' => 'gallery',
                'name' => 'Gallery',
                'icon' => 'squares-2x2',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'repeater',
                            'key' => 'images',
                            'label' => 'Images',
                            'fields' => [
                                [
                                    'type' => 'image',
                                    'key' => 'image',
                                    'label' => 'Image',
                                ],
                                [
                                    'type' => 'text',
                                    'key' => 'caption',
                                    'label' => 'Caption',
                                    'translatable' => true,
                                ],
                            ],
                        ],
                    ],
                    'can_contain_blocks' => false,
                ],
            ],
            [
                'key' => 'cta',
                'name' => 'Call to Action',
                'icon' => 'arrow-right',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'text',
                            'key' => 'title',
                            'label' => 'Title',
                            'translatable' => true,
                        ],
                        [
                            'type' => 'text',
                            'key' => 'button_text',
                            'label' => 'Button Text',
                            'translatable' => true,
                        ],
                        [
                            'type' => 'text',
                            'key' => 'button_url',
                            'label' => 'Button URL',
                            'translatable' => true,
                        ],
                        [
                            'type' => 'select',
                            'key' => 'style',
                            'label' => 'Style',
                            'datasource' => 'cta-styles',
                        ],
                    ],
                    'can_contain_blocks' => false,
                ],
            ],
            [
                'key' => 'columns',
                'name' => 'Columns',
                'icon' => 'columns',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'number',
                            'key' => 'columns',
                            'label' => 'Columns',
                            'default' => 2,
                            'min' => 1,
                            'max' => 4,
                        ],
                    ],
                    'can_contain_blocks' => true,
                ],
            ],
            [
                'key' => 'grid',
                'name' => 'Grid',
                'icon' => 'squares-plus',
                'is_global' => false,
                'schema' => [
                    'fields' => [
                        [
                            'type' => 'number',
                            'key' => 'columns',
                            'label' => 'Columns',
                            'default' => 3,
                        ],
                    ],
                    'can_contain_blocks' => true,
                ],
            ],
        ];

        foreach ($blockTypes as $blockType) {
            BlockType::firstOrCreate(
                ['key' => $blockType['key']],
                $blockType
            );
        }
    }
}
