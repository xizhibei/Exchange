<?php

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
        if (getenv('HTTP_CLIENT_IP')) {
            $onlineip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $onlineip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR')) {
            $onlineip = getenv('REMOTE_ADDR');
        } else {
            $onlineip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
        }

        $exception = $errors->exception;
        $log->debug($onlineip . PHP_EOL . $exception->getMessage() .
                PHP_EOL . $exception->getTraceAsString());

        $this->view->exception = $errors->exception;
        $this->view->request = $errors->request;
    }

}

