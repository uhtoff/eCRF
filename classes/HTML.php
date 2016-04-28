<?php
Class HTML {
    const JQVER = '1.7.2'; 
    const JQUIVER = '1.10.4';
	public static function header( $headArray=NULL, $docRoot = 'https://' ) {
		echo <<<_END
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		
_END;
		if( $headArray ) {
			foreach( $headArray as $key => $value ) {
				switch ( $key ) {
					case "title":
						$title = self::clean( $value );
						echo "<title>{$title}</title>\n";
						break;
					case "css":
						if ( !isset( $headArray['bootstrap'] ) ) { // If bootstrap being used, css needs to come between bootstrap.css and bootstrap-responsive.css
							foreach( (array)$value as $path ) {
								$path = self::clean( $path );
								echo "<link href=\"{$path}\" rel=\"stylesheet\" type=\"text/css\" />\n";
							}
						}
						break;
					case "jquery":
						if ( !isset( $headArray['bootstrap'] ) ) {
                            if ( $value != 1 ) {
                                $version = self::clean($value);
                            } else {
                                $version = self::JQVER;
                            }
                            echo "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/{$version}/jquery.min.js\" type=\"text/javascript\" ></script>\n";
						}
						break;
                    case "jqueryui":
                        if ( !isset( $headArray['bootstrap'] ) ) {
                            if ( $value != 1 ) {
                                $version = self::clean($value);
                            } else {
                                $version = self::JQUIVER;
                            }
                            echo "<link rel=\"stylesheet\" href=\"https://ajax.googleapis.com/ajax/libs/jqueryui/{$version}/themes/smoothness/jquery-ui.css\" />\n
                            <script src=\"https://ajax.googleapis.com/ajax/libs/jqueryui/{$version}/jquery-ui.min.js\"></script>";
						}
						break;
                        
					case "bootstrap":
						echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
						echo "<link rel=\"stylesheet\" href=\"{$docRoot}/css/bootstrap.css\">\n";
						if ( isset( $headArray['css'] ) ) { // Insert custom css here if set, otherwise just pad the top for the navbar
							foreach( (array)$headArray['css'] as $path ) {
								$path = self::clean( $path );
								echo "<link href=\"{$path}\" rel=\"stylesheet\" type=\"text/css\" />\n";
							}
						} else {
							echo "<style type=\"text/css\">body {padding-top: 50px;}</style>\n";
						}
						if ( isset( $headArray['font-awesome'] ) ) {
							echo "<link rel=\"stylesheet\" href=\"{$docRoot}/font-awesome/css/font-awesome.min.css\">
<!--[if IE 7]>
  <link rel=\"stylesheet\" href=\"./font-awesome/css/font-awesome-ie7.min.css\">
<![endif]-->";
						}
						echo "<link rel=\"stylesheet\" href=\"{$docRoot}/css/bootstrap-responsive.css\">\n";
						echo "<!--[if lt IE 9]>
	<script src=\"./js/html5shiv.js\"></script>
	<style>
		.noie{
			display:none;
		}
	</style>
<![endif]-->";
						echo "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js\" type=\"text/javascript\" ></script>\n"; // Bootstrap needs jquery
						echo "<script src=\"{$docRoot}/js/bootstrap.js\" type=\"text/javascript\" ></script>\n";
						break;
                    case "dataTables":
                        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.8.2/css/jquery.dataTables.css\">";
                        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$docRoot}/css/dTablebs.css\">";
                        echo "<script type=\"text/javascript\" charset=\"utf-8\" src=\"https://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.8.2/jquery.dataTables.min.js\"></script>";
//						echo "<script type=\"text/javascript\" charset=\"utf-8\" src=\"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js\"></script>";
//						echo "<script type=\"text/javascript\" charset=\"utf-8\" src=\"https://cdn.datatables.net/plug-ins/1.10.11/sorting/datetime-moment.js\"></script>";
                        echo "<script type=\"text/javascript\" charset=\"utf-8\" src=\"{$docRoot}/js/dTablebs.js\"></script>";
                        break;
					case "script":
						foreach( (array)$value as $path ) {
							$path = self::clean( $path );
							echo "<script src=\"{$path}\" type=\"text/javascript\" ></script>\n";
						}
						break;
                    case "analytics":
                        echo "<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');";
                        echo $value;
                        echo "ga('send', 'pageview');

</script>";
                        break;
				}
			}
		}
		echo <<<_END
		<script type="text/javascript" src="./js/modernizr.js"></script>
		<script type="text/javascript" src="./js/phshiv.js"></script>
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	</head>
	
_END;
	}
	public static function form ( $attribArr ) {
		if ( isset( $attribArr['action'] ) ) {
			echo "<form ";
			foreach( $attribArr as $att => $value ) {
				$att = HTML::clean( $att );
				$value = HTML::clean ( $value );
				echo "{$att} = \"{$value}\" ";
			}
			echo ">\n";
		}
	}
	public static function select( $name, $varArray, $selected = NULL, $zero = NULL, $width = NULL, $display = true ) {
		$name = self::clean( $name );
		$output = '';
		$output .= "<select name=\"{$name}\"";
		if ( $width ) $output .= "class=\"span{$width}\"";
		$output .= ">\n";
		if( $zero ) {
			$output .= "<option value=\"\" ";
			if( $selected === "" ) {
				$output .= "selected = \"selected\"";
			}
			$output .= ">{$zero}</option>\n";
		}
		foreach( $varArray as $value => $text ) {
			$value = self::clean( $value );
			$text = self::clean( $text );
			$output .= "<option value=\"{$value}\" ";
			if( $value == $selected ) {
				$output .= "selected = \"selected\"";
			}
			$output .= ">{$text}</option>\n";
		}
		$output .= "</select>";
		if ( $display ) echo $output;
		return $output;
	}
	public static function ul( $list, $liAttribs=NULL, $ol = false ) {
		$class = "";
		if( $ol ) {
			echo "<ol>\n";
		} else {
			echo "<ul>\n";
		}
		foreach( $list as $value ) {
			if ( is_array( $value ) ) {
				HTML::ul( $value, $liAttribs );
			} elseif ( isset( $liAttribs[$value] ) ) {
				$output = $value;
				if( isset( $liAttribs[ $value ][ "a" ][ "href" ] ) ) {
					$output = "<a href=\"" . $liAttribs[ $value ][ "a" ][ "href" ] . "\">{$output}</a>";
				}
				if( isset( $liAttribs[ $value ][ "image" ][ "src" ] ) ) {
					$image = "<img ";
					foreach( $liAttribs[$value][ "image" ] as $attrib => $v ) {
						$image .= self::clean( $attrib ) . " = \"" . self::clean( $v ) . "\" ";
					}
					$image .= "/>";
					$output .= $image;
				}
				if ( isset( $liAttribs[ $value ][ "class" ] ) ) {
					$class = " class =\"";
					foreach ( $liAttribs[ $value ][ "class" ] as $value ) {
						$class .= HTML::clean( " {$value}" );
					}
					$class .= "\"";
				}
				echo "<li{$class}>{$output}</li>\n";
			} else {
				$output = $value;
				echo "<li>{$output}</li>\n";
			}
		}
		if( $ol ) {
			echo "</ol>\n";
		} else {
			echo "</ul>\n";
		}
	}
	public static function submit( $value, $endForm = 0, $display = true ) {
		$value = self::clean( $value );
		$output = "<br /><input type=\"submit\" name=\"submit\" class=\"submit btn\" value=\"{$value}\" />\n";
		if ( $endForm ) {
			$output .= "</form>\n";
		}
		if ( $display ) echo $output;
		return $output;
	}
	public static function hidden( $name, $value ) {
		$value = self::clean( $value );
		$var = self::clean( $name );
		echo "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />\n";
	}
	public static function br() {
		echo "<br/>\n";
	}
	public static function clean( $dirty ) {
		$clean = htmlentities( $dirty, ENT_QUOTES, "UTF-8" );
		return $clean;
	}
	public static function wrap( $tag, $text ) {
		// Should have array of valid tags really...
		echo "<{$tag}>";
		echo HTML::clean( $text );
		echo "</{$tag}>\n";
	}
}
?>