<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
class UserModifyForm extends Zend_Form {

    public function init() {
        $this->setMethod('post');
        $this->setAttrib("class", "normal_form");
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div')),
            'Form'
        ));
        $this->addElement('text', 'username', array(
            'validators' => array(
                'alnum',
                array('regex', false, '/^[a-z]/i')
            ),
            'required' => true,
            'filters' => array('StringToLower'),
            'label' => '用户名:',
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
        $this->addElement('text', 'qq', array(
            'validators' => array(
                'alnum',
            ),
            'required' => true,
            'filters' => array('StringToLower'),
            'label' => 'QQ:',
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
        $this->addElement('text', 'cellphone', array(
            'validators' => array(
                'alnum',
            ),
            'required' => true,
            'filters' => array('StringToLower'),
            'label' => '手机:',
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
        
        $this->addElement("radio", "sex", array(
            'multiOptions' => array(
                '男' => '男',
                '女' => '女',
            ),
            'label' => '性别:',
            "separator" => ' ',
            'decorators' => array(
                array('ViewHelper', array('helper' => 'formRadio')),
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
        $this->addElement('submit', '更新', array(
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
