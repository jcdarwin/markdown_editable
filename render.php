<?php

/*
    Based on the work of Rob McBroom
    http://projects.skurfer.com/Example.mdown

    Display a Markdown file as HTML, and use jEditable
    to allow in-browser editing of the Markdown.
    http://www.appelsiini.net/projects/jeditable
*/

$settings = parse_ini_file( "render.ini" );
$requested = rawurldecode( $_SERVER['REQUEST_URI'] );
$request_parts = explode( '-', $requested );

if ( array_pop( $request_parts ) == $settings['text_suffix'] ) {
  // replace the requested name with extension removed
  $requested = implode( '-', $request_parts );
  $show_text = true;
} else {
  // the file name to use for display and URLs
  $requested_file = basename( rawurldecode( $requested ) );
  $show_text = false;
}

if ( preg_match( "/^\./", $requested ) || $requested == "index.php" ) {
  // suspicious
  header( "Content-type: text/plain" );
  echo "It looks like you're up to something.\n";
  echo "Trying to read: $requested\n";
  exit;
}

// Strip any query parameters from end of php url.
$requested = preg_replace('/\.php.*$/', '.php', $requested);

// File (Markdown or otherwise) to read
$md_source = $_SERVER['DOCUMENT_ROOT'] . $requested;

$md_ext = preg_replace('/^.*\./', '', $md_source);

// Path to use in link URLs
$ht_path = dirname( $_SERVER['SCRIPT_NAME'] );

// As we can't rely on $ht_path, use a setting from the ini file to determine the script home.
$home = $settings['home'];

// Our allowable mardown extensions.
$md_exts = array('markdown', 'mdown', 'md', 'mkd');

