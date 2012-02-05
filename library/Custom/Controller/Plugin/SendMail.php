<?php

class Custom_Controller_Plugin_SendMail extends Zend_Controller_Plugin_Abstract {

    private $transport;
    protected $config;

    public function __construct() {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'mail');
        $this->config = $config;
        $this->transport = new Zend_Mail_Transport_Smtp($config->smtphost, $config->params->toArray());
    }

    public function __set($name, $value) {
        $this->variables[$name] = $value;
    }

    public function send($user, $subject, $template) {
        $this->variables['url'] = $this->config->host . "/user/active?uid=" . $user['uid'] . "&key=" . $user['code'];
        $this->variables['user'] = $user;    
        
        $view = new Zend_View(array('basePath' => APPLICATION_PATH. "/views/mail"));
        foreach ($this->variables as $key => $value) {
                $view->assign($key,$value);
        }
        $html = $view->render($template);

        $mail = new Zend_Mail("UTF-8");
        
        $mail->setFrom("noreply@exchange.hit.edu.cn", "NoReply")
                ->addTo($user['email'], $user['name'])
                ->setSubject($subject)
                //->setBodyText($text)
                ->setBodyHtml($html);

        $mail->send($this->transport);
    }

}

?>
