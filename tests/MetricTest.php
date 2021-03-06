<?php
namespace SiteMaster\Plugins\Unl;

use SiteMaster\Core\Auditor\Parser\HTMl5;

class MetricTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getHTMLVersion()
    {
        $metric = new Metric('unl');
        
        $xpath_template_4_0     = $this->getTestXPath('template_4_0.html');
        $xpath_template_3_1     = $this->getTestXPath('template_3_1.html');
        $xpath_template_3_0     = $this->getTestXPath('template_3_0.html');
        $xpath_template_unknown = $this->getTestXPath('example.html');
        
        $this->assertEquals('4.0', $metric->getHTMLVersion($xpath_template_4_0));
        $this->assertEquals('3.1', $metric->getHTMLVersion($xpath_template_3_1));
        $this->assertEquals('3.0', $metric->getHTMLVersion($xpath_template_3_0));
        $this->assertEquals(null, $metric->getHTMLVersion($xpath_template_unknown));
    }

    /**
     * @test
     */
    public function getDEPVersion()
    {
        $metric = new Metric('unl');

        $xpath_template_4_0     = $this->getTestXPath('template_4_0.html');
        $xpath_template_3_1     = $this->getTestXPath('template_3_1.html');
        $xpath_template_3_0     = $this->getTestXPath('template_3_0.html');
        $xpath_template_unknown = $this->getTestXPath('example.html');

        $this->assertEquals('4.0.9', $metric->getDEPVersion($xpath_template_4_0));
        $this->assertEquals('3.1.19', $metric->getDEPVersion($xpath_template_3_1));
        $this->assertEquals('3.0', $metric->getDEPVersion($xpath_template_3_0));
        $this->assertEquals(null, $metric->getDEPVersion($xpath_template_unknown));
    }

    /**
     * @test
     */
    public function getYouTubeEmbeds()
    {
        $metric = new Metric('unl');
        
        $xpath_template = $this->getTestXPath('example.html');
        $this->assertEquals(array('//www.youtube.com/embed/SxPE9xwsXTs'), $metric->getYouTubeEmbeds($xpath_template));

        $xpath_template = $this->getTestXPath('template_4_0.html');
        $this->assertEquals(array(), $metric->getYouTubeEmbeds($xpath_template));
    }

    /**
     * @test
     */
    public function getRootSiteURL()
    {
        $metric = new Metric('unl');

        $xpath_template = $this->getTestXPath('template_3_0.html');
        $this->assertEquals('http://www.unl.edu/', $metric->getRootSiteURL($xpath_template));
        $xpath_template = $this->getTestXPath('template_3_1.html');
        $this->assertEquals('http://www.unl.edu/', $metric->getRootSiteURL($xpath_template));
        $xpath_template = $this->getTestXPath('template_4_0.html');
        $this->assertEquals('http://wdn.unl.edu/', $metric->getRootSiteURL($xpath_template));
    }

    public function getTestXPath($filename)
    {
        $parser = new HTMl5();
        $html = file_get_contents(__DIR__ . '/data/' . $filename);
        return $parser->parse($html);
    }
}
