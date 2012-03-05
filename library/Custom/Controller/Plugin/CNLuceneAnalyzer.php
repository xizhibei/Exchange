<?php

/* * *****************************************************************************
  Author:XuZhipei <xuzhipei@gmail.com>
  Date:  2012/2/3
 * ***************************************************************************** */

class Custom_Controller_Plugin_CNLuceneAnalyzer extends Zend_Search_Lucene_Analysis_Analyzer_Common {

    private$_position;
    private$_cnStopWords = array();

    public function setCnStopWords($cnStopWords) {
        $this->_cnStopWords = $cnStopWords;
    }

    /**
     * Reset token stream
     */
    public function reset() {
        $this->_position = 0;
        $search = array(",", "/", "\\", ".", ";", ":", "\"", "!", "~", "`", "^", "(",
            ")", "?", "-", "t", "n", "'", "<", ">", "r", "rn", "$", "&", "%", "#", "@", "+",
            "=", "{", "}", "[", "]", "：", "）", "（", "．", "。", "，", "！", "；", "“",
            "”", "‘", "’", "［", "］", "、", "—", "　", "《", "》", "－", "…", "【", "】",);
        $this->_input = str_replace($search, ' ', $this->_input);
        $this->_input = str_replace($this->_cnStopWords, ' ', $this->_input);
    }

    /**
     * Tokenization stream API
     * Get next token
     * Returns null at the end of stream
     *
     * @return Zend_Search_Lucene_Analysis_Token|null
     */
    public function nextToken() {
        if ($this->_input === null) {
            returnnull;
        }
        while ($this->_position < strlen(utf8_decode($this->_input))) {
            while ($this->_position < strlen(utf8_decode($this->_input)) &&
            $this->_input[$this->_position] == ' ') {
                $this->_position++;
            }
            $termStartPosition = $this->_position;
            $temp_char = $this->_input[$this->_position];
            $isCnWord = false;
            if (ord($temp_char) > 127) {
                $i = 0;
                while ($this->_position < strlen(utf8_decode($this->_input)) &&
                ord($this->_input[$this->_position]) > 127) {
                    $this->_position = $this->_position + 3;
                    $i++;
                    if ($i == 2) {
                        $isCnWord = true;
                        break;
                    }
                }
                if ($i == 1)
                    continue;
            }else {
                while ($this->_position < strlen(utf8_decode($this->_input)) &&
                ctype_alnum($this->_input[$this->_position])) {
                    $this->_position++;
                }
            }
            if ($this->_position == $termStartPosition) {
                return null;
            }

            $token = new Zend_Search_Lucene_Analysis_Token(
                    substr($this->_input, $termStartPosition, $this->_position - $termStartPosition), $termStartPosition, $this->_position);
            $token = $this->normalize($token);
            if ($isCnWord)
                $this->_position = $this->_position - 3;
            if ($token !== null) {
                return $token;
            }
        }
        return null;
    }

}

?> 