<?php
namespace SiteMaster\Plugins\Unl\Logger;

use SiteMaster\Core\Auditor\Site\Page;
use SiteMaster\Plugins\Unl\Plugin;

class PageTitleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getPageTitle()
    {
        $plugin = new Plugin();
        $logger = new PageTitle(new Page());
        $parser = new \Spider_Parser();
        $html = file_get_contents($plugin->getRootDirectory() . '/tests/data/template_4_0.html');
        $xpath = $parser->parse($html);

        $this->assertEquals('Content Resource Examples', $logger->getPageTitle($xpath));
    }
}