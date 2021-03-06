<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class HomeAPIModule extends APIModule
{
    protected $id = 'home';
    protected $vmin = 1;
    protected $vmax = 1;

    public function initializeForCommand() {
        switch ($this->command)
        {
            case 'notice':
                $response = null;
                $responseVersion = 1;
                if ($this->getOptionalModuleVar('BANNER_ALERT', false, 'notice')) {
                    $noticeData = $this->getOptionalModuleSection('notice');
                    if ($noticeData) {
                        $response = array(
                            'notice'=>'',
                            'moduleID'=>null,
                            'link'=>$this->getOptionalModuleVar('BANNER_ALERT_MODULE_LINK', false, 'notice')
                        );
                        // notice can either take a module or data model class or retriever class. The section is passed on. It must implement the HomeAlertInterface interface
        
                        if (isset($noticeData['BANNER_ALERT_MODULE'])) {
                            $moduleID = $noticeData['BANNER_ALERT_MODULE'];
                            $controller = WebModule::factory($moduleID);
                            $response['moduleID'] = $moduleID;
                            $string = "Module $moduleID";
                        } elseif (isset($noticeData['BANNER_ALERT_MODEL_CLASS'])) {
                            $controller = DataModel::factory($noticeData['BANNER_ALERT_MODEL_CLASS'], $noticeData);
                            $string = $noticeData['BANNER_ALERT_MODEL_CLASS'];
                        } elseif (isset($noticeData['BANNER_ALERT_RETRIEVER_CLASS'])) {
                            $controller = DataRetriever::factory($noticeData['BANNER_ALERT_RETRIEVER_CLASS'], $noticeData);
                            $string = $noticeData['BANNER_ALERT_RETRIEVER_CLASS'];
                        } else {
                            throw new KurogoConfigurationException("Banner alert not properly configured");
                        }
        
                        if (!$controller instanceOf HomeAlertInterface) {
                            throw new KurogoConfigurationException("$string does not implement HomeAlertModule interface");
                        } 
        
                        $response['notice'] = $controller->getHomeScreenAlert();
                    }
                }

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;
            default:
                $this->invalidCommand();
        }
    }
}


