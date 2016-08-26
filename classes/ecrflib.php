<?php
function random_int($min, $max)
    {
        /**
         * Type and input logic checks
         */
        if (!is_numeric($min)) {
            throw new TypeError(
                'random_int(): $min must be an integer'
            );
        }
        if (!is_numeric($max)) {
            throw new TypeError(
                'random_int(): $max must be an integer'
            );
        }
        $min = (int) $min;
        $max = (int) $max;
        if ($min > $max) {
            throw new Error(
                'Minimum value must be less than or equal to the maximum value'
            );
        }
        if ($max === $min) {
            return $min;
        }
        /**
         * Initialize variables to 0
         *
         * We want to store:
         * $bytes => the number of random bytes we need
         * $mask => an integer bitmask (for use with the &) operator
         *          so we can minimize the number of discards
         */
        $attempts = $bits = $bytes = $mask = $valueShift = 0;
        /**
         * At this point, $range is a positive number greater than 0. It might
         * overflow, however, if $max - $min > PHP_INT_MAX. PHP will cast it to
         * a float and we will lose some precision.
         */
        $range = $max - $min;
        /**
         * Test for integer overflow:
         */
        if (!is_int($range)) {
            /**
             * Still safely calculate wider ranges.
             * Provided by @CodesInChaos, @oittaa
             *
             * @ref https://gist.github.com/CodesInChaos/03f9ea0b58e8b2b8d435
             *
             * We use ~0 as a mask in this case because it generates all 1s
             *
             * @ref https://eval.in/400356 (32-bit)
             * @ref http://3v4l.org/XX9r5  (64-bit)
             */
            $bytes = PHP_INT_SIZE;
            $mask = ~0;
        } else {
            /**
             * $bits is effectively ceil(log($range, 2)) without dealing with
             * type juggling
             */
            while ($range > 0) {
                if ($bits % 8 === 0) {
                    ++$bytes;
                }
                ++$bits;
                $range >>= 1;
                $mask = $mask << 1 | 1;
            }
            $valueShift = $min;
        }
        /**
         * Now that we have our parameters set up, let's begin generating
         * random integers until one falls between $min and $max
         */
        do {
            /**
             * The rejection probability is at most 0.5, so this corresponds
             * to a failure probability of 2^-128 for a working RNG
             */
            if ($attempts > 128) {
                throw new Exception(
                    'random_int: RNG is broken - too many rejections'
                );
            }

            /**
             * Let's grab the necessary number of random bytes
             */
            $randomByteString = mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
            if ($randomByteString === false) {
                throw new Exception(
                    'Random number generator failure'
                );
            }
            /**
             * Let's turn $randomByteString into an integer
             *
             * This uses bitwise operators (<< and |) to build an integer
             * out of the values extracted from ord()
             *
             * Example: [9F] | [6D] | [32] | [0C] =>
             *   159 + 27904 + 3276800 + 201326592 =>
             *   204631455
             */
            $val = 0;
            for ($i = 0; $i < $bytes; ++$i) {
                $val |= ord($randomByteString[$i]) << ($i * 8);
            }
            /**
             * Apply mask
             */
            $val &= $mask;
            $val += $valueShift;
            ++$attempts;
            /**
             * If $val overflows to a floating point number,
             * ... or is larger than $max,
             * ... or smaller than $int,
             * then try again.
             */
        } while (!is_int($val) || $val > $max || $val < $min);
        return (int) $val;
    }

function emptyInput( $value ) { // As '0' is a valid form entry, then have to be more specific about what is empty...
	$emptyInput = true;
	if ( is_array( $value ) ) {
		foreach( $value as $k => $v ) {
			if ( $k !== 'unit' && !emptyInput( $v ) ) { // If just a unit field is set it's still empty
				$emptyInput = false;
			}
		}
	} else {
		if ( is_null($value) || $value === "" ) {
			$emptyInput = true;
		} else {
			$emptyInput = false;
		}
	}
	return $emptyInput;
}

function splitDateTime( $datetime ) {
    $dt = array();
    $split = explode(' ',$datetime);
    $dt['date'] = $split[0];
    $dt['time'] = $split[1];
    return $dt;
}

function convertDate( $date ) {
    $d = NULL;
    $split = explode('-',$date);
    if ( isset($split[2]) ) {
        $d = "{$split[2]}/{$split[1]}/{$split[0]}";
    } else {
        $split = explode('/',$date);
		if ( isset($split[2]) ) {
			$d = "{$split[2]}-{$split[1]}-{$split[0]}";
		}
		else $d = $date;
    }
    return $d;
}

//if ( $_SERVER['SERVER_NAME'] != '127.0.0.1' 
//        && $_SERVER['SERVER_NAME'] != 'localhost' 
//        && $_SERVER['HTTPS'] != "on" ) { 
//    // Ensure that the user is redirected to the https site
//    // Will do via .htaccess too (belt and braces)
//    $url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
//    header("Location: $url");
//    exit;
//}

session_name( "trial" );

class Trial { // Object for all things just related to the trial/site (writing headers/sidebars, handling pages, getting form fields)
	protected $language = 'en';
    /**
     * @var Record
     */
    public $record;
    /**
     * @var eCRFUser
     */
    public $user;
	public function __construct( $page ) {
		$this->expireUsers();
		$this->setPage($page);
        $sql = "SELECT *, TIME_TO_SEC( TIMEDIFF( timeoff, NOW() ) ) as timeleft FROM ecrf";
        $result = DB::query($sql);
        if ( $result->getRows() ) {         
            $this->offline = $result->offline;
            $this->title = $result->title;
            $this->welcomeMessage = $result->welcomeMessage;
            $this->timeoff = $result->timeoff;
            $this->timeleft = $result->timeleft;
            $this->setTest( $result->test );
            $this->email['Host'] = $result->emailHost;
            $this->email['Port'] = $result->emailPort;
            $this->email['Address']  = $result->emailAddress;
            $this->email['User'] = $result->emailUser;
            $this->email['Password'] = $result->emailPassword;
            $this->email['Name'] = $result->emailName;
            $this->email['SMTP'] = $result->emailSMTP;
            $this->about = $result->about;
        }
	}
    public function setTest( $test ) {
        $this->test = $test;
    }
    public function setPage( $page ) {
        $subPage = false;
        if ( $page ) {
            $sql = "SELECT subPage, firstSub FROM pages WHERE name = ?";
            $pA = array('s',$page);
            $result = DB::query( $sql, $pA );
            if ( $result->getRows() ) {
                if ( $result->subPage ) {
                    $this->setParentPage( $result->subPage );
                    $subPage = true;
                }
                if ( $result->firstSub ) {
                    $this->setParentPage( $page );
                    $page = $result->firstSub;
                    $subPage = true;
                }
            }
        }
        $this->page = $page;
        $this->setSubPage( $subPage );
    }
    public function setSubPage( $subpage ) {
        $this->subPage = $subpage;
    }
    public function setParentPage( $page ) {
        $this->parentPage = $page;
    }
	public function addUser( eCRFUser $user ) { // Before adding to trial, check is logged on and renew or log them out via refreshUser method
		$loggedIn = $user->refreshUser();
		$this->language = $user->getLanguage();
		if ( $loggedIn ) {
			$this->user = $user;
		}
		return $loggedIn;
	}
	public function addRecord( $record_id = NULL ) {
		if ( isset($this->user) && $this->user->isLinked() ) {
			$this->record = new Record( $this->user->isLinked() );
		} else if ( $record_id ) {
            $this->record = new Record( $record_id );
        } else {
			$this->record = new Record();
		}	
		if ( isset ( $this->record ) && isset( $this->user ) ) {
			$this->record->addUser( $this->user );
		}
		return isset( $this->user ) ? $this->user->isLinked() : NULL;
	}

