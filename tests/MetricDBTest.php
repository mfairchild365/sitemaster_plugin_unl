<?php
namespace SiteMaster\Plugins\Unl;

use SiteMaster\Core\Auditor\Scan;
use SiteMaster\Core\Auditor\Site\Page;
use SiteMaster\Core\DBTests\BaseTestDataInstaller;
use SiteMaster\Core\DBTests\DBTestCase;
use SiteMaster\Core\Registry\Site;

class MetricDBTest extends DBTestCase
{
    /**
     * @test
     */
    public function markPage()
    {
        $this->setUpDB();

        $metric = new Metric('metric_links');
        $metric_record = $metric->getMetricRecord();
        $site = Site::getByBaseURL('http://www.test.com/');
        $scan = Scan::createNewScan($site->id);
        $page_4_0 = Page::createNewPage($scan->id, $site->id, 'http://test.com/4_0');
        $page_3_1 = Page::createNewPage($scan->id, $site->id, 'http://test.com/3_1');
        $page_3_0 = Page::createNewPage($scan->id, $site->id, 'http://test.com/3_0');

        $xpath_template_4_0     = $this->getTestXPath('template_4_0.html');
        $xpath_template_3_1     = $this->getTestXPath('template_3_1.html');
        $xpath_template_3_0     = $this->getTestXPath('template_3_0.html');
        
        $metric->markPage($page_4_0, $xpath_template_4_0, $scan);
        
        $page_attributes = PageAttributes::getByScannedPageID($page_4_0->id);
        $scan_attributes = ScanAttributes::getByScansID($scan->id);
        
        $this->assertEquals('4.0', $page_attributes->html_version);
        $this->assertEquals('4.0.9', $page_attributes->dep_version);
        $this->assertEquals('4.0', $scan_attributes->html_version);
        $this->assertEquals('4.0.9', $scan_attributes->dep_version);

        $metric->markPage($page_3_1, $xpath_template_3_1, $scan);
        $scan_attributes->reload();
        
        $this->assertEquals('3.1', $scan_attributes->html_version);
        $this->assertEquals('3.1.19', $scan_attributes->dep_version);
        
        //check marks
        $metric->markPage($page_3_0, $xpath_template_3_0, $scan);

        $machine_names_found = array();
        foreach ($page_3_0->getMarks($metric_record->id) as $page_mark) {
            $mark = $page_mark->getMark();
            $machine_names_found[] = $mark->machine_name;
        }

        $this->assertContains(Metric::MARK_MN_UNL_FRAMEWORK_HTML, $machine_names_found);
        $this->assertContains(Metric::MARK_MN_UNL_FRAMEWORK_DEP, $machine_names_found);
    }

    public function setUpDB()
    {
        $plugin = new Plugin();

        //Uninstall plugin data
        $plugin->onUninstall();

        //clean and install base db
        $this->cleanDB();
        $this->installBaseDB();

        //Install plugin data
        $plugin->onInstall();

        //Install basic moc data
        $this->installMockData(new BaseTestDataInstaller());
    }

    public function getTestXPath($filename)
    {
        $parser = new \Spider_Parser();
        $html = file_get_contents(__DIR__ . '/data/' . $filename);
        return $parser->parse($html);
    }
}
