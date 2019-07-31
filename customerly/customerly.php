<?php
/**
 * @author Customerly.
 * @copyright  Customerly  2016-2019
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Customerly extends Module
{
    public $_html = '';
    public $fields_form;
    public $fields_value;
    public $validation_errors = array();

    public function __construct()
    {
        $this->name = 'customerly';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'customerly.io';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '084fe8aecafea8b2f84cca493377eb9b';
        parent::__construct();
        $this->displayName = $this->l('Customerly Live Chat');
        $this->description = $this->l('This module allows you to add a complete Live Chat');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $result = true;
        if (!parent::install()
            || !Configuration::updateValue('CLY_PROJECT_ID', '')
            || !$this->registerHook('displayHeader')
        ) {
            $result = false;
        }
        return $result;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
        ) {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        $this->initFieldsForm();
        if (Tools::getIsset(Tools::getValue('savecustomerly'))) {
            foreach ($this->fields_form as $form) {
                foreach ($form['form']['input'] as $field) {
                    if (Tools::getIsset($field['validation'])) {
                        $errors = array();
                        $value = Tools::getValue($field['name']);
                        if (Tools::getIsset($field['required']) && $field['required'] && $value == false && (string)$value != '0')
                            $errors[] = sprintf(Tools::displayError('Field "%s" is required.'), $field['label']);
                        elseif ($value) {
                            if (!Validate::$field_validation($value))
                                $errors[] = sprintf(Tools::displayError('Field "%s" is invalid.'), $field['label']);
                        }
                        // Set default value
                        if ($value === false && isset($field['default_value']))
                            $value = $field['default_value'];

                        if (count($errors)) {
                            $this->validation_errors = array_merge($this->validation_errors, $errors);
                        } elseif ($value == false) {
                            switch ($field['validation']) {
                                case 'isUnsignedId':
                                case 'isUnsignedInt':
                                case 'isInt':
                                case 'isBool':
                                    $value = 0;
                                    break;
                                default:
                                    $value = '';
                                    break;
                            }
                            Configuration::updateValue('CLY_' . Tools::strtoupper($field['name']), $value);
                        } else
                            Configuration::updateValue('CLY_' . Tools::strtoupper($field['name']), $value, true);
                    }
                }
            }

            if (count($this->validation_errors)) {
                $this->_html .= $this->displayError(implode('<br/>', $this->validation_errors));
            } else {
                $this->_html .= $this->displayConfirmation($this->getTranslator()->trans('Settings updated', array(), 'Admin.Theme.Panda'));
            }
        }

        $helper = $this->initForm();
        return $this->_html . $helper->generateForm($this->fields_form);
    }

    protected function initFieldsForm()
    {
        $this->fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->displayName,
            ),
            'input' => array(
                array(
                    'type' => 'textarea',
                    'label' => $this->getTranslator()->trans('Customerly Project ID', array(), 'Modules.Customerly.Admin'),
                    'name' => 'project_id',
                    'cols' => 80,
                    'rows' => 1,
                    'desc' => $this->getTranslator()->trans('You can find this Project ID in your Customerly Project Settings', array(), 'Modules.Customerly.Admin'),
                    'validation' => 'isAnything',
                ),
            ),
            'submit' => array(
                'title' => $this->getTranslator()->trans('   Save   ', array(), 'Admin.Actions')
            )
        );
    }

    protected function initForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'savecustomerly';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        return $helper;
    }

    private function getConfigFieldsValues()
    {
        $fields_values = array(
            'project_id' => Configuration::get('CLY_PROJECT_ID'),
        );
        return $fields_values;
    }

    public function hookDisplayHeader($params)
    {
        if (!$this->isCached('module:customerly/views/templates/hook/header.tpl', $this->getCacheId())) {
            $project_id = Configuration::get('CLY_PROJECT_ID');
            $live_chat_snippet = '<!-- Customerly Integration Code --><script>window.customerlySettings = {app_id: "' . $project_id . '"};!function(){function e(){var e=t.createElement("script");e.type="text/javascript",e.async=!0,e.src="https://widget.customerly.io/widget/' . $project_id . '";var r=t.getElementsByTagName("script")[0];r.parentNode.insertBefore(e,r)}var r=window,t=document,n=function(){n.c(arguments)};r.customerly_queue=[],n.c=function(e){r.customerly_queue.push(e)},r.customerly=n,r.attachEvent?r.attachEvent("onload",e):r.addEventListener("load",e,!1)}();</script>';
            $this->context->smarty->assign('customerly', array(
                'head_code' => $live_chat_snippet,
            ));
        }
        return $this->fetch('module:customerly/views/templates/hook/header.tpl', $this->getCacheId());
    }
}
