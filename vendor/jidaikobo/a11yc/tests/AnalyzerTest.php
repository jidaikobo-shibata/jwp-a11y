<?php

declare(strict_types=1);

namespace Jidaikobo\A11yc\Tests;

use Jidaikobo\A11yc\Analyzer;
use Jidaikobo\A11yc\CheckRegistry;
use PHPUnit\Framework\TestCase;

final class AnalyzerTest extends TestCase
{
    public function testAnalyzeResultSetReturnsStructuredSummary(): void
    {
        $analyzer = new Analyzer();

        $result = $analyzer->analyzeResultSet(
            'https://example.com/',
            array(
                'errors' => array(
                    'errors' => array(),
                    'notices' => array(),
                ),
                'errs_cnts' => array(
                    'a' => 0,
                    'aa' => 0,
                    'aaa' => 0,
                ),
            ),
            array(
                'url' => 'https://example.com/',
                'user_agent' => 'using',
            )
        );

        self::assertSame('https://example.com/', $result['meta']['url']);
        self::assertIsArray($result['issues']);
        self::assertSame(0, $result['summary']['error_count']);
        self::assertSame(0, $result['summary']['notice_count']);
        self::assertSame(array('a' => 0, 'aa' => 0, 'aaa' => 0), $result['summary']['counts_by_level']);
    }

    public function testExtractImagesReturnsImageSummary(): void
    {
        $analyzer = new Analyzer();

        $images = $analyzer->extractImages(
            '<img src="/a.png" alt="sample">',
            array(
                'url' => 'https://example.com/',
            )
        );

        self::assertCount(1, $images);
        self::assertSame('img', $images[0]['element']);
        self::assertStringContainsString('sample', (string) $images[0]['alt']);
        self::assertStringContainsString('example.com', (string) $images[0]['src']);
        self::assertStringContainsString('/a.png', (string) $images[0]['src']);
    }

    public function testAnalyzeHtmlReturnsIssuesForRealHtml(): void
    {
        $analyzer = new Analyzer();

        try {
            ob_start();
            $result = $analyzer->analyzeHtml(
                '<!doctype html><html lang="ja"><head><title>Test</title></head><body><img src="/a.png"></body></html>',
                array(
                    'url' => 'https://example.com/',
                )
            );
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        self::assertSame('https://example.com/', $result['meta']['url']);
        self::assertGreaterThanOrEqual(1, $result['summary']['error_count']);
        self::assertNotEmpty($result['issues']);
        self::assertContains(
            'alt_attr_of_img',
            array_column($result['issues'], 'id')
        );
    }

    public function testImageReferencesResolveAgainstDocumentUrl(): void
    {
        $analyzer = new Analyzer();
        $base = 'https://example.com/dir/page.html';
        foreach (array(
            '/a.png' => 'https://example.com/a.png',
            'a.png' => 'https://example.com/dir/a.png',
            '../a.png' => 'https://example.com/a.png',
            './a.png' => 'https://example.com/dir/a.png',
            'a%2Fb.png' => 'https://example.com/dir/a%2Fb.png',
            'https://example.com/a%20b.png' => 'https://example.com/a%20b.png',
        ) as $reference => $expected) {
            $images = $analyzer->extractImages('<img src="' . $reference . '" alt="sample">', array('url' => $base));
            self::assertSame($expected, $images[0]['src']);
        }
    }

    public function testZeroAlternativeTextIsNotEmpty(): void
    {
        $analyzer = new Analyzer();
        $options = array('url' => 'https://example.com/', 'checks' => array('AltAttrOfImg'));
        foreach (array('0', '1', '') as $alt) {
            $html = '<img src="/a.png" alt="' . $alt . '">';
            $result = $analyzer->analyzeHtml($html, $options);
            self::assertSame($alt === '', in_array('alt_attr_of_empty', array_column($result['issues'], 'id'), true));
            self::assertSame($alt, $analyzer->extractImages($html, $options)[0]['alt']);
        }
    }

    public function testButtonImagesAreImportantRegardlessOfAttributes(): void
    {
        $analyzer = new Analyzer();
        foreach (array('<button>', '<button type="button">', '<BUTTON>') as $opening) {
            $images = $analyzer->extractImages(
                $opening . '<img src="/a.png" alt="Go"></button>',
                array('url' => 'https://example.com/')
            );
            self::assertCount(1, $images);
            self::assertSame('img (button)', $images[0]['element']);
            self::assertTrue($images[0]['is_important']);
        }
    }

    public function testLanguageOverrideSelectsMatchingResources(): void
    {
        $analyzer = new Analyzer();
        $html = '<img src="/a.png">';
        $options = array('url' => 'https://example.com/', 'checks' => array('AltAttrOfImg'));
        $ja = $analyzer->analyzeHtml($html, $options + array('lang' => 'ja'));
        $en = $analyzer->analyzeHtml($html, $options + array('lang' => 'en'));
        self::assertNotEmpty($ja['issues'][0]['message']);
        self::assertNotEmpty($en['issues'][0]['message']);
        self::assertNotSame($ja['issues'][0]['message'], $en['issues'][0]['message']);
        self::assertSame($ja['issues'][0]['message'], $analyzer->analyzeHtml($html, $options)['issues'][0]['message']);
        $explicit = $analyzer->analyzeHtml($html, $options + array(
            'lang' => 'en',
            'resource_path' => A11YC_RESOURCE_PATH,
        ));
        self::assertSame($ja['issues'][0]['message'], $explicit['issues'][0]['message']);
    }

    public function testCheckRegistryScansAvailableChecks(): void
    {
        $available = CheckRegistry::availableChecks();

        self::assertContains('AltAttrOfImg', $available);
        self::assertContains('LinkCheck', $available);
        self::assertSame(array_values(array_unique($available)), $available);
    }

    public function testCheckRegistryCanRegisterExternalChecks(): void
    {
        CheckRegistry::clearExtensions();

        try {
            CheckRegistry::register(
                'ExternalCheck',
                '\\Jidaikobo\\A11yc\\Validate\\Check\\Titleless',
                'check'
            );

            self::assertContains('ExternalCheck', CheckRegistry::availableChecks());
            self::assertSame(
                array('\\Jidaikobo\\A11yc\\Validate\\Check\\Titleless', 'check'),
                CheckRegistry::resolve('ExternalCheck')
            );
        } finally {
            CheckRegistry::clearExtensions();
        }
    }
}
