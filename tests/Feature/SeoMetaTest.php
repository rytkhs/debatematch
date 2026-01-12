<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    public function test_homepage_has_seo_meta(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $siteName = config('app.name', 'DebateMatch');
        $title = __('misc.home_meta_title') . ' - ' . $siteName;
        $description = __('misc.home_meta_description');
        $canonical = rtrim(config('app.url', 'http://localhost'), '/') . '/';

        $response->assertSee('<title>' . $title . '</title>', false);
        $response->assertSee('name="description" content="' . $description . '"', false);
        $response->assertSee('rel="canonical" href="' . $canonical . '"', false);
        $response->assertSee('property="og:title" content="' . $title . '"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
    }
}
