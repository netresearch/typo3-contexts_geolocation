<?php
namespace Netresearch\ContextsGeolocation\Context\Type;
use B13\Magnets\IpLocation;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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


/**
 * Checks that the country of the user is one of the configured ones.
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Christian Weiske <christian.weiske@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 */
class CountryContext
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

        return $this->invert($this->storeInSession(
            $this->matchCountries()
        ));
    }

    /**
     * Detects the current country and matches it against the list
     * of allowed countries
     *
     * @return boolean True if the user's country is in the list of
     *                 allowed countries, false if not
     */
    public function matchCountries()
    {
        try {
            $strCountries = trim($this->getConfValue('field_countries'));

            if ($strCountries == '') {
                //nothing configured? no match.
                return false;
            }
            $geoip = new IpLocation($this->getRemoteAddress());

            $arCountries = explode(',', $strCountries);
            $strCountry  = $geoip->getCountryCode();
            $strCountry = $this->twoLetterCountryCodeToThreeLetterCountryCode($strCountry);

            if (($strCountry === false)
                && in_array('*unknown*', $arCountries)
            ) {
                return true;
            }
            if (($strCountry !== false)
                && in_array($strCountry, $arCountries)
            ) {
                return true;
            }
        } catch (\Exception $exception) {
        }

        return false;
    }

    /**
     * @param string $countryCode
     * @return string
     */
    protected function twoLetterCountryCodeToThreeLetterCountryCode(string $countryCode): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('static_countries');
        return $queryBuilder->select('cn_iso_3')
            ->from('static_countries')
            ->where(
                $queryBuilder->expr()->eq(
                    'cn_iso_2',
                    $queryBuilder->createNamedParameter($countryCode)
                )
            )
            ->execute()
            ->fetchColumn(0);
    }
}

