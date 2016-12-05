<?php
namespace Netresearch\ContextsGeolocation\Controller;
use Netresearch\ContextsGeolocation\AbstractAdapter;

/**
 * Plugin to display the location of an IP address on a map
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Christian Weiske <christian.weiske@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 */
class PositionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * Plugin main function.
     *
     *
     * @return string The content after this plugin.
     */
    public function showAction()
    {
        $ip    = $this->getIp();
        $geoip = AbstractAdapter::getInstance($ip);
        $data  = $geoip->getLocation();

        $this->view->assignMultiple(
            array(
                'form' => $this->renderForm($ip),
                'map'  => $this->renderMap($ip, $data),
                'data' =>  $this->renderData($ip, $data)
            )
        );
    }

    /**
     * Returns IP from $_GET['ip'] or user's address.
     *
     * If $_GET[ip] is invalid, th user's address is used.
     *
     * @return string IP address
     */
    protected function getIp()
    {
        if (!isset($_GET['ip'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        $strGetIp = $_GET['ip'];
        $bIpv4 = filter_var(
                $strGetIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4
            ) !== false;
        $bIpv6 = filter_var(
                $strGetIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6
            ) !== false;

        if (!$bIpv4 && !$bIpv6) {
            //invalid IP
            return $_SERVER['REMOTE_ADDR'];
        }

        return $strGetIp;
    }

    /**
     * Renders the IP input form
     *
     * @param string $ip IP number
     *
     * @return string HTML code
     */
    protected function renderForm($ip)
    {
        $strIp     = htmlspecialchars($ip);
        $strPageId = htmlspecialchars($GLOBALS['TSFE']->id);
        $actionUrl = $this->controllerContext->getUriBuilder()->setTargetPageUid(
            $GLOBALS['TSFE']->id
        )->build();

        return <<<HTM
<form method="get" action="$actionUrl">
    <input type="hidden" name="id" value="$strPageId" />
    <input type="text" size="16" name="ip" value="$strIp" placeholder="IP address" />
    <input type="submit" value="Submit" />
</form>
HTM;
    }

    /**
     * Renders the data we get from the geolocation database
     *
     * @param string $ip   IP number
     * @param array  $data Array of location data from geoip query
     *
     * @return string HTML code
     */
    protected function renderData($ip, $data)
    {
        return '<h3>Data about this IP</h3>'
            . '<pre>'
            . htmlspecialchars(var_export($data, true))
            . '</pre>';
    }

    /**
     * Renders the map
     *
     * @param string $ip   IP number
     * @param array  $data Array of location data from geoip query
     *
     * @return string HTML code
     */
    protected function renderMap($ip, $data)
    {
        if ($data['latitude'] == 0 && $data['longitude'] == 0) {
            //broken position
            return '';
        }

        $flLat = (float) $data['latitude'];
        $flLon = (float) $data['longitude'];
        $jLat  = json_encode($flLat);
        $jLon  = json_encode($flLon);
        $jZoom = 10;
        $appKey = $this->getAppKey();

        return <<<HTM
<link rel="stylesheet" href="/typo3conf/ext/contexts_geolocation/Resources/Public/JavaScript/Leaflet/leaflet.css" />
<!--[if lte IE 8]>
    <link rel="stylesheet" href="/typo3conf/ext/contexts_geolocation/Resources/Public/JavaScript/Leaflet/leaflet.ie.css" />
<![endif]-->
<script src="/typo3conf/ext/contexts_geolocation/Resources/Public/JavaScript/Leaflet/leaflet.js"></script>


<script src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=$appKey"></script>
<div id="map" style="margin: 10px 0px"></div>
<style type="text/css">
#map { height: 300px; }
</style>
<script type="text/javascript">


     // Set view to chosen geographical coordinates
    var map = L.map('map', {
        layers: MQ.mapLayer(),
        center: [ $jLat, $jLon ],
        zoom: $jZoom
    });
    // Add marker of current coordinates
    L.marker([$jLat, $jLon]).addTo(map);



</script>
HTM;
    }

    /**
     * Get the aopp key for mapquest api
     *
     * @return string|null app key
     */
    protected function getAppKey()
    {
        $arExtConf = unserialize(
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['contexts_geolocation']
        );

        return $arExtConf['app_key'];
    }
}
?>
