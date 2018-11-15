<?php

defined('TYPO3_MODE') or die('Access denied.');

call_user_func(function () {
    $arPluginList = array(
        'Position'       => false,
    );

    foreach ($arPluginList as $strPluginName => $bUseFlexform) {
        $strPluginKey = strtolower(str_replace('_', '', $_EXTKEY) . '_' . $strPluginName);
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Netresearch.context_geolocation',
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
});
