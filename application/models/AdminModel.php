<?php

class AdminModel extends Zend_Db_Table {
    protected function _setup() {
        $this->_name = 'admin';
        $this->_primary = 'aid';
        parent::_setup();
    }
}

?>