    /**
     * @return Record
     */
    public function getRecord() {
        return $this->record;
    }

    /**
     * Get array of all records from the database
     * @param bool $includeDiscontinued
     * @return Record[]
     */
    public function getAllRecords($includeDiscontinued=false)
    {
        $sql = "SELECT id FROM link";
        if (!$includeDiscontinued) {
            $sql .= " WHERE discontinue_id IS NULL";
        }
        $linkIDs = DB::query($sql);
        $records = array();
        foreach ($linkIDs->rows as $linkID) {
            $records[$linkID->id] = new Record($linkID->id);
        }
        return $records;
    }
    /*
     * return eCRFUser
     */
	public function getUser() {
		$getUser = false;
		if ( isset( $this->user ) && $this->user instanceof User ) {
			$getUser = $this->user;
		}
		return $getUser;
	}
    public function getUserCentre() {
        $return = false;
        $user = $this->getUser();        
        if ( $user ) {
            $return = $user->getCentre();
        }
        return $return;
    }
    public function getTrialID() {
        return $this->record->getData('core')->get('trialid');
    }
    public function generateTrialID($centre = NULL) {
		if ( !$centre ) {
			$centre = $this->getUser()->getCentre();
		}
		$centre = str_pad($centre,3,'0',STR_PAD_LEFT);
		do {
			$id = str_pad(random_int(1,9999),4,'0',STR_PAD_LEFT);
			$sql = "SELECT count(id) as numID FROM core WHERE trialid LIKE ?";
			$pA = array('s',"%{$id}");
			$idCheck = DB::query($sql,$pA);
		} while ( $idCheck->rows[0]->numID > 0 );
		return "{$centre}-{$id}";
    }
	public function expireUsers( $time = 20 ) { // Searches user database for users to expire
		$sql = "SELECT id FROM user WHERE TIMESTAMPDIFF(MINUTE, lastactive, now() ) > {$time} AND loggedin=1";
		$result = DB::query( $sql );
        foreach( $result->rows as $row ) {
            $user = new eCRFUser( $row->id );
            $user->logout( false );
        }	
	}
	public function getPage() {
		return $this->page;
	}
    public function getSubPage() {
        if ( isset( $this->subPage ) ) {
            return $this->subPage;
        } else return NULL;
    }
    public function getParentPage() {
        if ( isset( $this->parentPage ) ) {
            return $this->parentPage;
        } else return NULL;
    }

