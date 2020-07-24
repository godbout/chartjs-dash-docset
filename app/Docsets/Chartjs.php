<?php

namespace App\Docsets;

use Godbout\DashDocsetBuilder\Docsets\BaseDocset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class Chartjs extends BaseDocset
{
    public const CODE = 'chartjs';
    public const NAME = 'Chart.js';
    public const URL = 'www.chartjs.org';
    public const INDEX = 'docs/latest/index.html';
    public const PLAYGROUND = 'https://codepen.io/lucusc/pen/pgQRdd';
    public const ICON_16 = '../../icons/icon.png';
    public const ICON_32 = '../../icons/icon@2x.png';
    public const EXTERNAL_DOMAINS = [
        'use.typekit.net',
    ];


    public function grab(): bool
    {
        $toIgnore = implode('|', [
            '/cdn-cgi',
            '/docs/2.9.3',
            '/docs/master',
            '/samples/master'
        ]);

        $toGet = implode('|', [
            '\.css',
            '\.ico',
            '\.js',
            '\.svg',
            '/docs',
            '/samples'
        ]);

        system(
            "echo; wget www.chartjs.org \
                --mirror \
                --trust-server-names \
                --reject-regex='{$toIgnore}' \
                --accept-regex='{$toGet}' \
                --ignore-case \
                --page-requisites \
                --adjust-extension \
                --convert-links \
                --span-hosts \
                --domains={$this->externalDomains()} \
                --directory-prefix=storage/{$this->downloadedDirectory()} \
                -e robots=off \
                --quiet \
                --show-progress",
            $result
        );

        return $result === 0;
    }

    public function entries(string $file): Collection
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        $entries = collect();

        $entries = $entries->merge($this->guideEntries($crawler, $file));
        $entries = $entries->merge($this->sectionEntries($crawler, $file));

        return $entries;
    }

    protected function guideEntries(HtmlPageCrawler $crawler, string $file)
    {
        $entries = collect();

        if (Str::contains($file, "{$this->url()}/docs/latest/index.html")) {
            $crawler->filter('.summary a')->each(function (HtmlPageCrawler $node) use ($entries) {
                $entries->push([
                    'name' => $node->text(),
                    'type' => 'Guide',
                    'path' => $this->url() . '/docs/latest/' . $this->cleanUrl($node->attr('href')),
                ]);
            });
        }

        return $entries;
    }

    protected function sectionEntries(HtmlPageCrawler $crawler, string $file)
    {
        $entries = collect();

        $crawler->filter('h2')->each(function (HtmlPageCrawler $node) use ($entries, $file) {
            $entries->push([
                'name' => $node->text(),
                'type' => 'Section',
                'path' => Str::after($file . '#' . Str::slug($node->text()), $this->innerDirectory())
            ]);
        });

        return $entries;
    }

    /**
     * Some links are not converted correctly by wget once
     * the download is finished. no idea why, but this cleans
     * the few that fail.
     */
    protected function cleanUrl($url)
    {
        return Str::after($url, 'https://www.chartjs.org/docs/latest/');
    }

    public function format(string $file): string
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        $this->removeLeftSidebar($crawler);
        $this->removeMenuAndSharingButtons($crawler);
        $this->removeNavigation($crawler);
        $this->makeContentFullWidth($crawler);

        $this->removeUnwantedCSS($crawler);
        $this->removeUnwantedJavaScript($crawler);

        $this->insertDashTableOfContents($crawler);

        return $crawler->saveHTML();
    }

    protected function removeLeftSidebar(HtmlPageCrawler $crawler)
    {
        $crawler->filter('.book-summary')->remove();
    }

    protected function removeMenuAndSharingButtons(HtmlPageCrawler $crawler)
    {
        $crawler->filter('body')->after(
            "<script>$(document).ready(function () { $('.pull-right.js-toolbar-action, .pull-left.btn').hide(); });</script>"
        );
    }

    protected function removeNavigation(HtmlPageCrawler $crawler)
    {
        $crawler->filter('.navigation')->remove();
    }

    protected function makeContentFullWidth(HtmlPageCrawler $crawler)
    {
        $crawler->filter('.book-body')->setStyle('left', '0px !important');
    }

    protected function removeUnwantedCSS(HtmlPageCrawler $crawler)
    {
        $crawler->filter('link[href*="search.css"]')->remove();
    }

    protected function removeUnwantedJavaScript(HtmlPageCrawler $crawler)
    {
        $crawler->filter('script[src*=analytics]')->remove();
        $crawler->filter('script[src*=search\.js]')->remove();
    }

    protected function insertDashTableOfContents(HtmlPageCrawler $crawler)
    {
        $crawler->filter('.page-inner')
            ->before('<a name="//apple_ref/cpp/Section/Top" class="dashAnchor"></a>');

        $crawler->filter('h2, h3')->each(static function (HtmlPageCrawler $node) {
            $node->before(
                '<a id="' . Str::slug($node->text()) . '" name="//apple_ref/cpp/Section/' . rawurlencode($node->text()) . '" class="dashAnchor"></a>'
            );
        });
    }
}
