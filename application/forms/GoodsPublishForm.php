<?php
/*******************************************************************************
    Author:XuZhipei <xuzhipei@gmail.com>
    Date:  2012/1/25
*******************************************************************************/
class GoodsPublishForm extends Zend_Form {

    public function init() {
        $this->setMethod('post');
        $this->setAttrib("class", "normal_form");
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div')),
            'Form'
        ));
        $this->addElement('text', 'name', array(
            'required' => true,
            'label' => '名称:',
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
        $this->addElement('text', 'ex_cond', array(
            'label' => '交换条件:',
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

        $this->addElement('text', 'price', array(
            'validators' => array(
                'alnum',
                array('regex', false, '/^(\d+)|(\d+.\d+)/i')
            ),
            'label' => '价格:',
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
        $this->addElement('text', 'pic', array(
            'label' => '图片:',
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
        $this->addElement('textarea', 'detail', array(
            'required' => true,
            'label' => '详细描述:',
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

        $this->addElement('radio', 'sale_ways', array(
            'required' => true,
            'label' => '交易方式:',
            'multiOptions' => array(
                '交易' => '交易',
                '交换' => '交换',
                '均可' => '均可',
            ),
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

        $this->addElement('submit', '发布', array(
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
