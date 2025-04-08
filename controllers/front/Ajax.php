<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
class MpEurosolutionAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    protected function response($params)
    {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode($params));
    }

    public function displayAjax()
    {
        $action = Tools::getValue('action');
        if (!$action) {
            $this->response(['error' => $this->trans('Azione non valida.')]);
        }
        $action = 'ajax' . Tools::ucfirst($action);
        if (!method_exists($this, $action)) {
            $this->response(['error' => 'Invalid action']);
        }
        $this->response($this->{$action}());
    }
}
