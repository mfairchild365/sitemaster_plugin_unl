<?php
namespace SiteMaster\Plugins\Unl;

use SiteMaster\Core\Auditor\Logger\Metrics;
use SiteMaster\Core\Auditor\Metric\Mark;
use SiteMaster\Core\Auditor\MetricInterface;
use SiteMaster\Core\Config;
use SiteMaster\Core\Registry\Site;
use SiteMaster\Core\Auditor\Scan;
use SiteMaster\Core\Auditor\Site\Page;
use SiteMaster\Core\Util;

class Metric extends MetricInterface
{
    const MARK_MN_UNL_FRAMEWORK_HTML = 'UNL_FRAMEWORK_HTML';
    const MARK_MN_UNL_FRAMEWORK_DEP = 'UNL_FRAMEWORK_DEP';
    const MARK_MN_UNL_FRAMEWORK_YOUTUBUE = 'UNL_FRAMEWORK_YOUTUBUE';
    
    /**
     * @param string $plugin_name
     * @param array $options
     */
    public function __construct($plugin_name, array $options = array())
    {
        $options = array_replace_recursive(array(
            'title_text' => array(
                self::MARK_MN_UNL_FRAMEWORK_HTML => 'The UNLedu framework HTML is out of date',
                self::MARK_MN_UNL_FRAMEWORK_DEP => 'The UNLedu framework dependents are out of date',
                self::MARK_MN_UNL_FRAMEWORK_YOUTUBUE => 'A Youtube Embed was found',
            ),
            'description_text' => array(
                self::MARK_MN_UNL_FRAMEWORK_HTML => 'The UNLedu framework HTML is out of date',
                self::MARK_MN_UNL_FRAMEWORK_DEP => 'The UNLedu framework dependents are out of date',
                self::MARK_MN_UNL_FRAMEWORK_YOUTUBUE => 'It is important to keep in mind that youtube is blocked in some places around the world, including China.  It is a best practice to host video on mediahub.unl.edu, where the video will not be blocked.',
            ),
            'help_text' => array(
                self::MARK_MN_UNL_FRAMEWORK_HTML => 'For mirroring instructions, see http://www1.unl.edu/wdn/wiki/Mirroring_the_Template_Files',
                self::MARK_MN_UNL_FRAMEWORK_DEP => 'For mirroring instructions, see http://www1.unl.edu/wdn/wiki/Mirroring_the_Template_Files',
                self::MARK_MN_UNL_FRAMEWORK_YOUTUBUE => 'Host the video from [Mediahub](http://mediahub.unl.edu/)',
            ),
            'point_deductions' => array(
                self::MARK_MN_UNL_FRAMEWORK_HTML => 80,
                self::MARK_MN_UNL_FRAMEWORK_DEP => 20,
                self::MARK_MN_UNL_FRAMEWORK_YOUTUBUE => 0,
            )
        ), $options);

        parent::__construct($plugin_name, $options);
    }

    /**
     * Get the human readable name of this metric
     *
     * @return string The human readable name of the metric
     */
    public function getName()
    {
        return 'UNLedu Framework Checker';
    }

    /**
     * Get the Machine name of this metric
     *
     * This is what defines this metric in the database
     *
     * @return string The unique string name of this metric
     */
    public function getMachineName()
    {
        return 'unl_wdn';
    }

    /**
     * Determine if this metric should be graded as pass-fail
     *
     * @return bool True if pass-fail, False if normally graded
     */
    public function isPassFail()
    {
        return false;
    }

    /**
     * Scan a given URI and apply all marks to it.
     *
     * All that this
     *
     * @param string $uri The uri to scan
     * @param \DOMXPath $xpath The xpath of the uri
     * @param int $depth The current depth of the scan
     * @param \SiteMaster\Core\Auditor\Site\Page $page The current page to scan
     * @param \SiteMaster\Core\Auditor\Logger\Metrics $context The logger class which calls this method, you can access the spider, page, and scan from this
     * @return bool True if there was a successful scan, false if not.  If false, the metric will be graded as incomplete
     */
    public function scan($uri, \DOMXPath $xpath, $depth, Page $page, Metrics $context)
    {
        $this->markPage($page, $xpath, $context->getScan());

        return true;
    }

