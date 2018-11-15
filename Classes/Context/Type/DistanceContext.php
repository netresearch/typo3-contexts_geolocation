<?php
namespace Netresearch\ContextsGeolocation\Context\Type;
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use \Netresearch\ContextsGeolocation\AbstractAdapter;
use \Netresearch\ContextsGeolocation\Exception;

/**
 * Distance between given point and user's IP.
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Christian Weiske <christian.weiske@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 */
class DistanceContext
    extends \Netresearch\Contexts\Context\AbstractContext
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return boolean True if the context is active, false if not
     */
    public function match(array $arDependencies = array())
    {
        list($bUseMatch, $bMatch) = $this->getMatchFromSession();
        if ($bUseMatch) {
            return $this->invert($bMatch);
        }

        return $this->invert(
            $this->storeInSession(
                $this->matchDistance()
            )
        );
    }

    /**
     * Detects the user's IP position and checks if it is within
     * the given radius.
     *
     * @return boolean True if the user's position is within the given
     *                 radius around the configured position.
     */
    public function matchDistance()
    {
        try {
            $geoip = AbstractAdapter
                ::getInstance(
                    $this->getRemoteAddress()
                );

            $bUnknown   = (bool) $this->getConfValue('field_unknown');
            $arPosition = $geoip->getLocation();

            if ($arPosition === false) {
                //unknown position
                return $bUnknown;
            }

            if (($arPosition['latitude'] == 0)
                && ($arPosition['longitude'] == 0)
            ) {
                //broken position
                return $bUnknown;
            }

            $strPosition    = trim($this->getConfValue('field_position'));
            $strMaxDistance = trim($this->getConfValue('field_distance'));

            if (($strPosition == '') || ($strMaxDistance == '')) {
                //nothing configured? no match.
                return false;
            }

            list($reqLat, $reqLong) = explode(',', $strPosition);

            $flDistance = $this->getDistance(
                $reqLat, $reqLong,
                $arPosition['latitude'], $arPosition['longitude']
            );

            return $flDistance <= ((float) $strMaxDistance);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Calculate distance between two points in kilometers.
     *
     * @param float $latitude1  Latitude of first point
     * @param float $longitude1 Longitude of first point
     * @param float $latitude2  Latitude of second point
     * @param float $longitude2 Longitude of second point
     *
     * @return float Distance in kilometers
     *
     * @link http://en.wikipedia.org/wiki/Haversine_formula
     * @link http://www.codecodex.com/wiki/Calculate_Distance_Between_Two_Points_on_a_Globe#PHP
     */
    protected function getDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $earth_radius = 6371;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat/2) * sin($dLat/2)
            + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2))
            * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;

        return $d;
    }
}
?>
