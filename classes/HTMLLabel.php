<?php
class HTMLLabel extends HTMLObject {
	protected $text;
	protected $forVal;
	public function __construct( $text, $forVal ) {
		$this->addText( $text );
		$this->addFor( $forVal );
	}
	public function addText( $text ) {
		$this->text = $text;
	}
	public function addFor( $forVal ) {
		$this->forVal = $forVal;
	}
	public function writeHTML() {
		$html = "<label for=\"{$this->clean( $this->forVal )}\" {$this->writeClasses()} >";
		$html .= $this->clean( $this->text );
		$html .= "</label>";
		return $html;
	}
}
?>
