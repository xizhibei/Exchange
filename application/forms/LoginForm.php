<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
class LoginForm extends Zend_Form {

    public function init() {
        $this->setMethod('post');
        $this->addElement('text', 'email', array(
            'validators' => array(
                array('regex', false, '/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/i')
            ),
            'required' => true,
            'filters' => array('StringToLower'),
            'label' => '邮箱:',
            'decorators' => array(
                array('ViewHelper', array('helper' => 'formText')),
                array('Label', array(
                        'class' => 'label',
                        'tag' => 'div',
                )),
                array('HtmlTag', array(
                        'tag' => 'div',
                        'class' => 'element',
                )),
            ),
        ));
        $this->addElement('password', 'password', array(
            'required' => true,
            'label' => '密码:',
            'decorators' => array(
                array('ViewHelper', array('helper' => 'formPassword')),
                array('Label', array(
                        'class' => 'label',
                        'tag' => 'div',
                )),
                array('HtmlTag', array(
                        'tag' => 'div',
                        'class' => 'element',
                )),
            ),
        ));
//        $form->addDisplayGroup(array('username', 'password'), 'Reg');
        $this->addElement('submit', '提交', array(
            'class' => 'button',
            'decorators' => array(
                array('ViewHelper', array('helper' => 'formSubmit')),
                array('HtmlTag', array(
                        'tag' => 'div',
                        'class' => 'submit',
                )),
            ),
        ));
    }

}

?>
