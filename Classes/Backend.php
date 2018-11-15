<?php
namespace Netresearch\ContextsGeolocation;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Part of geolocation context extension.
 *
 * PHP version 5
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Christian Weiske <christian.weiske@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 */
//use Netresearch\ContextsGeolocation\Adapter\AbstractAdapter;

/**
 * Provides methods used in the backend by flexforms.
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Christian Weiske <christian.weiske@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 */
class Backend
{
    /**
     * Get all countries from static info tables.
     * Uses the three-letter country code as key instead of the uid.
     *
     * @param array  &$params      Additional parameters
     * @param object $parentObject Parent object instance
     *
     * @return void
     */
    public function getCountries(array &$params, $parentObject)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('static_countries');
        $arRows = $queryBuilder->select('cn_iso_3 AS code', 'cn_short_en AS name')
            ->from('static_countries')
            ->orderBy('name', 'ASC')
            ->execute()
            ->fetchAll();
        $params['items'][] = array('- unknown -', '*unknown*');
        foreach ($arRows as $arRow) {
            $params['items'][] = array(
                $arRow['name'], $arRow['code']
            );
        }
    }

    /**
     * Display input field with popup map element to select a position
     * as latitude/longitude points.
     *
     * @param array  $arFieldInfo Information about the current input field
     * @param object $tceforms    Form rendering library object
     *
     * @return string HTML code
     */
    public function inputMapPosition($arFieldInfo, $tceforms)
    {
        if (!is_array($arFieldInfo['row']['type_conf'])) {
            $flex = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($arFieldInfo['row']['type_conf']);
        } else {
            $flex = $arFieldInfo['row']['type_conf'];
        }

        if (is_array($flex)
            && isset($flex['data']['sDEF']['lDEF']['field_position']['vDEF'])
        ) {
             list($lat, $lon) = explode(
                 ',',
                 $flex['data']['sDEF']['lDEF']['field_position']['vDEF']
             );
             $lat      = (float) trim($lat);
             $lon      = (float) trim($lon);
             $jZoom    = 10;
             $inputVal = $flex['data']['sDEF']['lDEF']['field_position']['vDEF'];
        } else {
            // TODO: geoip current address
            $lat      = 51.33876;
            $lon      = 12.3761;
            $jZoom    = 8;
            $inputVal = '';
        }

        $jLat = json_encode($lat);
        $jLon = json_encode($lon);

        if (is_array($flex)
            && isset($flex['data']['sDEF']['lDEF']['field_distance']['vDEF'])
        ) {
            $jRadius = json_encode(
                (float) $flex['data']['sDEF']['lDEF']['field_distance']['vDEF']
            );
        } else {
            $jRadius = 10;
        }

        if ($tceforms instanceof \TYPO3\CMS\Backend\Form\FormEngine) {
            $input = $tceforms->getSingleField_typeInput(
                $arFieldInfo['table'], $arFieldInfo['field'],
                $arFieldInfo['row'], $arFieldInfo
            );
        } elseif ($tceforms instanceof \TYPO3\CMS\Backend\Form\Element\UserElement) {
            $factory = new \TYPO3\CMS\Backend\Form\NodeFactory();
            $nodeInput = $factory->create(
                array(
                    'renderType' => 'input',
                    'tableName' => $arFieldInfo['table'],
                    'databaseRow' => $arFieldInfo['row'],
                    'fieldName' => $arFieldInfo['field'],
                    'parameterArray' => $arFieldInfo,
                )
            );
            $arInput = $nodeInput->render();
            $input = $arInput['html'];
        } else {
           return '';
        }

        preg_match('#id=["\']([^"\']+)["\']#', $input, $arMatches);
        $inputId = $arMatches[1];
        $appKey = $this->getAppKey();

        $html = <<<HTM
$input<br/>

<link rel="stylesheet" href="/typo3conf/ext/contexts_geolocation/Resources/Public/JavaScript/Leaflet/leaflet.css" />
<!--[if lte IE 8]>
    <link rel="stylesheet" href="/typo3conf/ext/contexts_geolocation/Resources/Public/JavaScript/Leaflet/leaflet.ie.css" />
<![endif]-->
<script src="/typo3conf/ext/contexts_geolocation/Resources/Public/JavaScript/Leaflet/leaflet.js"></script>

<script src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=$appKey"></script>

<div id="map"></div>
<style type="text/css">
#map { height: 400px; }
</style>
<script type="text/javascript">
//<![CDATA[


function updatePosition(latlng, marker, circle)
{
    var input = document.getElementById('$inputId');

    input.value = latlng.lat + ", " + latlng.lng;
    
    if (input.dataset.formengineInputName) {
        document.getElementsByName(input.dataset.formengineInputName)[0].value = input.value;
    }
    
    if (input.name) {
        document.getElementsByName(input.name.replace('_hr', ''))[0].value = input.value;
    }
    

    if (marker !== null) {
        marker.setLatLng(latlng);
    }

    if (circle !== null) {
        circle.setLatLng(latlng);
    }
}



// Set view to chosen geographical coordinates
var map = L.map('map', {
    layers: MQ.mapLayer(),
    center: [ $jLat, $jLon ],
    zoom: $jZoom
});
    
// Add marker of current coordinates
var marker = L.marker([$jLat, $jLon]).addTo(map);
marker.dragging.enable();

// Add distance circle
var circle = L.circle(
    [$jLat, $jLon], $jRadius * 1000,
    {
        color       : 'red',
        fillColor   : '#f03',
        fillOpacity : 0.2
    }
).addTo(map);

// Handle dragging of marker
marker.on('drag', function(e) {
    updatePosition(e.target.getLatLng(), null, circle);
});

// Handle click on map
map.on('click', function(e) {
    updatePosition(e.latlng, marker, circle);
});

//TYPO3 7.6 we have data attributes
var distanceId = null;
var distanceElement = document.querySelector('[data-formengine-input-name*="field_distance"]');
if (distanceElement) {
    distanceId = distanceElement.id;
}

//before TYPO3 7.6
var distanceName = document.getElementById('$inputId').name.replace(
    'field_position', 'field_distance'
);

if (distanceId) {
    document.getElementById(distanceId).addEventListener(
        'change', function(e) {
            circle.setRadius(e.target.value * 1000);
        }, false
    );
} else if (distanceName) {
    document.getElementsByName(distanceName)[0].addEventListener(
        'change', function(e) {
            circle.setRadius(e.target.value * 1000);
        }, false
    );
}


// Update map if new latitude/longitude input is provided
document.getElementById('$inputId').addEventListener(
    'change', function(e) {
        var values = e.target.value.split(',');
        var lat    = parseFloat(values[0]);
        var lon    = parseFloat(values[1]);
        var latlon = new L.LatLng(lat, lon);

        updatePosition(latlon, marker, circle);

        map.panTo(latlon);
    }, false
);


//]]>
</script>
HTM;

        return $html;
    }

    /**
     * Check if the extension has been setup properly.
     * Renders a flash message when geoip is not available.
     *
     * @return void
     */
    public function setupCheck()
    {
        try {
            AbstractAdapter::getInstance();
        } catch (Exception $exception) {

            $strMessage =  'The "<tt>geoip</tt>" PHP extension is not available.'
                . ' Geolocation contexts will not work.';
            /* @var $message \TYPO3\CMS\Core\Messaging\FlashMessage */
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $strMessage,
                'Geolocation configuration',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );

            /* @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessageService'
            );
            $queue = $flashMessageService->getMessageQueueByIdentifier();
            $queue->addMessage($message);

        }
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
