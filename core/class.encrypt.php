<?php

class Encrypt {

    function __construct() {

    }

    public static function GetCyphering() {
        // Store the cipher method
        $ciphering = "AES-128-CTR";

        return $ciphering;
    }

    public static function GetIVLength() {
        $ciphering = self::GetCyphering();

        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);

        return $iv_length;
    }


    public static function GetOptions() {
        $options = 0;

        return $options;
    }

    public static function GetIV() {
        $iv = '1234567891011121';

        return $iv;
    }

    public static function GetEncryptionKey() {
        $encryption_key = 'as404309fd;';

        return $encryption_key;
    }

    public static function encrypt($pure_string, $encryption_key) {
        $ciphering = self::GetCyphering();
        $options = self::GetOptions();
        $encryption_iv = self::GetIV();


        $encryption = openssl_encrypt($pure_string, $ciphering,
                                      $encryption_key, $options, $encryption_iv);


        return $encryption;
    }


    public static function decrypt($encrypted_string, $encryption_key) {
        $ciphering = self::GetCyphering();
        $options = self::GetOptions();
        $encryption_iv = self::GetIV();

        $decryption = openssl_decrypt ($encrypted_string, $ciphering,
                                       $encryption_key, $options, $encryption_iv);

        return $decryption;
    }
}