    /**
     * @TODO: Just give recaptcha script on registration page
     */
	function writeHead($page = NULL, $docRoot = '.') {
		$headArray = array( "title" => $this->getTitle(),
					"css" => "{$docRoot}/css/ecrf.css",
					"jquery" => 1,
					"bootstrap" => 1,
					"font-awesome" => 1,
                    "dataTables" => 1,
					"script" => array( "{$docRoot}/js/ecrf.js", ("{$docRoot}/js/" . Config::get('trial') . ".js"), 'https://www.google.com/recaptcha/api.js' ),
                    "analytics" => "ga('create', 'UA-43098186-1', 'isos.org.uk');" );
		HTML::header( $headArray, $docRoot );
        if( $this->timeleft ) {
            if ( $this->timeleft <= 0 ) {
                $sql = "UPDATE ecrf SET offline = 1, timeoff = NULL";
                DB::query( $sql );
                echo "<script type = \"text/javascript\">jQuery( function(){alert('System has now shut down, you have been logged off.')} );</script>";
            } else {
                echo "<script type = \"text/javascript\">jQuery( function(){alert('System shutting down in {$this->timeleft} seconds, please save your work.')} );</script>";
            }
		}
	}
	function writeNavBar() {
		echo "<div class=\"navbar navbar-fixed-top\">";
		echo "<div class=\"navbar-inner\">";
		echo '<div class="container">';
        $loggedin = 0; 
		if ( isset( $this->user ) ) {
			$loggedin = $this->user->isLoggedIn();
			$privilege = $this->user->getPrivilege();
		} else {
			$loggedin = 0;
			$privilege = 99;
		}
        $sql = "SELECT pages.id as id, name, IFNULL( pages_labels.label_text, pages.label ) as label_text, pageOrder, dataName FROM pages 
			LEFT JOIN pages_labels ON pages.id = pages_id AND language_code = '{$this->language}'
			WHERE type = 'nav' AND privilege_id >= ? AND active = 1 ";
		if ( $loggedin ) {
			$sql .= "AND privilege_id != 100 "; // Don't include pages only available when not logged in
		}
		$sql .= "ORDER BY pageOrder";
		$pA = array( 'i', $privilege );
        $result = DB::query( $sql, $pA );
        $dropDown = array();
        $nav = array();
        foreach( $result->rows as $row ) { // Create list item from result
            $showPage = $this->parseBranches( $row->id );
            if ( $showPage ) {
                $html = "\t<li";
                if ( $this->page == $row->name ) {
                    $html .= " class=\"active\"";
                }
                $html .= ">\n\t\t<a href=\"index.php?page=";
                $html .= HTML::clean( $row->name );
                $html .= "\">";
				$html .= HTML::clean( $row->label_text );
                $html .= "</a>\n\t</li>\n";
                if ( !is_null( $row->dataName ) ) { // dataName is the label for a drop down, if so then put in dropdown array
                    $dropDown[ $row->dataName ][ $row->pageOrder ] = $html;
                } else { // else in nav array
                    $nav[ $row->pageOrder ] = $html;
                }
            }
        }
        // Create dropdown menu and add to nav bar
        foreach( $dropDown as $label => $liArr ) {
            $html = '';
            if ( $label != 'user' ) {
                $html .= "\t<li class=\"dropdown\">\n";
                $html .= "\t\t<a href=\"#\" class=\"dropdown-toggle nocheck\" data-toggle=\"dropdown\">\n";
                $html .= $label;
                $html .= "\n\t\t\t<b class=\"caret\"></b>\n";
                $html .= "\t\t</a>\n";
                $html .= "\t\t<ul class=\"dropdown-menu\">\n";
            }
            $order = NULL;
            foreach ( $liArr as $o => $li ) {
                if ( !$order ) {
                    $order = $o;
                }
                $html .= $li;
            }
            if ( $label != 'user' ) {
                $html .= "\t\t</ul>\n";
                $nav[ $order ] = $html;
            } else {
                $userLi[] = $html;
            }
        }
        ksort( $nav ); // Sort to put in correct order (mainly to get dropdowns in right place)
        if ( $loggedin == 1 ) {
			echo "<ul class=\"nav pull-right\">";
			echo "<li class=\"dropdown\">";
			echo "<a href=\"#\" class=\"dropdown-toggle nocheck\" data-toggle=\"dropdown\">";
			echo "<i class=\"icon-user\"></i> {$this->user}";
			echo "<b class=\"caret\"></b>";
			echo "</a>";
			if ( isset( $userLi ) ) {
				echo "<ul class=\"dropdown-menu\">";
				foreach ( $userLi as $li ) {
					echo $li;
				}
				echo "</ul>";
			}
			echo "</li>";
			echo "</ul>";
		}
		else {
            $_SESSION['csrfToken'] = $token = base64_encode( openssl_random_pseudo_bytes(32));
			echo <<<_END
				<form class="pull-right form-inline nomargin nomand" action="login.php" method="post">
				<input type="text" name="username" class="input-small" placeholder="Username"/>
				<input type="password" name="password" class="input-small" placeholder="Password"/>
				<input type="hidden" name="csrfToken" value="{$token}"/>
                <button type="submit" class="btn nocheck">Sign in</button>
				</form>
_END;
		}
        echo <<<_END
        <a class="btn btn-navbar" style="float:left;" data-toggle="collapse" data-target=".nav-collapse">
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </a>
_END;
		
        echo "<a class=\"brand\" href=\"index.php\"><img class=\"noie\" src=\"favicon-32x32.png\" />{$this->getTitle()}</a>";
       
		
        echo "<div class=\"nav-collapse\">";
		echo "<ul class=\"nav\">\n";

        
        foreach ( $nav as $html ) {
            echo $html;
        }
		echo '</ul>';
        echo "</div>";


		echo "</div>";
		echo "</div>";
		echo "</div>";
	}
    public function parseBranches( $id, $record_id = NULL ) {
        $showPage = true;
        if ( !isset( $this->branchesResult ) ) {
            $sql = "SELECT * FROM pagesBranch";
            $result = DB::query( $sql );
            foreach( $result->rows as $row ) {
                $this->branchesResult[$row->pages_id][] = $row;
            }
        }
        if ( isset( $this->branchesResult[$id] ) ) {
            $branches = $this->branchesResult[$id];
            $property = false;
            foreach( $branches as $branch ) {
                switch ( $branch->object ) {
                    case 'Trial':
                        $property = $this->{$branch->property};
                        break;
                    case 'Centre':
                        if ( isset( $this->userCentre ) ) {
                            $object = $this->userCentre;
                            $property = $object->{$branch->property};
                        } else {
                            $centreID = $this->getUserCentre();
                            if ( $centreID ) {
                                $object = $this->userCentre = new Centre ( $centreID );
                                $property = $object->{$branch->property};
                            }
                        }
                        break;
                    default:
                        if ( $record_id ) {
                            $this->addRecord( $record_id );
                        }
                        $property = $this->record->getField($branch->object,$branch->property);
                        break;
                }
                if ( $property !== false ) {
					
                    if ( $branch->operand == 'NOT' ) {
						if ( $property == $branch->value ) {
							$showPage = false;
						}
                    } elseif ( $property != $branch->value ) {
                        $showPage = false;
                    }
                }
            }
        }
        return $showPage;
    }
	public function writeDataNav() {
        $sql = "SELECT pages.id as id, name, IFNULL( label_text, pages.label ) as label_text, firstSub FROM pages
			LEFT JOIN pages_labels ON pages_id = pages.id AND language_code = '{$this->language}'
            WHERE type='data' AND subPage IS NULL AND active = 1  
            ORDER by pageOrder";
        $result = DB::query( $sql );
		if ( $result->getRows() ) {
			echo "<ul class=\"nav nav-tabs\">\n";
			foreach( $result->rows as $row ) {
                $showPage = $this->parseBranches( $row->id );
                if ( $showPage ) {
                    $name = HTML::clean($row->name);
					$label = HTML::clean( $row->label_text );
                    echo "\t<li";
                    if ( $this->page == $row->name || $this->getParentPage() == $row->name ) {
                        echo " class=\"active\"";
                    }
                    if ( $row->firstSub ) {
                        $name = $row->firstSub;
                    }
                    echo ">\n\t\t<a href=\"dataentry.php?page={$name}";
                    echo "\">{$label}</a></li>\n";
                }
			}
			echo "</ul>\n";
		}
	}
    public function writeSubDataNav() {
        $sql = "SELECT id, name, label FROM pages 
                WHERE type='data' AND subPage = ? AND active = 1 
                ORDER BY pageOrder";
        $pA = array('s',$this->getParentPage());
        $result = DB::query($sql,$pA);
		if ( $result->getRows() ) {
			echo "<ul class=\"nav nav-tabs\">\n";
			foreach( $result->rows as $row ) {
                $showPage = $this->parseBranches( $row->id );
                if ( $showPage ) {
                    $name = HTML::clean($row->name);
                    $label = HTML::clean($row->label);
                    echo "\t<li";
                    if ( $this->getPage() == $row->name ) {
                        echo " class=\"active\"";
                    }
                    echo ">\n\t\t<a href=\"dataentry.php?page={$name}";
                    echo "\">{$label}</a></li>\n";
                }
			}
			echo "</ul>\n";
		}
    }
	public function writeCore() {
		$core = $this->record->getData( 'core' );
		echo "<div class=\"row\">";
 //       echo "<div class=\"span1\">&nbsp;</div>";
		echo "<div class=\"span6\">";
		echo "<strong class=\"text-centre\">Centre: ";
        echo $core->get('centre_name');
        echo "</strong>";
		echo "</div>";
		echo "<div class=\"span6\">";
		echo "<strong>" . Config::get('idName') . ": {$this->getTrialID()}</strong>";
		echo "</div>";
		echo "</div>";
		echo "<div class=\"row\">";
		echo "&nbsp;";
		echo "</div>";
	}
	function writeFooter() {
        $sql = "SELECT COUNT(id) as numUsers FROM user WHERE loggedin = 1";
        $userQ = DB::query($sql);
        $numUsers = $userQ->numUsers;
         $uP = $numUsers == 1 ? '' : 's';
         $isAre = $numUsers == 1 ? 'is' : 'are';
	echo <<<_END
<div id="footer">
<div class="container">
        <p class="muted credit pull-left">There {$isAre} {$numUsers} user{$uP} currently logged in</p>
<p class="muted credit pull-right">Developed by Dr Russ Hewson for Queen Mary University London</p>
</div>
</div>
</body>
</html>
_END;
	}
	public function checkPageLogin( $page ) { // Takes requested page, ensures it exists and ensures privilege to access it
		$checkPage = NULL;
        // If database offline then auto logout
        if( $this->isOffline() && $this->getUser() && $this->user->getPrivilege() != 1 ) { 
            $page = 'logout';
        }
		if ( $page == 'logout' ) { // Special case for logout
			if ( $this->getUser() ) {
				$this->user->logout();
				unset( $this->user );
				$checkPage = $page;
			}
		} else {
			$sql = "SELECT id, privilege_id FROM pages WHERE name = ? AND active = 1"; // Get required privilege for page
			$pA = array( 's', $page );
            $result = DB::query( $sql, $pA );
			if ( $result->getRows() ) {
				$rp = $result->privilege_id;
				if ( isset( $this->user ) ) {
					if ( $rp == 100 ) { // 100 privilege pages only available when not logged on
						$checkPage = NULL;
					} else {
						if ( $this->user->getPrivilege() <= $rp ) {
							$checkPage = $page;
						}
					}
				} else {
					if ( $rp == 100 ) {
						$checkPage = $page;
					}
				}
                $showPage = $this->parseBranches( $result->id );
                if ( !$showPage ) {
                    $checkPage = NULL;
                }
			}
		}
		return $checkPage;
	}
	public function getNextPage( $page = NULL ) {
        if ( !$page ) {
            $page = $this->getPage();
        }
        $getNextPage = false;
		$sql = "SELECT b.id as id, b.name as name, b.firstSub AS firstSub FROM pages a 
			LEFT JOIN pages b 
			ON a.pageOrder < b.pageOrder
			WHERE b.type = 'data' 
			AND b.dataName = 'Record' 
			AND a.name = ? 
            AND b.active = 1
            ORDER BY b.pageOrder 
			LIMIT 0,1";
		$pA = array( 's', $page );
        $result = DB::query( $sql, $pA );
		if ( $result->getRows() ) {
            if ( !$this->parseBranches($result->id) || $this->checkComplete($result->name) ) {
                // If the next page isn't supposed to be shown or is complete then recurse
                $getNextPage = $this->getNextPage( $result->name );
            } elseif ( $result->firstSub ) {
                $getNextPage = $result->firstSub;
            } else {
                $getNextPage = $result->name;
            }
		}
        return $getNextPage;
	}
    protected function getFormLanguage()
    {
        if ($this->getRecord()) {
            return $this->getRecord()->getLanguage();
        } elseif ($this->getUser()) {
            return $this->getUser()->getLanguage();
        } else {
            return 'en';
        }
    }
	public function getFormFields( $page = NULL, $multiple = false, $multiSuffix = NULL, $record = NULL ) {
		if ( !$page ) {
            $page = $this->getPage();
        }
        $fields = array();
        if ( $multiple ) {
            if (!isset($this->multipleFormFields)) {
                $sql = "SELECT id, labelText, fieldName, defaultVal,
				  	type, toggle, mandatory, multiple, size, class 		 
				  FROM formFields  
				  WHERE pages_name=?  
                  AND multiple = ?
				  ORDER BY entryorder";
                $pA = array('ss', $page, $multiple);
                $result = $this->multipleFormFields = DB::query($sql, $pA);
            } else {
                $result = $this->multipleFormFields;
            }
        } else {
            if (!isset($this->formFields)) {
                $sql = "SELECT formFields.id, IFNULL( label_text, formFields.labelText ) as label_text, fieldName, defaultVal,
					type, toggle, mandatory, size, class, readonly		 
				FROM formFields
				LEFT JOIN formFields_labels
				ON formFields.id = formFields_id AND language_code = '{$this->getFormLanguage()}' 
				WHERE pages_name=? 
                AND multiple IS NULL			
				ORDER BY entryorder";
                $pA = array('s', $page);
                $result = $this->formFields = DB::query($sql, $pA);
            } else {
                $result = $this->formFields;
            }
        }
        $excluded = $this->getExcludedFormFields($record);
        $counter = 1;
        foreach( $result->rows as $row ) {
            if (in_array($row->id,$excluded)) {
                continue;
            }
            if ( !$row->fieldName ) {
                $row->fieldName = $counter++;
            }
            if ( $row->type != 'data' ) {
                $name = "{$page}-{$row->fieldName}"; // Prepends the name with the current page
            } else {
                $name = $row->fieldName;
            }
            if ( $multiSuffix ) {
                $name .= "_{$multiSuffix}";
            }
            $fields[ $name ][ 'type' ] = $row->type;
			$fields[ $name ][ 'label' ] = $row->label_text;
            $fields[ $name ][ 'toggle' ] = $row->toggle;
            $fields[ $name ][ 'mandatory' ] = $row->mandatory;
            $fields[ $name ][ 'default' ] = $row->defaultVal;
            $fields[ $name ][ 'size' ] = $row->size;
            $fields[ $name ][ 'readonly' ] = $row->readonly;
            $fields[ $name ][ 'class' ] = $row->class;
            if ( $row->type == 'checkbox' || $row->type == 'radio' ) { // Add checkbox options from validation table
                $options = array();
                $sql = "SELECT value, special FROM formVal 
                    WHERE formFields_id = ?
                    AND operator = 'IN LIST'
                    ORDER BY groupNum";
                $pA = array( 'i', $row->id );
                $getTable = DB::cleanQuery($sql, $pA);
                if ( $getTable->getRows() > 1 ) {
					$sql = "SELECT a.option_value, IFNULL( b.option_text, a.option_text ) as option_text 
					FROM {$getTable->value} a 
					LEFT JOIN {$getTable->value} b 
					ON a.option_value = b.option_value AND b.language_code = '{$this->language}' ";
					if ( $getTable->value != 'centre' ) {
						$sql .= "WHERE a.language_code = 'en' ";
					}
					$sql .= "ORDER BY a.option_order";
					$result = DB::query($sql);
					foreach ( $result->rows as $row ) {
						$this->addOption( $row->option_text, $row->option_value );
					}
                } else {
					$sql = "SELECT a.option_value, IFNULL( b.option_text, a.option_text ) as option_text 
						FROM {$getTable->value} a 
						LEFT JOIN {$getTable->value} b 
						ON a.option_value = b.option_value AND b.language_code = '{$this->language}' 
						WHERE a.language_code = 'en' ORDER BY a.option_order";
					$ref = DB::query($sql);
                }
                foreach( $ref->rows as $rRow ) {
					$options[ $rRow->option_value ] = $rRow->option_text;
				}
                $fields[ $name ]['options'] = $options;
            }
            if ( $row->type == 'select' ) { // Adds select options from table
                $options = array();
                $sql = "SELECT value, special, operator FROM formVal 
                    WHERE formFields_id = ? ORDER BY groupNum";
                $pA = array( 'i', $row->id );
                $getTable = DB::query($sql, $pA);
                foreach( $getTable->rows as $vRow ) {
                    $filterNum = NULL;
                    switch( $vRow->operator ) {
                        case 'IN LIST':
                            if ( $vRow->special == 'FILTER' ) {
                                $filter = explode('-',$vRow->value);
                                $filterNum = $this->record->getField($filter[0],$filter[1]);
                            } else {
                                $refTable = DB::clean( $vRow->value );
                                $order = $vRow->special == 'ALPHA' ? 'name' : 'option_order';
                                if ( strpos($refTable,'-') ) {
                                    $filterBy = explode('-',$refTable);
                                    $refTable = $filterBy[0];
                                    $filterTable = $filterBy[1];
                                } else {
                                    $filterTable = NULL;
                                }
								$sql = "SELECT a.option_value, IFNULL( b.option_text, a.option_text ) as option_text
									FROM {$refTable} a 
									LEFT JOIN {$refTable} b
									ON a.option_value = b.option_value AND b.language_code = '{$this->language}' ";
                                if ( $filterTable ) {
                                    $sql .= "RIGHT JOIN {$filterTable} c
                                            ON a.id = c.{$refTable}_id ";
                                }
								if ( $refTable != 'centre' ) {
									$sql .= "WHERE a.language_code = 'en' ";
								}
                                $sql .= "ORDER BY a.{$order}";
								$ref = DB::query($sql);
                            }
                            break;
                        case 'NOT IN LIST':
                            $excludeArr = explode(',',$vRow->value);
                            break;
                        default:
                            if ( $vRow->special == 'REFERENCE' ) {
                                $valArr = explode('-',$vRow->value);
                                if ( $valArr[0] == 'user' ) {
                                    $valNum = $_SESSION['user']->get($valArr[1]);
                                }
                                foreach ( $ref->rows as $key => $rRow ) {
                                    if ( $valNum > $rRow->option_value ) {
                                        unset( $ref->rows[$key] );
                                    }
                                }
                            }
                            break;
                    }
                }
                foreach( $ref->rows as $rRow ) {
                    if ( isset($excludeArr) && in_array($rRow->option_value,$excludeArr) ) continue;
                    if ( $row->fieldName == 'centre_id' ) { // If making fields for centre_id and user is only allowed local then restrict to local
                        if ( isset( $this->user ) && $this->user->isLocal() && $rRow->option_value != $this->user->getCentre() ) {
                            continue;
                        } else {
                            $options[ $rRow->option_value ] = $rRow->option_text;
                        }
                    } else {
                        if ( isset($filterNum) ) {
                            $filterRef = explode(',',$rRow->filterRef);
                            if ( !in_array( $filterNum, $filterRef )  ) continue;
                            $options[ $rRow->option_value ] = $rRow->option_text;
                        } else {
                            $options[ $rRow->option_value ] = $rRow->option_text;
                        }
                    }
                }
                $fields[ $name ]['options'] = $options;
            }
            if ( $row->type == 'number' ) { // Gets potential units for units table
                $unit = array();
                $sql = "SELECT unit, conversion, decimal_places FROM units WHERE number = ? ORDER BY unitorder";
                $pA = array( 's', $row->fieldName );
                $ref = DB::query( $sql, $pA );              
                foreach( $ref->rows as $rRow ) {
					$unit[ $rRow->unit ][ 'conversion'] = $rRow->conversion;
                    $unit[ $rRow->unit ][ 'decimals' ] = $rRow->decimal_places;
                }
                $fields[ $name ][ 'unit' ] = $unit;
            }
            if ( $row->type == 'multiple' ) {
                $page = substr($name, 0, strpos($name, "-")); // Split out class and name from input field
                $name = substr($name, strpos($name, "-") + 1);
                $data = $this->record->getData( $page );
                $number = $data->get( $name );
                if ( $number ) {
                    for( $i = 0; $i < $number; $i++ ) {                       
                        $fields = array_merge( $fields, $this->getFormFields($page, $name, $i+1));
                    }
                }
            }
		}
		$getFormFields = $fields;
        $this->fields = $getFormFields;
		return $getFormFields;
	}
    protected function getExcludedFormFields($record=NULL)
    {
        $excluded = array();
        $sql = "SELECT * FROM formFields_branch";
        $result = DB::query( $sql );
        foreach( $result->rows as $row ) {
            $property = false;
            switch ( $row->object ) {
                case 'Trial':
                    $property = $this->{$row->property};
                    break;
                case 'Centre':
                    if ( isset( $this->userCentre ) ) {
                        $object = $this->userCentre;
                        $property = $object->{$row->property};
                    } else {
                        $centreID = $this->getUserCentre();
                        if ( $centreID ) {
                            $object = $this->userCentre = new Centre ( $centreID );
                            $property = $object->{$row->property};
                        }
                    }
                    break;
                case 'User':
                    $user = $this->getUser();
                    $property = $user->{$row->property};
                    break;
                default:
                    if ( $record ) {
                        $property = $record->getField($row->object, $row->property);
                    } elseif ($this->getRecord()) {
                        $property = $this->getRecord()->getField($row->object, $row->property);
                    }
                    break;
            }
            if ( $property !== false ) {

                if ( $row->operand == 'NOT' ) {
                    if ( $property == $row->value ) {
                        $excluded[] = $row->formFields_id;
                    }
                } elseif ( $property != $row->value ) {
                    $excluded[] = $row->formFields_id;
                }
            }
        }
        return $excluded;
    }
    public function getValRules( $page, $fieldName ) {
        $getValRules = NULL;
        $sql = "SELECT formFields.type as varType, value, operator, groupNum, 
                groupType, special, errorMessage, formFields.pages_name as page,
                formFields.fieldName as fieldName
            FROM formFields
            LEFT JOIN formVal
                ON formVal.formFields_id = formFields.id
            WHERE formFields.pages_name = ? AND formFields.fieldName = ? 
            ORDER BY groupNum, formVal.id";
        if ($fieldName === 'password') {
            $fieldName = 'password[0]';
        }
        $pA = array('ss', $page, $fieldName);
        $valRules = DB::query($sql,$pA);
        if ( $valRules->getRows() ) {
            $getValRules = $valRules->rows;
        }
        return $getValRules;
    }
	public function addUserInput( $post, $data = NULL ) {
		if ( !$data ) {
            $data = $this->record->getData( $this->getPage() );
        }

		foreach( $post as $key => $value ) {
            if ( $key == 'page' ) {
                continue;
            }
			$page = substr($key, 0, strpos($key, "-")); // Split out class and name from input field
			$fieldName = substr($key, strpos($key, "-") + 1);
			if ( $page == $this->getPage() ) {				// Checks that the input is from the right page
                $valRules = $this->getValRules( $page, $fieldName );
                if ( !emptyInput($value ) ) {
                    if ( $valRules ) {                        
                        $vR = new ValidationRules();
                        $vR->addRulesFromDB($valRules);
                        $record = isset( $this->record ) ? $this->record : $data;
                        $valid = new ValidationResult( $value, $vR, $record );
                        $value = $valid->getValue();
                        if ( $valid->isValid() ) {
                            $data->set( $fieldName, $value );
                        } else {
                            if( !is_array( $valid->getValue() ) ) {
                                $_SESSION['inputErr'][$key]['value'] = $valid->getValue();

                            } else {
                                $_SESSION['inputErr'][$key]['value'] = false; // Allows marking an error without reproducing the error value
                            }
                            $_SESSION['inputErr'][$key]['error'] = $valid->getError();
                            if ( $valid->getVarType() == 'password' ) {
                                $_SESSION['error'] = $valid->getError();
                            }
                        }
                    } else {
                        $data->set( $fieldName, $value );
                    }
                }
                
			}
		}
		if ( !$complete = $this->checkComplete( $this->getPage(), $data ) ) { // Check to see if the object is complete, if so then set it as complete, if not then add a missingData field to inputErr
			$_SESSION['inputErr']['missingData'] = true; // This ensures mandatory data missing is shown
		}
        $nextPage = $complete && !isset( $_SESSION['inputErr'] );
		return $nextPage;
	}
	public function addFlagInput( $post ) {
		$data = $this->record->getData( $this->getPage() );
		$flag = new Flag();
		$flag->link_id = $this->record->getID();
		$flag->pages_name = $this->getPage();
		foreach( $post as $key => $value ) {
			$page = substr($key, 0, strpos($key, "-")); // Split out class and name from input field
			$fieldName = substr($key, strpos($key, "-") + 1);
			if ( $page == 'flag' ) {
				$flag->set( $fieldName, $value );
			}
		}
		if ( $flag->isComplete() ) {
            $data->delete( $flag->getFieldName() );
			$flag->saveToDB();
            $flag->getFromDB();
			$flag->flag_id = $flag->getID();
		} else {
			$flag = null;			
		}
		return $flag;
	}
    public function addSignInput( $post ) {
		if ( isset( $post['comment'] ) ) {
			$this->record->set( 'comment', $post['comment'] );
			$this->record->saveToDB();
		}
        if ( isset( $post['presignpt'] ) ) {
			if ( $post['presignpt'] && $this->user->canPreSign() ) {
				$this->record->preSignRecord();
			}
		}
		if ( isset( $post['unpresignpt'] ) ) {
			if ( $post['unpresignpt'] && $this->user->canUnPreSign() ) {
				$this->record->unPreSignRecord();
			}
		}
		if ( isset( $post['signpt'] ) ) {
			if ( $post['signpt'] && $this->user->canSign() ) {
				$this->record->signRecord();
                $this->record->preSignRecord();
			}
		}
		if ( isset( $post['unsignpt'] )  ) {
			if ( $post['unsignpt'] && $this->user->canUnsign() ) {
				$this->record->unsignRecord();
			}
		}
	}
	public function checkComplete( $page = NULL, $data = NULL, $record = NULL ) {
		$checkComplete = true;
		if ( !$page ) {
			$page = $this->getPage();
		}
        // Fire this off first to prevent any possible complication of passing through the record to the form field processes
        $fields = $this->getFormFields( $page, false, NULL, $record );

        if ( !$record ) {
            $record = $this->record;
        }

        if ( !$data ) {
            $data = $record->getData($page);
            if ( !$data ) {
                return false;
            }
        }

		foreach( $fields as $name => $values ) {
			if ( isset( $values['mandatory'] ) ) {
				$mand = $values['mandatory'];
				$fieldName = substr($name, strpos($name, "-") + 1);
				if ( strpos( $fieldName, "[" ) ) {
					$fieldName = substr( $fieldName, 0, strpos( $fieldName, "[" ) );
				}
                if ( strpos( $mand, "_" ) ) {
                    list($mandField, $mandValue) = explode("_", $mand);
                } else {
                    $mandField = $mand;
                    $mandValue = 1;
                }
				if ( $mand == 1 || $data->get( $mandField ) == $mandValue ) { // If either mandatory is 1, or the fieldname in mandatory is truthy
					if ( emptyInput( $data->get( $fieldName ) ) && !( method_exists($data, 'getFlag') && $data->getFlag( $page, $fieldName, $record->getID() ) ) ) { // See if that field is filled and unflagged
						$checkComplete = false; // If not set return value as false and break (only takes one empty field to not be complete
						break;
					}
				}
			}
		}
		if ( $checkComplete ) {
			$data->complete = 1;
		} else {
			$data->complete = 0;
		}
		return $checkComplete;
	}
	public function checkAllComplete() {
		$checkComplete = array();
		$sql = "SELECT id, name, label FROM pages WHERE class IS NOT NULL AND type = 'data' AND dataName = 'Record' AND active = 1 ORDER BY pageOrder";
		$result = DB::query( $sql );
        foreach( $result->rows as $row ) {
            $data = $this->record->getData( $row->name );
            $showPage = $this->parseBranches( $row->id  );
            if ( $showPage && !$this->checkComplete( $row->name, $data ) ) {
                $checkComplete[] = $row->label;
            }
        }
		return $checkComplete;
	}
    public function checkInterimComplete($record=NULL) {
        $checkComplete = array();
        if (!isset($this->interimPageList)) {
            $sql = "SELECT id, name, label FROM pages WHERE class IS NOT NULL AND type = 'data' AND dataName = 'Record' AND active = 1 AND ( name != 'oneyear' AND name != 'oneyearit' ) ORDER BY pageOrder";
            $this->interimPageList = DB::query($sql);
        }
        if (!$record) {
            $record = $this->getRecord();
        }
        foreach( $this->interimPageList->rows as $row ) {
            $data = $record->getData($row->name);
            $showPage = $this->parseBranches( $row->id, $record->getID() );
            if ( $showPage && !$this->checkComplete( $row->name, $data, $record ) ) {
                $checkComplete[] = $row->label;
            }
        }
        return $checkComplete;
    }
	public function sendEmail( $emails ) {
        $emails = (array)$emails;
        $success = false;
        foreach( $emails as $email ) {
            $mail = new PHPMailer(TRUE);        
            try{
                $mail->IsSMTP();
                if ( $this->email['SMTP'] ) {
                    $mail->SMTPAuth   = true;   // enable SMTP authentication
                    $mail->SMTPSecure = $this->email['SMTP'];  // sets the prefix to the server
                }
                $mail->CharSet = 'utf-8';	
                $mail->Host       = $this->email['Host'];     // sets the SMTP server
                $mail->Port       = $this->email['Port'];     // set the SMTP port
                $mail->Username   = $this->email['User'];     // Username
                $mail->Password   = $this->email['Password']; // Password
                $mail->AddAddress( $email['email'], $email['name'] );
                $mail->Subject = $email['subject'];
                $mail->Body = $email['message'];
                $mail->IsHTML(TRUE);
                $retrymail = clone $mail;
                $mail->SetFrom($this->email['Address'], $this->email['Name']);
                $mail->Send();	
                $mailsent = true;
            } catch(Exception $e) {
                $error = $e->getMessage();
                $sql = "INSERT INTO log ( email, error ) VALUES ( ?, ? )";
                $pA = array( 'ss', 'Primary', $error );
                DB::query( $sql, $pA );
                $mailsent = false;
                /* try{
                    $error = $e->errorMessage();
                    $sql = "INSERT INTO log ( email, error ) VALUES ( ?, ? )";
                    $pA = array( 'ss', 'Primary/ISOS', $error );
                    DB::query( $sql, $pA );
                    if ( isset($retrymail) ) {
                        $mail = $retrymail;
                        // If email fails then try gmail backup
                        $mail->Host       = "smtp.gmail.com";     // sets the SMTP server
                        $mail->Port       = 465;     // set the SMTP port
                        $mail->Username = "noreply@perioperativemedicine.net";
                        $mail->Password = "Propofol1";
                        $mail->SetFrom('noreply@perioperativemedicine.net', 'ISOS Admin');
                        $mail->Send();
                        $mailsent = true;
                    } else {
                        $_SESSION['error'] = "Please use a valid email address.";
                        throw new Exception('No valid email address given');
                    }
                } catch ( Exception $e) {
                    $error = $e->getMessage();
                    $sql = "INSERT INTO log ( email, error ) VALUES ( ?, ? )";
                    $pA = array( 'ss', 'Secondary/Gmail', $error );
                    DB::query( $sql, $pA );
                    $mailsent = false;			
                } */
            }
            if ( $mailsent ) {
                $success = true;
            }
        }
		return $success;
	}
	