    /**
     * This method will find broken links and mark a page appropriately
     *
     * @param Page $page the page to mark
     * @param \DOMXPath $xpath
     * @param \SiteMaster\Core\Auditor\Scan $scan
     */
    public function markPage(Page $page, \DOMXPath $xpath, Scan $scan)
    {
        $html_version = $this->getHTMLVersion($xpath);
        $dep_version = $this->getDEPVersion($xpath);
        
        //Save these attributes for the page.
        PageAttributes::createPageAttributes($page->id, $html_version, $dep_version);
        
        if (!$scan_attributes = ScanAttributes::getByScansID($scan->id)) {
            $scan_attributes = ScanAttributes::createScanAttributes($scan->id, $html_version, $dep_version);
        } else {
            //Update the scan version if this page's versions are older
            if (version_compare($html_version, $scan_attributes->html_version) == -1) {
                //$html_version is smaller, so decrease the scan attribute version
                $scan_attributes->html_version = $html_version;
                $scan_attributes->save();
            }

            if (version_compare($dep_version, $scan_attributes->dep_version) == -1) {
                //$dep_version is smaller, so decrease the scan attribute version
                $scan_attributes->dep_version = $dep_version;
                $scan_attributes->save();
            }
            
            //Update the root site URL if we need to
            if (empty($scan_attributes->root_site_url) && $root = $this->getRootSiteURL($xpath)) {
                $scan_attributes->root_site_url = $root;
                $scan_attributes->save();
            }
        }
        
        $version_helper = new FrameworkVersionHelper();
        
        if (!$version_helper->isCurrent($html_version, FrameworkVersionHelper::VERSION_NAME_HTML)) {
            //Create a new mark
            $machine_name = self::MARK_MN_UNL_FRAMEWORK_HTML;
            $mark = $this->getMark(
                $machine_name,
                $this->getMarkTitle($machine_name),
                $this->getMarkPointDeduction($machine_name),
                $this->getMarkDescription($machine_name),
                $this->getMarkHelpText($machine_name)
            );
            
            $page->addMark($mark, array(
                'value_found' => $html_version
            ));
        }

        if (!$version_helper->isCurrent($dep_version, FrameworkVersionHelper::VERSION_NAME_DEP)) {
            //Create a new mark
            $machine_name = self::MARK_MN_UNL_FRAMEWORK_DEP;
            $mark = $this->getMark(
                $machine_name,
                $this->getMarkTitle($machine_name),
                $this->getMarkPointDeduction($machine_name),
                $this->getMarkDescription($machine_name),
                $this->getMarkHelpText($machine_name)
            );

            $page->addMark($mark, array(
                'value_found' => $dep_version
            ));
        }
        
        //youtube notice
        $embeds = $this->getYouTubeEmbeds($xpath);
        if (!empty($embeds)) {
            $machine_name = self::MARK_MN_UNL_FRAMEWORK_YOUTUBUE;
            $mark = $this->getMark(
                $machine_name,
                $this->getMarkTitle($machine_name),
                $this->getMarkPointDeduction($machine_name),
                $this->getMarkDescription($machine_name),
                $this->getMarkHelpText($machine_name)
            );
            
            foreach ($embeds as $embed) {
                $page->addMark($mark, array(
                    'value_found' => $embed
                ));
            }
        }
    }

    /**
     * get the name for a mark
     *
     * @param string $machine_name the machine name of the mark
     * @return string
     */
    public function getMarkTitle($machine_name)
    {
        if (isset($this->options['title_text'][$machine_name])) {
            return $this->options['title_text'][$machine_name];
        }

        return 'Framework Error';
    }
    
    /**
     * get the point deduction for a mark
     *
     * @param string $machine_name the machine name of the mark
     * @return double
     */
    public function getMarkPointDeduction($machine_name)
    {
        if (isset($this->options['point_deductions'][$machine_name])) {
            return $this->options['point_deductions'][$machine_name];
        }

        return 0;
    }

