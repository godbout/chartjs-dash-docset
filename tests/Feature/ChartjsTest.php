<?php

namespace Tests\Feature;

use App\Docsets\Chartjs;
use Godbout\DashDocsetBuilder\Services\DocsetBuilder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class ChartjsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->docset = new Chartjs();
        $this->builder = new DocsetBuilder($this->docset);

        if (! Storage::exists($this->docset->downloadedDirectory())) {
            fwrite(STDOUT, PHP_EOL . PHP_EOL . "\e[1;33mGrabbing chartjs..." . PHP_EOL);
            Artisan::call('grab chartjs');
        }

        if (! Storage::exists($this->docset->file())) {
            fwrite(STDOUT, PHP_EOL . PHP_EOL . "\e[1;33mPackaging chartjs..." . PHP_EOL);
            Artisan::call('package chartjs');
        }
    }

    /** @test */
    public function it_has_a_table_of_contents()
    {
        Config::set(
            'database.connections.sqlite.database',
            "storage/{$this->docset->databaseFile()}"
        );

        $this->assertNotEquals(0, DB::table('searchIndex')->count());
    }

    /** @test */
    public function the_left_sidebar_gets_removed_from_the_dash_docset_files()
    {
        $leftSidebar = 'book-summary';

        $this->assertStringContainsString(
            $leftSidebar,
            Storage::get($this->docset->downloadedIndex())
        );

        $this->assertStringNotContainsString(
            $leftSidebar,
            Storage::get($this->docset->innerIndex())
        );
    }

    /** @test */
    public function the_menu_and_sharing_buttons_are_getting_hidden()
    {
        $javascriptToHideThoseShits = "<script>$(document).ready(function () { $('.pull-right.js-toolbar-action, .pull-left.btn').hide(); });</script>";

        $this->assertStringNotContainsString(
            $javascriptToHideThoseShits,
            Storage::get($this->docset->downloadedIndex())
        );

        $this->assertStringContainsString(
            $javascriptToHideThoseShits,
            Storage::get($this->docset->innerIndex())
        );
    }

    /** @test */
    public function the_navigation_gets_removed_from_the_dash_docset_files()
    {
        $navigation = 'navigation navigation';

        $this->assertStringContainsString(
            $navigation,
            Storage::get($this->docset->downloadedIndex())
        );

        $this->assertStringNotContainsString(
            $navigation,
            Storage::get($this->docset->innerIndex())
        );
    }

    /** @test */
    public function the_content_is_being_made_full_width()
    {
        $crawler = HtmlPageCrawler::create(
            Storage::get($this->docset->downloadedIndex())
        );

        $this->assertNull(
            $crawler->filter('.book-body')->getStyle('left')
        );

        $crawler = HtmlPageCrawler::create(
            Storage::get($this->docset->innerIndex())
        );

        $this->assertStringContainsString(
            '0px !important',
            $crawler->filter('.book-body')->getStyle('left')
        );
    }

    /** @test */
    public function the_CSS_gets_updated_in_the_dash_docset_files()
    {
        $this->assertStringContainsString(
            'search.css',
            Storage::get($this->docset->downloadedIndex())
        );

        $this->assertStringNotContainsString(
            'search.css',
            Storage::get($this->docset->innerIndex())
        );
    }

    /** @test */
    public function it_inserts_dash_anchors_in_the_doc_files()
    {
        $this->assertStringContainsString(
            'name="//apple_ref/',
            Storage::get($this->docset->innerIndex())
        );
    }
}
