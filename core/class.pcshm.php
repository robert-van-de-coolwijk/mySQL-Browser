<?php


class pc_Shm {

    var $tmp;
    var $size;
    var $shm;
    var $keyfile;
    const DefaultRightsNumber = 644;
    const KeySizeKB = 64;

    function pc_Shm($tmp = '') {
        if (!function_exists('shmop_open')) {
            trigger_error('pc_Shm: shmop extension is required.', E_USER_ERROR);
            return;
        }

        if ($tmp != '' && is_dir($tmp) && is_writable($tmp)) {
            $this->tmp = $tmp;
        } else {
            $this->tmp = '/tmp';
        }

        // size in kb
        $this->size = (int)(1024 * pc_Shm::KeySizeKB);
        #echo var_export($this->size, true);

        return true;
    }

//    function __construct($tmp = '') {
//        return $this->pc_Shm($tmp);
//    }

//    private function setSize($size) {
//        if (ctype_digit($size)) {
//            $this->size = $size;
//        }
//    }

    private function open($id) {
        $key = $this->_getKey($id);
        

            $shm = @shmop_open(
                $key, 
                'c', 
                self::DefaultRightsNumber, 
                $this->size
            );
        
        if (!$shm) {
            trigger_error('pc_Shm: could not create shared memory segment', E_USER_ERROR);
            return false;
        }
        
        $this->shm = $shm;
        
        return true;
    }

    private function write($data) {
        $written = shmop_write($this->shm, $data, 0);
        if ($written != strlen($data)) {
            trigger_error('pc_Shm: could not write entire length of data', E_USER_ERROR);
            return false;
        }
        return true;
    }

    
    /**
     * Returns the current cache
     * Warning: returns a string from the size of the opened memory segment, use trim
     * 
     * @return boolean
     */
    private function read() {
        $data = shmop_read($this->shm, 0, $this->size);
        if (!$data) {
            trigger_error('pc_Shm: could not read from shared memory block', E_USER_ERROR);
            return false;
        }
        return $data;
    }

//    private function delete() {
//        if (shmop_delete($this->shm)) {
//            if (file_exists($this->tmp . DIRECTORY_SEPARATOR . $this->keyfile)) {
//                unlink($this->tmp . DIRECTORY_SEPARATOR . $this->keyfile);
//            }
//        }
//        return true;
//    }

    private function close() {
        return shmop_close($this->shm);
    }

    /**
     * get key and close
     * Warning: returns a string from the size of the opened memory segment, use trim
     * 
     * @param type $id
     * @return type
     */
    function fetch($id) {
        $this->open($id);
        
        $data = $this->read();
        $this->close();
        
        return $data;
    }

    /**
     * put key and close
     * 
     * @param type $id
     * @param type $data
     * @return boolean
     */
    function save($id, $data) {
        $this->open($id);
        
        $result = $this->write($data);
        
        if (!(bool) $result) {
            return false;
        } else {
            $this->close();
            return $result;
        }
    }

    private function _getKey($id) {
        $this->keyfile = 'pcshm_' . $id;
        if (!file_exists($this->tmp . DIRECTORY_SEPARATOR . $this->keyfile)) {
            touch($this->tmp . DIRECTORY_SEPARATOR . $this->keyfile);
        }
        return ftok($this->tmp . DIRECTORY_SEPARATOR . $this->keyfile, 'R');
    }

}


/// Dirty HACKS \\\
if( !function_exists('ftok') )
{
    function ftok($filename = "", $proj = "")
    {
        if( empty($filename) || !file_exists($filename) )
        {
            return -1;
        }
        else
        {
            $filename = $filename . (string) $proj;
            for($key = array(); sizeof($key) < strlen($filename); $key[] = ord(substr($filename, sizeof($key), 1)));
            return dechex(array_sum($key));
        }
    }
}