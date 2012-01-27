<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
class SendMsgForm extends Zend_Form {

    public function init() {
        $this->setMethod('post');
         $this->addElement('hidden', 'to_user', array(
            'required' => true,
             'validators' => array(
                'alnum'
            ),
        ));
        $this->addElement('text', 'title', array(
            'required' => true,
            'label' => '标题:',
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
        $this->addElement('textarea', 'msg', array(
            'required' => true,
            'label' => '内容:',
            'class' => 'ckeditor',
            'decorators' => array(
                array('ViewHelper', array('helper' => 'formTextarea')),
                array('Label', array(
                        'class' => 'label',
                        'tag' => 'div',
                )),
                array('HtmlTag', array(
                        'tag' => 'div',
                )),
            ),
        ));
       
//        $form->addDisplayGroup(array('username', 'password'), 'Reg');
        $this->addElement('submit', '发送', array(
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