	public function clearAllData()
	{
		$sql = "SELECT tableName FROM pages LEFT JOIN classes ON pages.class = classes.name WHERE pages.class IS NOT NULL AND type='data' ORDER BY tableName";
		$result = DB::query( $sql );
		foreach( $result->rows as $row ) {
			$sql = "TRUNCATE {$row->tableName}";
			DB::query($sql);
			$sql = "TRUNCATE {$row->tableName}Audit";
			DB::query($sql);
		}
		$sql = "TRUNCATE link";
		DB::query( $sql);
		$sql = "TRUNCATE linkAudit";
		DB::query( $sql );
        $sql = "TRUNCATE userAudit";
        DB::query( $sql );
	}

	public function simulateTrial($n = 1)
	{
		$sql = "SELECT id, weight, country_id FROM centre ORDER BY id";
		$result = DB::query($sql);
		$centreArr = array();
		$centreTotal = 0;
		foreach( $result->rows as $row ) {
			for( $i = 0; $i < $row->weight; $i++ ) {
				$centreArr[$centreTotal]['id'] = $row->id;
				$centreArr[$centreTotal]['country'] = $row->country_id;
				$centreTotal++;
			}
		}
		$sql = "SELECT id, weight FROM surgicalprocedure ORDER BY id";
		$result = DB::query($sql);
		$surgeryArr = array();
		$surgeryTotal = 0;
			foreach( $result->rows as $row ) {
			for( $i = 0; $i < $row->weight; $i++ ) {
				$surgeryArr[$surgeryTotal] = $row->id;
				$surgeryTotal++;
			}
		}
		$epiduralWeight = 4;
        if ( $n == 1 ) {
            $saveFile = fopen('assignment.csv', 'w');
            fputcsv($saveFile, array('Study', 'Control', 'Group'));
        } else {
            $saveFile = fopen('assignment.csv', 'a');
        }
//        $totalTime = 0;
        $numTrial = 4600;
		for ( $i = 0; $i < $numTrial; $i++ ) {
            echo "$n - $i\r\n";
			$this->addRecord();
			$core = $this->record->getData('core');
			$centre = $centreArr[random_int(0,$centreTotal-1)];
			$id = $this->generateTrialID($centre['id']);
			$core->set('trialid',$id);
			$core->set('centre_id',$centre['id']);
            $surgery = $surgeryArr[random_int(0,$surgeryTotal-1)];
			$core->set('planned_surgery', $surgery);
			$epidural = random_int(1,10) <= $epiduralWeight ? 1 : 0;
			$core->set('planned_epidural',$epidural);
            $this->record->saveToDB();
			$this->setStudyGroup($saveFile);
			$this->record->saveToDB();
		}
//        $averageTime = $totalTime/$numTrial;
//        echo "$averageTime\r\n";
	}
}

