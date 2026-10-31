<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CssIntegrityTest extends TestCase
{
    /**
     * An unclosed block swallows every rule after it (PicoCSS/colony.css regression:
     * a missing "}" made `.tile-site-marker` inert, so SVG polygons rendered black).
     */
    public function test_every_stylesheet_has_balanced_braces(): void
    {
        foreach (glob(dirname(__DIR__, 2).'/public/css/*.css') as $file) {
            $css = preg_replace('~/\*.*?\*/~s', '', file_get_contents($file));
            $this->assertSame(
                substr_count($css, '{'),
                substr_count($css, '}'),
                basename($file).' has unbalanced braces'
            );
        }
    }
}
