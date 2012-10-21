<?php

// Retrieve the Markdown and return.
require_once( "markdown.php" );

$mdown = file_get_contents( $_GET['id'] );

echo $mdown;
