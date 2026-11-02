<?php

namespace Tests\Unit;

use App\Support\Blocks\BlockRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BlockRegistryTest extends TestCase
{
    #[Test]
    public function renderable_skips_unknown_and_inactive_blocks(): void
    {
        $blocks = [
            ['type' => 'rich_text', 'data' => ['is_active' => true]],
            ['type' => 'php_eval', 'data' => ['is_active' => true]],
            ['type' => 'stats', 'data' => ['is_active' => false]],
            'bozuk-veri',
            ['type' => 'contact_cta', 'data' => []],
        ];

        $types = array_column(BlockRegistry::renderable($blocks), 'type');

        $this->assertSame(['rich_text', 'contact_cta'], $types);
    }

    #[Test]
    public function corporate_pages_do_not_offer_home_only_blocks(): void
    {
        $types = BlockRegistry::types(includeHomeOnly: false);

        $this->assertContains('rich_text', $types);
        $this->assertContains('features', $types);
        $this->assertNotContains('hero_slider', $types);
        $this->assertNotContains('featured_products', $types);
    }

    #[Test]
    public function every_block_has_at_least_one_variant(): void
    {
        foreach (BlockRegistry::BLOCKS as $type => $block) {
            $this->assertNotEmpty($block['variants'], "{$type} için varyant tanımlı değil");
        }

        $this->assertSame('blocks.featured-products', BlockRegistry::view('featured_products'));
    }
}