if ( file_exists($md_source) && in_array($md_ext, $md_exts) ) {

  // if file name ended with text_suffix, show the original Markdown
  if ( $show_text ) {
    header( "Content-type: text/plain" );
    readfile( $md_source );
  } else {

    // Publish/Display the text as HTML
    $mdown = file_get_contents( $md_source );

    // check for metadata?
    $metadata = false;

    switch ( $settings['metadata'] ) {
      case 'remove':
        // look for metadata and discard it
        require_once( "mdmd.php" );
        $result = parse_metadata( $mdown );
        $mdown = $result['document'];
      break;
      case 'table':
        // store metadata for display in an HTML table
        // (and strip it from the original document)
        require_once( "mdmd.php" );
        $result = parse_metadata( $mdown );
        $metadata = $result['metadata'];
        $mdown = $result['document'];
      break;
      default:
        // don't even look for metadata
      break;
    }
    // convert Markdown to HTML
    require_once( "markdown.php" );
    $html = Markdown( $mdown );

    // apply SmartyPants
    if ( $settings['smartypants'] ) {
      require_once( "smartypants.php" );
      $html = SmartyPants( $html );
    }

    if ( $settings['jeditable'] ) {
      // Wrap the markup in an editable div.
      $id = preg_replace('/^\//', '', $requested);
      $html = "<div class='editable_textarea' id='${id}'>$html</div>";
    }

    // include a table of metadata
    if ( $metadata ) {
      $mtable = '<table id="metadata"><tbody>' . PHP_EOL;
      foreach( $metadata as $attr => $val ) {
        if ( isset($settings['link_pattern']) && in_array( $attr, $settings['link_attrs'] ) ) {
          // turn value(s) into a link
          $newval = array();
          foreach( $val as $target ) {
            $link = preg_replace(
              array( '/%k/', '/%v/' ),
              array( $attr, $target ),
              $settings['link_pattern']
            );
            array_push( $newval, "<a href=\"$link\">$target</a>" );
          }
          $val = $newval;
        }
        $val = implode( "<br>", $val );
        $mtable .= "  <tr>" . PHP_EOL;
        $mtable .= "    <td class=\"mda\">$attr</td><td class=\"mdv\">$val</td>" . PHP_EOL;
        $mtable .= "  </tr>" . PHP_EOL;
      }
      $mtable .= "</tbody></table>" . PHP_EOL;
      $html = $mtable . $html;
    }
    // window title
    $title = "Viewing Markdown file ($requested_file) as HTML";
    if ( $settings['title_from_heading'] ) {
      $title = get_title( $html );
    }
    // add the Table of Contents
    if ( isset($settings['toc']) ) {
      $html = table_of_contents( $html );
      if ( $settings['toc_hidden'] ) {
        $toc_display = ' onLoad="javascript:toggleVisibility(document.getElementById(\'showhide\'), \'TOC\');"';
      } else {
        $toc_display = '';
      }
    } else {
      $toc_display = "";
    }

    $jeditable_scripts = '';
    if ( $settings['jeditable'] ) {
      $jeditable_scripts = <<<JEDITABLE
  <script src="lib/jquery-1.8.2.min.js" type="text/javascript"></script>
  <script src="lib/jquery.hotkeys.js" type="text/javascript"></script>
  <script src="lib/jquery.jeditable.js" type="text/javascript"></script>
  <script>
       $(document).ready(function() {
            $(".editable_textarea").editable("http://localhost:8000/save.php", { 
                indicator : "<img src='img/indicator.gif'>",
                loadurl  : 'http://localhost:8000/load.php',
                type   : 'textarea',
                submitdata: { _method: "put" },
                select : true,
                submit : 'OK',
                cancel : 'cancel',
                cssclass : "editable"
            });
            
       });
  </script>
JEDITABLE;
    }

    echo <<<HTML
<html>
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  <link rel="stylesheet" href="markdown-screen.css" type="text/css" media="screen" charset="utf-8">
  <link rel="stylesheet" href="markdown-print.css" type="text/css" media="print" charset="utf-8">
  <title>$title</title>
  ${jeditable_scripts}
  <script type="text/javascript" charset="utf-8">
    function toggleVisibility(theButton, targetName) {
      var target = document.getElementById(targetName);
      if ( target.style.opacity == '0' ) {
        // show
        target.style.left = '0px';
        target.style.position = 'relative';
        target.style.opacity = '1';
        theButton.innerHTML = "Hide Table of Contents";
      } else {
        // hide
        target.style.left = '-4000px';
        target.style.position = 'absolute';
        target.style.opacity = '0';
        theButton.innerHTML = "Show Table of Contents";
      }
      return true;
    }
  </script>
</head>
<body${toc_display}>

HTML;
    if ( isset($settings['toc']) ) {
      echo '<div class="controls" style="float:left;width:32%;"><p id="showhide" class="controls" onClick="toggleVisibility(this, \'TOC\');" style="float:left;margin:0;cursor:pointer;">Hide Table of Contents</p></div>' . PHP_EOL;
    }
    if ( $settings['text_version'] ) {
      $text_href = "${requested_file}-${settings['text_suffix']}";
      echo '<div class="controls" style="float:right;"><a href="' . $text_href . '">View Original Text</a></div>' . "\n";
      echo '<div class="controls" style="text-align:center;margin-left:32%; margin-right:32%;"><a href="http://en.wikipedia.org/wiki/Markdown#Syntax_examples">Markdown syntax</a></div>' . "\n";
    }
    echo $html;
    echo <<<HTML
<div id="bigfoot">
  <!-- A decent amount of empty space was added so the browser can jump to anchors near the bottom of the page. -->
  &nbsp;
</div>

HTML;
    echo <<<HTML
</body>
</html>
HTML;
  }
} elseif ( file_exists( $home . $requested ) ) {

    if ( preg_match( "/load\.php$/", $requested ) ) {
      // Retrieve the Markdown and return.
      require_once( $home . $requested );
    } elseif ( preg_match( "/save\.php$/", $requested ) ) {
      // Save the Markdown.
      require_once( $home . $requested );
    } elseif ( preg_match( "/\.js$/", $requested ) ) {
      // We're after a javascript resource.
      header( "Content-type: application/javascript" );
      echo file_get_contents($home . $requested);
    } elseif ( preg_match( "/\.css$/", $requested ) ) {
      // We're after a css resource.
      header( "Content-type: text/css" );
      echo file_get_contents($home . $requested);
    }

} else {
  // the requested file doesn't exist
  header( "HTTP/1.1 404 Not Found" );
  header( "Content-type: text/html" );
  echo <<<HTML
<html>
<head>
  <title>404 Not Found</title>
  <link rel="stylesheet" href="${ht_path}/markdown-screen.css" type="text/css" media="screen" charset="utf-8">
</head>
<body>
<p>I couldn't find anything under the name of ${requested}. Sorry.</p>
</body>
</html>
HTML;
html_comment( "Requested: $requested" );
}

