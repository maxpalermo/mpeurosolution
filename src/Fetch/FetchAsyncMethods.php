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

namespace MpSoft\MpEurosolution\Fetch;

use MpSoft\MpEurosolution\Models\ModelCustomerEurosolution;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FetchAsyncMethods
{
    protected $sessionJSON;
    protected $templatePath;
    protected $context;
    protected $module;

    public function __construct()
    {
        $data = file_get_contents('php://input');
        $this->module = \Module::getInstanceByName('mpeurosolution');
        $this->context = \Context::getContext();
        $this->sessionJSON = json_decode($data, true);
        $this->templatePath = $this->module->getLocalPath() . 'views/templates/admin/';
    }

    protected function ajaxRender($message)
    {
        header('Content-Type: application/json');
        exit(json_encode($message));
    }

    protected function getTemplate($template)
    {
        return $this->context->smarty->createTemplate(
            $this->templatePath . $template,
            $this->context->smarty
        );
    }

    public function run()
    {
        if (isset($this->sessionJSON['action']) && isset($this->sessionJSON['ajax'])) {
            $action = $this->sessionJSON['action'];
            if (method_exists($this, $action)) {
                $this->ajaxRender($this->$action($this->sessionJSON));
                exit;
            }
        }

        return $this->displayAjaxError();
    }

    public function displayAjaxError()
    {
        $this->ajaxRender(['error' => 'NO METHOD FOUND']);
    }

    public function updateEurosolutionId($params)
    {
        $MPEUROSOLUTION_customerId = (int) $params['id_customer'];
        $eurosolutionId = (int) $params['id_eurosolution'];
        $MPEUROSOLUTION_employeeId = (int) $params['id_employee'];

        try {
            $model = new ModelCustomerEurosolution($MPEUROSOLUTION_customerId);
            if (\Validate::isLoadedObject($model)) {
                $model->id_eurosolution = $eurosolutionId;
                $model->id_employee = $MPEUROSOLUTION_employeeId;
                $model->date_upd = date('Y-m-d H:i:s');
                $result = $model->update();
            } else {
                $model->force_id = true;
                $model->id = $MPEUROSOLUTION_customerId;
                $model->id_eurosolution = $eurosolutionId;
                $model->id_employee = $MPEUROSOLUTION_employeeId;
                $model->date_add = date('Y-m-d H:i:s');
                $model->date_upd = date('Y-m-d H:i:s');
                $result = $model->add();
            }
            if ($result) {
                $message = sprintf('Il cliente %s è stato aggiornato con l\'ID Eurosolution %s', $MPEUROSOLUTION_customerId, $eurosolutionId);
            } else {
                $message = sprintf('Il cliente %s non è stato aggiornato.', $MPEUROSOLUTION_customerId);
                if (\Db::getInstance()->getMsgError()) {
                    $message .= ' Error: ' . \Db::getInstance()->getMsgError();
                }
            }
        } catch (\Throwable $th) {
            $result = false;
            $message = $th->getMessage();
        }

        return [
            'success' => $result,
            'message' => $message,
            'button' => $this->renderButton(['id_customer' => $MPEUROSOLUTION_customerId]),
        ];
    }

    public function renderButton($params)
    {
        $id_customer = (int) ($params['id_customer'] ?? 0);
        $id_order = (int) ($params['id_order'] ?? 0);

        if ($id_order && !$id_customer) {
            $order = new \Order($id_order);
            if (\Validate::isLoadedObject($order)) {
                $id_customer = (int) $order->id_customer;
            }
        }

        $model = new ModelCustomerEurosolution($id_customer);
        if (\Validate::isLoadedObject($model)) {
            $eurosolutionId = (int) $model->id_eurosolution;
        } else {
            $eurosolutionId = 0;
        }
        $template = $this->getTemplate('eurosolutionBtn.tpl');
        $template->assign([
            'MPEUROSOLUTION_customerId' => $id_customer,
            'eurosolutionId' => $eurosolutionId,
            'MPEUROSOLUTION_employeeId' => (int) $model->id_employee,
            'id_order' => $id_order,
        ]);

        return $template->fetch();
    }

    public function renderCustomerEurosolutionRow($params)
    {
        $id_customer = (int) ($params['id_customer'] ?? 0);
        $id_employee = (int) ($params['id_employee'] ?? 0);

        $model = new ModelCustomerEurosolution($id_customer);
        if (\Validate::isLoadedObject($model)) {
            $eurosolutionId = (int) $model->id_eurosolution;
        } else {
            $eurosolutionId = 0;
        }
        $template = $this->getTemplate('eurosolutionRow.tpl');
        $template->assign([
            'MPEUROSOLUTION_customerId' => $id_customer,
            'eurosolutionId' => $eurosolutionId,
            'MPEUROSOLUTION_employeeId' => $id_employee,
        ]);

        return $template->fetch();
    }
}
