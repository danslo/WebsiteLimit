<?php

class Danslo_WebsiteLimit_Block_Permissions_User_Edit_Tab_Main
    extends Mage_Adminhtml_Block_Permissions_User_Edit_Tab_Main
{

    public function _prepareForm()
    {
        /*
         * Let original block fill the fieldset.
         */
        $data = parent::_prepareForm();

        /*
         * Add our own element.
         */
        $fieldset = $this->getForm()->getElement('base_fieldset');
        $fieldset->addField('website_limit', 'select', array(
            'name'      => 'website_limit',
            'label'     => Mage::helper('adminhtml')->__('Limited to website'),
            'id'        => 'website_limit',
            'title'     => Mage::helper('adminhtml')->__('Limited to website'),
            'required'  => false,

            /*
            * A non-limited user should be able to access all websites.
            */
            'values'    => array_merge(
                array(NULL => Mage::helper('adminhtml')->__('All websites')),
                Mage::getModel('core/website')->getCollection()->toOptionArray()
             )
        ));

        /*
         * Repopulate form after adding our own field.
         */
        $formData = Mage::registry('permissions_user')->getData();
        unset($formData['password']);
        $this->getForm()->setValues($formData);

        /*
         * Return parent data.
         */
        return $data;
    }

}