function table_of_contents( $html ) {
  global $settings;
  preg_match_all("/(<h([1-6]{1})[^<>]*>)(.+)(<\/h[1-6]{1}>)/", $html, $matches, PREG_SET_ORDER);
  $toc = "";
  $list_index = 0;
  $indent_level = 0;
  $raw_indent_level = 0;
  $anchor_history = array();
  $Sections = [];
  $SectionWIDs = [];
  foreach ( $matches as $val ) {
    ++$list_index;
    $prev_indent_level = $indent_level;
    $indent_level = $val[2];
    $anchor = safe_parameter( $val[3] );
    // ensure that we don't reuse an anchor
    $anchor_index = 1;
    $raw_anchor = $anchor;
    while ( in_array( $anchor, $anchor_history ) ) {
      $anchor_index++;
      $anchor = $raw_anchor . strval( $anchor_index );
    }
    array_push( $anchor_history, $anchor );
    if ( $indent_level > $prev_indent_level ) {
      // indent further (by starting a sub-list)
      $toc .= "\n<ul>\n";
      $raw_indent_level++;
    }
    if ( $indent_level < $prev_indent_level ) {
      // end the list item
      $toc .= "</li>\n";
      // end this list
      $toc .= "</ul>\n";
      $raw_indent_level--;
    }
    if ( $indent_level <= $prev_indent_level ) {
      // end the list item too
      $toc .= "</li>\n";
    }
    // add permalink?
    if ( isset($settings['permalink']) && $settings['permalink'] != "" ) {
      $pl = ' <a href="#'.$anchor.'" class="permalink" title="link to this section">' . $settings['permalink'] . '</a>';
    } else {
      $pl = "";
    }
    // print this list item
    $toc .= '<li><a href="#'.$anchor.'">'. $val[3] . '</a>';
    $Sections[$list_index] = '/' . addcslashes($val[1] . $val[3] . $val[4], '/.*?+^$[]\\|{}-()') . '/'; // Original heading to be Replaced
    $SectionWIDs[$list_index] = '<h' . $val[2] . ' id="'.$anchor.'">' . $val[3] . $pl . $val[4]; // New Heading
  }
  // close out the list
  $toc .= "</li>\n";
  for ( $i = $raw_indent_level; $i > 1; $i-- ) {
    $toc .= "</ul>\n</li>\n";
  }
  $toc .= "</ul>\n";
  return '<div id="TOC">' . $toc . '</div>' . "\n" . preg_replace($Sections, $SectionWIDs, $html, 1);
}

function get_title( $html ) {
  if ( preg_match( "/<h[1-6]{1}[^<>]*>(.+)<\/h[1-6]{1}>/", $html, $matches ) ) {
    return strip_tags( $matches[1] );
  } else {
    return "Untitled Markdown Document";
  }
}

function safe_parameter( $unsafe ) {
  
  /* change a string into something that can be safely used as a parameter
  in a URL. Example: "Rob is a PHP Genius" would become "rob_is_a_php_genius" */
  
  // remove HTML tags
  $unsafe = strip_tags( $unsafe );
  // remove all but alphanumerics, spaces and underscores
  $lowAN = preg_replace( "/[^a-z0-9_ ]/", "", strtolower( $unsafe ) );
  // replace spaces/underscores with underscores
  $clean = preg_replace( "/[ _]+/", "_", $lowAN );
  // remove any leading or trailing underscores
  $safe = trim( $clean, '_' );
  return $safe;
}

function html_comment( $invar ) {
  /* for debugging - this function will spit out an HTML comment
  to show what's in a variable */
  
  echo "\n<!--\n";
  print_r( $invar );
  echo "\n-->\n";
}
?>
