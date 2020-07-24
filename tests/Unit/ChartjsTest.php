<?php

namespace Tests\Unit;

use App\Docsets\Chartjs;
use Godbout\DashDocsetBuilder\Services\DocsetBuilder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChartjsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->docset = new Chartjs();
        $this->builder = new DocsetBuilder($this->docset);
    }

    /** @test */
    public function it_can_generate_a_table_of_contents()
    {
        $toc = $this->docset->entries(
            $this->docset->downloadedIndex()
        );

        $this->assertNotEmpty($toc);
    }

    /** @test */
    public function it_can_format_the_documentation_files()
    {
        $leftSidebar = 'book-summary';

        $this->assertStringContainsString(
            $leftSidebar,
            Storage::get($this->docset->downloadedIndex())
        );

        $this->assertStringNotContainsString(
            $leftSidebar,
            $this->docset->format($this->docset->downloadedIndex())
        );
    }
}
