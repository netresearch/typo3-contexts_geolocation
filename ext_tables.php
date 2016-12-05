<?php
/**
 * Geolocation contexts: Database table backend configuration
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
defined('TYPO3_MODE') or die('Access denied.');

$arPluginList = array(
    'Position'       => false,
);

foreach ($arPluginList as $strPluginName => $bUseFlexform) {
    $strPluginKey = strtolower(str_replace('_', '', $_EXTKEY) . '_' . $strPluginName);
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        $_EXTKEY,
        $strPluginName,
        $strPluginName
    );
    if ($bUseFlexform) {
        $TCA['tt_content']['types']['list']['subtypes_addlist'][$strPluginKey]
            = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            $strPluginKey,
            'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/'.$strPluginName.'.xml'
        );
    }
}

?>
