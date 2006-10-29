<?php

/**
 * This file is part of the SysCP project.
 * Copyright (c) 2003-2006 the SysCP Project.
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.syscp.org/misc/COPYING.txt
 *
 * @author     Florian Aders <eleras@syscp.org>
 * @copyright  (c) 2003-2006 Florian Lippert
 * @package    Syscp.Modules
 * @subpackage Index
 * @license    GPLv2 http://files.syscp.org/misc/COPYING.txt
 * @version    $Id:admin_index.php 460 2006-04-23 15:07:49 +0200 (So, 23 Apr 2006) martin $
 */

if($this->User['change_serversettings'] == '1')
{
    if(isset($_GET['modulename'])
       && $_GET['modulename'] != ''
       && isset($_GET['vendorname'])
       && $_GET['vendorname'] != '')
    {
        $module = $_GET['modulename'];
        $vendor = $_GET['vendorname'];
        $configFile = SYSCP_PATH_LIB.'modules/'.$vendor.'/'.$module.'/module.conf';
        Syscp::uses('Syscp.Handler.Modules');
        $modulescheck = new Syscp_Handler_Modules();
        $modulescheck->initialize($this->moduleConfig);

        if($modulescheck->checkDeps($vendor, $module))
        {
            if(Syscp::isWriteableFile($configFile)
               && $this->moduleConfig[$vendor][$module]['enabled'] != "core")
            {
                $moduleconf = file_get_contents($configFile);
                $search = '/Module\.enabled.*=.*[true|false]/';
                $replace = 'Module.enabled     = true';
                $moduleconf = preg_replace($search, $replace, $moduleconf);
                file_put_contents($configFile, $moduleconf);
                $cacheFile = SYSCP_PATH_BASE.'cache/modules.config';
                unlink($cacheFile);
            }
            else
            {
                $this->TemplateHandler->showError('SysCP.modulesmanager.error.modulecouldnotbeenabled', $vendor.'/'.$module);
                return false;
            }
        }
        else
        {
            $this->TemplateHandler->set('vendor', $vendor);
            $this->TemplateHandler->set('module', $module);
            $this->TemplateHandler->set('failedEnable', $modulescheck->getFailedModEnabledChecks());
            $this->TemplateHandler->set('failedVersion', $modulescheck->getFailedModVersionChecks());
            $this->TemplateHandler->setTemplate('SysCP/modulesmanager/admin/error.tpl');
            $errorFailedDeps = $this->TemplateHandler->fetch();
            $this->TemplateHandler->showError('SysCP.modulesmanager.error.modulecouldnotbeenableddeps', $errorFailedDeps);
            return false;
        }

        $this->redirectTo(array(
            'module' => 'modulesmanager',
            'action' => 'list'
        ));
    }
    else
    {
        $this->TemplateHandler->showError('SysCP.modulesmanager.missingvals');
        return false;
    }
}

