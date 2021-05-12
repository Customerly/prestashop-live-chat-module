<?php
/**
 * @author Customerly.
 * @copyright  Customerly  2016-2021
 * @license Apache
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
        $this->version = '2.0.0';
        $this->author = 'customerly.io';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '084fe8aecafea8b2f84cca493377eb9b';
        parent::__construct();
        $this->displayName = $this->l('Customerly Live Chat');
        $this->description = $this->l('Install Live Chat on your Shop');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $result = true;
        if (!parent::install()
            || !Configuration::updateValue('CLY_PROJECT_ID', '')
            || !$this->registerHook('displayBeforeBodyClosingTag')
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
        if (isset($_POST['savecustomerly'])) {
            foreach ($this->fields_form as $form) {
                foreach ($form['form']['input'] as $field) {
                    if (isset($field['validation'])) {
                        $errors = array();
                        $value = Tools::getValue($field['name']);
                        if (isset($field['required']) && $field['required'] && $value == false && (string)$value != '0') {
                            $errors[] = sprintf(Tools::displayError('Field "%s" is required.'), $field['label']);
                        } elseif ($value) {
                            $field_validation = $field['validation'];
                            if (!Validate::$field_validation($value)) {
                                $errors[] = sprintf(Tools::displayError('Field "%s" is invalid.'), $field['label']);
                            }
                        }
                        // Set default value
                        if ($value === false && isset($field['default_value'])) {
                            $value = $field['default_value'];
                        }

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
            'project_id' => Configuration::get('CLY_PROJECT_ID')
        );
        return $fields_values;
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        if (!$this->isCached('module:customerly/views/templates/hook/checkout.tpl', $this->getCacheId())) {
            $project_id = Configuration::get('CLY_PROJECT_ID');
            $live_chat_snippet = '<!-- Customerly Live Chat Snippet Code --><script>!function(){var e=window,i=document,t="customerly",n="queue",o="load",r="settings",u=e[t]=e[t]||[];if(u.t){return void u.i("[customerly] SDK already initialized. Snippet included twice.")}u.t=!0;u.loaded=!1;u.o=["event","attribute","update","show","hide","open","close"];u[n]=[];u.i=function(t){e.console&&!u.debug&&console.error&&console.error(t)};u.u=function(e){return function(){var t=Array.prototype.slice.call(arguments);return t.unshift(e),u[n].push(t),u}};u[o]=function(t){u[r]=t||{};if(u.loaded){return void u.i("[customerly] SDK already loaded. Use customerly.update to change settings.")}u.loaded=!0;var e=i.createElement("script");e.type="text/javascript",e.async=!0,e.src="https://messenger.customerly.io/launcher.js";var n=i.getElementsByTagName("script")[0];n.parentNode.insertBefore(e,n)};u.o.forEach(function(t){u[t]=u.u(t)})}();customerly.load({"app_id":"' . $project_id . '"});</script><!-- End of Customerly Live Chat Snippet Code -->';

            $this->context->smarty->assign('customerly', array(
                'footer_code' => $live_chat_snippet,
            ));
        }
        return $this->fetch('module:customerly/views/templates/hook/checkout.tpl', $this->getCacheId());
    }
}
