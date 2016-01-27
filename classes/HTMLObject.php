<?php
class HTMLObject {
	protected $classes = array();
	protected $tags = array();
	protected $id;
	protected function clean( $dirty ) {
		$clean = htmlentities( $dirty, ENT_QUOTES, "UTF-8" );
		return $clean;
	}
	public function addClass( $class, $propagate = false ) { // Accepts an array or a single entry
		if ( is_array( $class ) ) {
			foreach ( $class as $c ) {
				$this->classes[] = $c;
			}
		} else {
			$this->classes[] = $class;
		}
	}
	public function hasClass( $class ) { // Returns the key of the class if exists or false if not (thus needs ===)
		$hasClass = array_search( $class, $this->classes );
		return $hasClass;
	}
	public function removeClass( $class ) { // Returns true if it removed a class and false if it didn't
		$index = array_search( $class, $this->classes );
		if ( $index === false ) {
			$removeClass = false;
		} else {
			unset( $this->classes[ $index ] );
			$removeClass = true;
		}
		return $removeClass;
	}
	public function writeClasses() {
		if ( $this->classes ) {
			$html = "class=\"";
			foreach ( $this->classes as $c ) {
				$html .= "{$this->clean( $c )} ";
			}
			$html .= "\"";
			return $html;
		}
	}
	public function addID( $id ) {
		$this->id = $id;
	}
	public function writeID() {
		if ( $this->id ) {
			$html = "id=\"{$this->clean( $this->id )}\"";
			return $html;
		}
	}
}
?>
