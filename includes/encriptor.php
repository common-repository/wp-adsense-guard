<?php 	
     /*
     *     Source : http://www.phpclasses.org/browse/file/22638.html
     */
# class.encryption.inc
if(! class_exists( 'Encriptor' ) ){
    class Encriptor
    {
        // The key
        private $_key;
    
        // Generates the key
        public function __construct($key = '', $md5 = true)
        {
            $key = str_split($md5 ? md5($key) : sha1($key), 1);
            $signal = false;
            $sum = 0;
    
            foreach($key as $char)
            {
                if($signal)
                {
                    $sum -= ord($char);
                    $signal = false;
                }
                else
                {
                    $sum += ord($char);
                    $signal = true;
                }
            }
    
            $this->_key = abs($sum);
        }
    
        // Encrypt
        public function encrypt($text)
        {
            $text = str_split($text, 1);
            $final = '';
    
            foreach($text as $char)
            {
                $final .= sprintf("%03x", ord($char) + $this->_key);
            }
    
            return $final;
        }
    
        // Decrypt
        public function decrypt($text)
        {
            $final = '';
            $text = str_split($text, 3);
    
            foreach($text as $char)
            {
                $final .= chr(hexdec($char) - $this->_key);
            }
    
            return $final;
        }
    } 
}
?>