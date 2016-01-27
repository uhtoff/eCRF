<?php
//  Public functions - setMode, setAlgo, setKey, SetIv to setup encryption algo
//  Standard use - create new Encrypt passing key to use
//  Encrypt::encrypt( $plaintext ) returns ciphertext
//  Encrypt::decrypt( $ciphertext ) returns plaintext
//  Note
//
Class Encrypt { // Wrapper for mcrypt to auto-initialise etc.
	private $algo = 'rijndael-256';
	private $mode = 'ctr';
	private $iv, $key, $td;
    public function __construct( $key ) {
		$this->setKey($key);
		$this->open();
	}
    public static function hashPassword( $password, $cost = 12 ) {
        $salt = '';
        if ( version_compare( phpversion(), '5.3.7', 'gt' ) ) {
            // Use Blowfish prefix $2y$ if version better than 5.3.7
            $salt .= '$2y$';
        } else {
            // Otherwise use $2a$
            $salt .= '$2a$';
        }
        // Then add cost
        $salt .= "{$cost}$";
        // Then generate salt
        $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';
        $charLen = 63;
        $saltLength = 22;
        for($i=0; $i<$saltLength; $i++)
        {
            $salt .= $allowedChars[mt_rand(0,$charLen)];
        }
        $hash = base64_encode( crypt($password,$salt) );
        return $hash;
    }
    public static function checkPassword( $password, $hash ) {
        // Decode hash
        $hash = base64_decode($hash);
        // Return boolean password check
        return $hash == crypt($password, $hash );
    }
	public function setAlgo( $algo ) {
		$algorithms = mcrypt_list_algorithms("/usr/local/lib/libmcrypt");
		if( in_array( $algo, $algorithms ) ) {
			$this->algo = $algo;
		}
	}
	public function setMode( $mode ) {
		$modes = mcrypt_list_modes("/usr/local/lib/libmcrypt");
		if( in_array( $mode, $modes ) ) {
			$this->mode = $mode;
		}
	}
	private function setKey( $key ) {
		$this->key = hash('sha256',$key,TRUE);
	}
	private function setIv( $iv ) {
		$this->iv = $iv;
	}
	private function open() {
		$this->td = mcrypt_module_open( $this->algo, "", $this->mode, "" );
	}
	private function init() {
		mcrypt_generic_init( $this->td, $this->key, $this->iv );
	}
	private function close() {
		mcrypt_generic_deinit( $this->td );
		mcrypt_module_close( $this->td );
	}

	public function encrypt( $plaintext ) {
		if ( !empty( $plaintext ) ) {
			if( !isset( $this->td ) ) $this->open(); // only open if not done already
            $iv_size = mcrypt_get_iv_size($this->algo, $this->mode);
            $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
            $this->setIv( $iv );
			$this->init();
			$ciphertext = mcrypt_generic( $this->td, $plaintext );
			return base64_encode($iv . $ciphertext);
		}
	}
	public function decrypt( $ciphertext ) {
		if ( !empty( $ciphertext ) ) {
			if( !isset( $this->td ) ) $this->open(); // only open if not done already
            $ciphertext = base64_decode($ciphertext);
            $iv_size = mcrypt_get_iv_size($this->algo, $this->mode);
            $iv = substr($ciphertext, 0, $iv_size);
            if ( strlen($iv) == $iv_size ) {
                $this->setIv( $iv );
                $this->init();
                $ciphertext = substr($ciphertext, $iv_size);
                $plaintext = mdecrypt_generic( $this->td, $ciphertext );
                return $plaintext;
            }
		}
	}
	public function __destruct() { // tidy up when done
		if ( isset( $this->td ) ) $this->close();
	}
}
?>