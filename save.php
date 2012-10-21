<?php

file_put_contents( $_POST['id'], $_POST['value'] );

// Retrieve the Markdown and return.
require_once( "markdown.php" );

$mdown = file_get_contents( $_POST['id'] );

$html = Markdown( $mdown );
// apply SmartyPants
if ( $settings['smartypants'] ) {
  require_once( "smartypants.php" );
  $html = SmartyPants( $html );
}

echo $html;