    /**
     * get the message for a mark
     *
     * @param string $machine_name the machine name of the mark
     * @return string
     */
    public function getMarkDescription($machine_name)
    {
        if (isset($this->options['description_text'][$machine_name])) {
            return $this->options['description_text'][$machine_name];
        }

        return 'General UNLedu framework error';
    }

    /**
     * get the help text to be used with a mark for a given machine name
     *
     * @param string $machine_name the machine name of the mark
     * @return string
     */
    public function getMarkHelpText($machine_name)
    {
        if (isset($this->options['help_text'][$machine_name])) {
            return $this->options['help_text'][$machine_name];
        }

        return 'Fix this problem';
    }

    /**
     * Get the html version of a page
     * 
     * @param \DOMXPath $xpath the xpath
     * @return null|string the version (null if not found)
     */
    public function getHTMLVersion(\DOMXPath $xpath)
    {
        $version = '';

        //look for >= 3.1 templates
        $nodes = $xpath->query(
            '//xhtml:body/@data-version'
        );

        foreach ($nodes as $node) {
            $version = $node->nodeValue;
        }

        if (!empty($version)) {
            //found >= 3.1 templates
            return $version;
        }

        //Look for 3.0
        $nodes = $xpath->query(
            '//xhtml:script/@src'
        );

        foreach ($nodes as $node) {
            if (stripos($node->nodeValue, 'templates_3.0') !== false) {
                //Found 3.0
                return '3.0';
            }
        }

        //Couldn't find anything.
        return null;
    }

    /**
     * Get the dependency version of a page
     * 
     * @param \DOMXPath $xpath the xpath of the page
     * @return null|string the version (null if not found)
     */
    public function getDEPVersion(\DOMXPath $xpath)
    {
        $version = '';

        //look for >= 3.1 templates
        $nodes = $xpath->query(
            "//xhtml:script[@id='wdn_dependents']/@src"
        );

        foreach ($nodes as $node) {
            $version = $node->nodeValue;
        }

        $matches = array();

        if (preg_match('/all.js\?dep=([0-9.]*)/', $version, $matches) && isset($matches[1])) {
            //found look for >= 3.1 templates
            return $matches[1];
        }

        //look for 3.0
        $nodes = $xpath->query(
            '//xhtml:script/@src'
        );

        foreach ($nodes as $node) {
            if (stripos($node->nodeValue, 'templates_3.0') !== false) {
                //found 3.0
                return '3.0';
            }
        }

        //Couldn't find anything.
        return null;
    }

    /**
     * Determine if a youtube was embedded in the page
     * 
     * @param \DomXPath $xpath the xpath of the page
     * @return array an array of youtube embed sources will be returned
     */
    public function getYouTubeEmbeds(\DomXPath $xpath) {
        //look for youtubue embeds
        $nodes = $xpath->query(
            "//xhtml:iframe[contains(@src,'//www.youtube.com/embed/')]"
        );
        
        $sources = array();
        foreach ($nodes as $node) {
            $sources[] = $node->getAttribute('src');
        }
        
        return $sources;
    }

    /**
     * Get the root site for this page.  The root site is the first site found in the breadcrumbs, as long as it is not 'www.unl.edu'.
     * A root site is usually a college or department.
     * 
     * @param \DomXpath $xpath
     * @return bool
     */
    public function getRootSiteURL(\DomXpath $xpath)
    {
        //look for youtubue embeds
        $nodes = $xpath->query(
            "(//xhtml:*[@id='breadcrumbs']/xhtml:ul/xhtml:li|//xhtml:*[@id='breadcrumbs']/xhtml:ul/xhtml:li/xhtml:span)/xhtml:a"
        );
        
        switch ($nodes->length) {
            case 0:
                break;
            case 1:
            case 2:
                return $nodes->item(0)->getAttribute('href');
                break;
            default:
                //There are more than 2 bread crumbs
                if ($nodes->item(0)->getAttribute('href') == 'http://www.unl.edu/') {
                    //Most of the time, www.unl.edu will be the root, but we actually want the second.
                    return $nodes->item(1)->getAttribute('href');
                }
                return $nodes->item(0)->getAttribute('href');
        }

        return false;
    }
}
