<?php

class Danslo_WebsiteLimit_Model_Observer
{

    protected function _isAdminUri()
    {
        $requestParts = explode('/', Mage::app()->getRequest()->getRequestUri());
        return $requestParts[2] === 'admin';
    }

    protected function _initAdminSession()
    {
        return Mage::getSingleton('core/session', array('name' => 'adminhtml'));
    }

    protected function _isAdminLoggedIn()
    {
        return Mage::getSingleton('admin/session')->isLoggedIn();
    }

    protected function _getWebsiteLimit()
    {
        return Mage::getSingleton('admin/session')->getUser()->getWebsiteLimit();
    }

    protected function _applyFilters(&$collection, $methods, $ids)
    {
        foreach ($methods as $method) {
            if (method_exists($collection, $method)) {
                call_user_func_array(array($collection, $method), array($ids));
                return true;
            }
        }
        return false;
    }

    protected function _filterCollection(&$collection, $websiteLimit)
    {
        /*
         * We must check instanceof because Magento often forgets to specific event prefixes.
         */
        if ($collection instanceof Mage_Core_Model_Resource_Website_Collection) {
            return $collection->addIdFilter($websiteLimit);
        } elseif ($collection instanceof Mage_Core_Model_Resource_Store_Collection) {
            return $collection->addWebsiteFilter($websiteLimit);
        } elseif ($collection instanceof Mage_Core_Model_Resource_Db_Collection_Abstract ||
            $collection instanceof Mage_Catalog_Model_Resource_Collection_Abstract) {
            $storeIds = Mage::getModel('core/website')->load($websiteLimit)->getStoreIds();

            /*
             * Sometimes we can just use addAttributeToFilter.
             */
            if ($collection instanceof Mage_Core_Model_Resource_Db_Collection_Abstract &&
                method_exists($collection, 'addAttributeToFilter')) {
                $collection->addAttributeToFilter('store_id', array('in' => $storeIds));
                return true;
            }

            /*
             * Other times we need to use specific methods.
             */
            return !$this->_applyFilters($collection, array('addWebsiteFilter'), $websiteLimit) &&
                   !$this->_applyFilters($collection, array('addStoreRestrictions', 'addStoreFilter'), $storeIds);
        }
        return false;
    }

    public function limitWebsites($observer)
    {
        /*
         * Discard anything that is not for the backend.
         */
        if (!$this->_isAdminUri()) {
            return;
        }

        /*
         * Until Magento has picked its own store,
         * we need to set it fake Magento into thinking it has an admin session.
         *
         * I hate to abuse exceptions this way.
         */
        $adminStore = null;
        try {
            Mage::app()->getStore();
        } catch (Exception $e) {
            $adminStore = Mage::getModel('core/store')->load(0);
            Mage::app()->setCurrentStore($adminStore);
        }

        $this->_initAdminSession();

        if (!$this->_isAdminLoggedIn()) {
            return;
        }
        if ($adminStore) {
            Mage::app()->setCurrentStore(null);
        }

        /*
         * Obtain the website limit and validate it.
         */
        $websiteLimit = $this->_getWebsiteLimit();
        if (is_null($websiteLimit)) {
            return;
        }
        if ($websiteLimit) {
            if (!Mage::getModel('core/website')->load($websiteLimit)->hasData()) {
                Mage::throwException('Your user has an invalid website assigned to it. Contact the system administrators.');
            }
        }

        /*
         * Filter the collection.
         */
        $collection = $observer->getCollection();
        if (!$this->_filterCollection($collection, $websiteLimit)) {
            /*
             * For debugging purposes only, so we can inspect which collections are not filtered.
             */
            Mage::log('Unfiltered class: ' . get_class($collection), null, 'websitelimit.log');
        }

        return $observer;
    }

}
