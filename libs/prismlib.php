<?php
include($_SERVER['DOCUMENT_ROOT'] . '/libs/serverconfig.php');
DB::setDB('prism');
session_name( "prism" );

function write_head() {
	echo <<<_END
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png">
<link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png">
<link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png">
<link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png">
<link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png">
<link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png">
<link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="/favicon-192x192.png" sizes="192x192">
<link rel="icon" type="image/png" href="/favicon-160x160.png" sizes="160x160">
<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
<meta name="msapplication-TileColor" content="#b91d47">
<meta name="msapplication-TileImage" content="/mstile-144x144.png">
<title>PRevention of Insufficiency after Surgical Management (PRISM) trial</title>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="/css/prism.css" rel="stylesheet" type="text/css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript" ></script>
<script src="/js/bootstrap.js" type="text/javascript" ></script>
<script src="/js/prism.js" type="text/javascript" ></script>  
<!--[if lt IE 9]> <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script> <![endif]-->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-58372142-1', 'auto');
  ga('send', 'pageview');

</script>
</head>
_END;
}
function write_header() {
	echo '<header class="jumbotron subhead"><p>PRISM</p>';
	echo '</header>';
}
function write_sidebar($inc) {
$sidebar = array(
    'news' => 'Home',
    'about' => 'About the Study',
    'interest' => 'Register your Interest',
    'data' => 'Data Entry',
    'docs' => 'Study Documents',
    'faq' => 'Frequently Asked Questions',
    'contact' => 'Contact Us'
);

echo <<<_END
	
            <div class="span3">                
                <nav>
                    <ul class="nav nav-list sidenav span3">
_END;
if ( !array_key_exists($inc, $sidebar) ) {
    $inc = 'news';
}
foreach ( $sidebar as $page => $title ) {
    echo "<li";
    if ( $inc == $page ) {
        echo " class=\"active\"";
    }
    echo "><a href=\"/{$page}/\">{$title}</a></li>";
}
echo <<<_END
                    </ul>
                </nav>
            </div>

_END;
}
function write_footer() {
echo <<<_END
<footer>
    
    <a href="http://www.qmul.ac.uk/">
        <img class="span2 pull-right" src="/img/qmulLogoTrans.png"/>
    </a>
    
    <br clear="all" />
</footer>
</div>
</body>
</html>
_END;
}
?>
