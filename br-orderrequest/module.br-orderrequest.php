<?php

/**
 * @copyright   Copyright (C) 2025-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2026-01-16
 * iTop module definition file
 */

/** @disregard P1009 Undefined type SetupWebPage - The SetupWebPage class is provided by iTop during module registration. */
SetupWebPage::AddModule(
    __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
    'br-orderrequest/0.2.0',
    array(
        // Identification
        //
        'label' => 'Datamodel: Order Request',
        'category' => 'business',

        // Setup
        //
        'dependencies' => array(
            'itop-config-mgmt/3.1.0',
            'itop-service-mgmt/3.2.0',
            'itop-tickets/3.2.0',
        ),
        'mandatory' => false,
        'visible' => true,

        // Components
        //
        'datamodel' => array(),
        'webservice' => array(),
        'data.struct' => array(
            // add your 'structure' definition XML files here,
        ),
        'data.sample' => array(
            // add your sample data XML files here,
        ),

        // Documentation
        //
        'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
        'doc.more_information' => '', // hyperlink to more information, if any

        // Default settings
        //
        'settings' => array(
            // Module specific settings go here, if any
        ),
    )
);
