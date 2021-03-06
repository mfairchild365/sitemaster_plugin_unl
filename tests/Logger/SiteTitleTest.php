<?php
namespace SiteMaster\Plugins\Unl\Logger;

use SiteMaster\Core\Auditor\Site\Page;
use SiteMaster\Plugins\Unl\Plugin;

class SiteTitleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getPageTitle()
    {
        $plugin = new Plugin();
        $logger = new SiteTitle(new Page());
        $parser = new \Spider_Parser();
        $html = file_get_contents($plugin->getRootDirectory() . '/tests/data/template_4_0.html');
        $xpath = $parser->parse($html);

        $this->assertEquals('Web Developer Network', $logger->getSiteTitle($xpath));

        $html = file_get_contents($plugin->getRootDirectory() . '/tests/data/template_3_1.html');
        $xpath = $parser->parse($html);

        $this->assertEquals('Web Developer Network', $logger->getSiteTitle($xpath));

        $html = file_get_contents($plugin->getRootDirectory() . '/tests/data/template_3_0.html');
        $xpath = $parser->parse($html);

        //Don't bother getting the site title for 3.0 sites
        $this->assertEquals(false, $logger->getSiteTitle($xpath));
    }
}