class eCRF extends Trial {
	public $researchdb;
    public $adverseEvents;
    public $violations;
    protected $_adminUser = 1;
    public function __construct( $page ) {
        
        parent::__construct($page);
    }
    public function getTitle() {
        return $this->title;
    }
    public function getWelcome() {
        return $this->welcomeMessage;
    }
    public function isOffline() {
        return $this->offline;
    }
    public function randomisationOffline() {
        $sql = "SELECT active FROM pages WHERE name = 'addpt'";
        $result = DB::query( $sql );
        return !$result->active;
    }
    public function addAdverseEvent($ae)
    {
        $sql = "INSERT INTO aelink (adverseevent_id, link_id) VALUES ( ?, ? )";
        $pA = array('ii', $ae->getID(), $this->record->getID());
        DB::query($sql,$pA);
    }
    public function getAdverseEvents()
    {
        $sql = "SELECT adverseevent_id FROM aelink WHERE link_id = ?";
        $pA = array('i', $this->record->getID());
        $result = DB::query($sql, $pA);
        $this->adverseEvents = array();
        foreach ( $result->rows as $row ) {
            $this->adverseEvents[] = new Data($row->adverseevent_id, 'AdverseEvent');
        }
        return $this->adverseEvents;
    }
    public function addViolation($v)
    {
        $sql = "INSERT INTO violationlink (violation_id, link_id) VALUES ( ?, ? )";
        $pA = array('ii', $v->getID(), $this->record->getID());
        DB::query($sql,$pA);
    }
    public function getViolations()
    {
        $sql = "SELECT violation_id FROM violationlink WHERE link_id = ?";
        $pA = array('i', $this->record->getID());
        $result = DB::query($sql, $pA);
        $this->violations = array();
        foreach ( $result->rows as $row ) {
            $this->violations[] = new Violation($row->violation_id);
        }
        return $this->violations;
    }
	public function setStudyGroup($saveFile = false)
	{
        $output = array();
		$patient = $this->record->getData('core');
        $patient->getFromDB();
        $numControl = $numStudy = 0;
        $criteria = array('centre.country_id','planned_surgery','planned_epidural');
        foreach ( $criteria as $criterion ) {
            $sql = "SELECT studygroup, count(core.id) AS caseCount FROM core 
                    LEFT JOIN link ON link.core_id = core.id ";
            if ( strpos($criterion,'.')) {
                $sql .= "LEFT JOIN centre ON core.centre_id = centre.id ";
            }
            $sql .= "WHERE link.discontinue_id IS NULL AND studygroup IS NOT NULL
			AND {$criterion} = ?
			AND trialid != ?
			GROUP BY studygroup";
            $fieldName = str_replace('.','_',$criterion);
            $criteriaValue = $patient->get($fieldName);
            if ( is_null($criteriaValue) ) {
                echo "A fatal error has occurred.";
                exit();
            }
            $pA = array(
                'is',
                $criteriaValue,
                $patient->get('trialid')
            );
            $result = DB::query($sql, $pA);
            foreach ($result->rows as $row) {
                if ($row->studygroup == 0) {
                    $numControl += $row->caseCount;
                } else {
                    if ($row->studygroup == 1) {
                        $numStudy += $row->caseCount;
                    }
                }
            }
        }
        $output[] =  $numStudy;
        $output[] = $numControl;
		$p = random_int(1,100);
		if ( $numStudy < $numControl ) {
			if ( $p <= 80 ) {
				$studyGroup = 1;
			} else {
				$studyGroup = 0;
			}
		} elseif ( $numStudy > $numControl ) {
			if ( $p > 80 ) {
				$studyGroup = 1;
			} else {
				$studyGroup = 0;
			}
		} else {
			if ( $p <= 50 ) {
				$studyGroup = 0;
			} else {
				$studyGroup = 1;
			}
		}
		$patient->set('studygroup',$studyGroup);
        $now = date("Y-m-d H:i:s");
		$patient->set('randdatetime', $now);
        $output[] = $studyGroup;
        if ( $saveFile ) {
            fputcsv($saveFile,$output);
        }
	}
    public function generateRandomisationEmail()
    {
        $email = array();
        $counter = 1;
        $contact = $this->getUser();
        $centre = $contact->getCentre( 'name' );
        $subject = "PRISM randomisation confirmation";
        $message = "<p>Dear {$contact->forename},</p>";
        $message .= "<p>Thank you for randomising a patient from {$centre} into the PRISM trial. Please find the details below for your reference.</p>";
        $core = $this->record->getData('core');
        $message .= "<p>PRISM ID - {$core->get('trialid')}</p>";
        $sGName = $core->get('studygroup') ? 'CPAP' : 'Control';
        $message .= "<p>Study group - {$sGName}</p>";
        $message .= "<p>Best wishes,</p>";
        $message .= "<p>Trial admin</p>";
        $message .= "<p>Please note, this email address is not monitored, any problems email admin@prismtrial.org</p>";
        $email[$counter]['subject'] = $subject;
        $email[$counter]['message'] = $message;
        $email[$counter]['name'] = "{$contact->forename} {$contact->surname}";
        $email[$counter]['email'] = $contact->email;
        return $email;
    }
    public function generateCentralRandomisationEmail()
    {
        $email = NULL;
        $trigger = array(5, 10, 25);
        $centreId = $this->record->getCentre();
        $sql = "SELECT COUNT(id) as centre_count FROM core WHERE centre_id = ? GROUP BY centre_id";
        $pA = array('i',$centreId);
        $result = DB::query($sql, $pA);
        if ( $result->getRows() ) {
            $numRandomised = $result->centre_count;
            if ( $numRandomised == 1 || in_array($numRandomised, $trigger) || $numRandomised % 50 === 0 ) {
                $email = array();
                $counter = 1;
                $contact = new eCRFUser($this->_adminUser);
                $randomiser = $this->getUser();
                $centre = $randomiser->getCentre( 'name' );
                $subject = "PRISM randomisation milestone";
                $message = "<p>Dear {$contact->forename},</p>";
                $message .= "<p>{$randomiser->forename} {$randomiser->surname} from {$centre} has just randomised that centre's {$numRandomised}";
                $message .= $numRandomised == 1 ? 'st' : 'th';
                $message .= " patient into the PRISM trial.</p>";
                $message .= "<p>Best wishes,</p>";
                $message .= "<p>Trial database</p>";
                $message .= "<p>Please note, this email address is not monitored, any problems email admin@prismtrial.org</p>";
                $email[$counter]['subject'] = $subject;
                $email[$counter]['message'] = $message;
                $email[$counter]['name'] = "{$contact->forename} {$contact->surname}";
                $email[$counter]['email'] = $contact->email;
            }
        }
        return $email;
    }
    public function generateAdverseEventEmail()
    {
        $email = array();
        $counter = 1;
        $contact = new eCRFUser($this->_adminUser);
        $centre = $this->getUser()->getCentre('name');
        $subject = "PRISM Adverse Event";
        $message = "<p>Dear {$contact->forename},</p>";
        $message .= "<p>";
        $message .= $this->getUser()->forename . " " . $this->getUser()->surname;
        $message .= " from {$centre} has just recorded an adverse event on patient ID ";
        $message .= $this->record->getData('core')->get('trialid');
        $message .= ". Please log onto the site to check the details.</p>";
        $message .= "<p>Best wishes,</p>";
        $message .= "<p>Trial admin</p>";
        $message .= "<p>Please note, this email address is not monitored, any problems email admin@prismtrial.org</p>";
        $email[$counter]['subject'] = $subject;
        $email[$counter]['message'] = $message;
        $email[$counter]['name'] = "{$contact->forename} {$contact->surname}";
        $email[$counter]['email'] = $contact->email;
        return $email;
    }
    public function generateViolationEmail()
    {
        $email = array();
        $counter = 1;
        $contact = new eCRFUser($this->_adminUser);
        $centre = $this->getUser()->getCentre('name');
        $subject = "PRISM Protocol Deviation";
        $message = "<p>Dear {$contact->forename},</p>";
        $message .= "<p>";
        $message .= $this->getUser()->forename . " " . $this->getUser()->surname;
        $message .= " from {$centre} has just recorded a protocol deviation on patient ID ";
        $message .= $this->record->getData('core')->get('trialid');
        $message .= ". Please log onto the site to check the details.</p>";
        $message .= "<p>Best wishes,</p>";
        $message .= "<p>Trial admin</p>";
        $message .= "<p>Please note, this email address is not monitored, any problems email admin@prismtrial.org</p>";
        $email[$counter]['subject'] = $subject;
        $email[$counter]['message'] = $message;
        $email[$counter]['name'] = "{$contact->forename} {$contact->surname}";
        $email[$counter]['email'] = $contact->email;
        return $email;
    }
}

// INSERT INTO formFields ( pages_name, fieldName, labelText, type, entryorder, mandatory, defaultVal, toggle, help, multiple, class, size, dl_name, readonly ) SELECT pages_name, fieldName, labelText, type, entryorder, mandatory, defaultVal, toggle, help, multiple, class, size, dl_name, 0 FROM isos_isosdb.formFields WHERE pages_name IN ('register','forgotpass','siteinfo','addsite')
?>