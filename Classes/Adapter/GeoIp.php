<?php
namespace Netresearch\ContextsGeolocation\Adapter;

/**
 * Part of geolocation context extension.
 *
 * PHP version 5
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Rico Sonntag <rico.sonntag@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 */

/**
 * Provides an adapter to the PHP "geoip" extension.
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Rico Sonntag <rico.sonntag@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 * @uses       http://www.php.net/manual/en/book.geoip.php
 */
class GeoIp
    extends \Netresearch\ContextsGeolocation\AbstractAdapter
{
    /**
     * Constructor. Protected to prevent direct instanciation.
     *
     * @param string $ip IP address
     *
     * @return void
     */
    private function __construct($ip = null)
    {
        $this->ip = $ip;
    }

    /**
     * Prevent cloning of class.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Get instance of class. Returns null if geoip extension is
     * not available.
     *
     * @param string $ip IP address
     *
     * @return \Netresearch\ContextsGeolocation\Adapter\GeoIp|null
     */
    public static function getInstance($ip = null)
    {
        if (extension_loaded('geoip')) {
            return new self($ip);
        }

        return null;
    }

    /**
     * Get two-letter continent code.
     *
     * @return string|false Continent code or FALSE on failure
     */
    public function getContinentCode()
    {
        return geoip_continent_code_by_name($this->ip);
    }

    /**
     * Get two or three letter country code.
     *
     * @param boolean $threeLetterCode TRUE to return 3-letter country code
     *
     * @return string|false Country code or FALSE on failure
     */
    public function getCountryCode($threeLetterCode = false)
    {
        if ($threeLetterCode) {
            return geoip_country_code3_by_name($this->ip);
        }

        return geoip_country_code_by_name($this->ip);
    }

    /**
     * Get country name.
     *
     * @return string|false Country name or FALSE on failure
     */
    public function getCountryName()
    {
        return geoip_country_name_by_name($this->ip);
    }

    /**
     * Get location record.
     *
     * @return array|false Location data or FALSE on failure
     */
    public function getLocation()
    {
        $data = geoip_record_by_name($this->ip);

        // Map data
        return array(
            'continentCode' => $data['continent_code'],
            'countryCode'   => $data['country_code'],
            'countryCode3'  => $data['country_code3'],
            'countryName'   => $data['country_name'],
            'region'        => $data['region'],
            'city'          => $data['city'],
            'postalCode'    => $data['postal_code'],
            'latitude'      => $data['latitude'],
            'longitude'     => $data['longitude'],
            'dmaCode'       => $data['dma_code'],
            'areaCode'      => $data['area_code'],
        );
    }

    /**
     * Get country code and region.
     *
     * @return array|false Region data or FALSE on failure
     */
    public function getRegion()
    {
        return geoip_region_by_name($this->ip);
    }

    /**
     * Get name of organization or of the ISP which has registered the
     * IP address range.
     *
     * @return string|false Organization name or FALSE on failure
     */
    public function getOrganization()
    {
        return geoip_org_by_name($this->ip);
    }
}
?>
