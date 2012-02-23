<?php

require_once 'Utility.php';

class ErrorController extends Zend_Controller_Action {

    public function errorAction() {
        $log = Zend_Registry::get('error_log');
        $this->_helper->layout->disableLayout(); //disable layout
        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Application error';
                break;
        }

        $onlineip = getIp();

        $exception = $errors->exception;
        $log->debug($onlineip . PHP_EOL . $exception->getMessage() .
                PHP_EOL . $exception->getTraceAsString() . 
                PHP_EOL . var_export($errors->request->getParams(),true));

        $this->view->exception = $errors->exception;
        $this->view->request = $errors->request;
    }

}

