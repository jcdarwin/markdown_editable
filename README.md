# Serving editable markdown documents using a simple local server #

## Description ##
This document explains how to set up a simple local server which can be used to serve [Markdown][md] documents that can be edited in the browser.

This allows us to create a [Markdown][md] document (such as this one) to provide simple documentation that we can easily examine
in its marked-up form in a web browser.

The markdown documents will be editable in the browser, meaning that the browser can be used both to create and edit the markdown, as well as view the final results. 

*Important: The following presumes you are running PHP >=5.4.0 (refer [upgrading to PHP 5.4 on Mac](http://php-osx.liip.ch/))*

## Installation ##

### Download the [php markdown script][pms] ###

    $ cd /scripts    
    $ git clone git://github.com:jcdarwin/markdown_editable.git markdown

    $ chmod ug+x markdown/*.php

### Download the [php Markdown library](http://pear.michelf.ca/) ###

    $ pear channel-discover pear.michelf.ca
    $ pear install michelf/Markdown

### Add the include_path to the php.ini ###

    $ sudo vim /usr/local/php5/lib/php.ini

    include_path=".:/usr/local/php5/lib/php:/scripts/markdown"

### Create a shell script, server.markdown, in our markdown directory to run the php web server ###

    $ cd /scripts/markdown
    $ sudo vim server.markdown

    #!/bin/bash
    php -S localhost:8000 render.php

    $ chmod ug+x server.markdown

### Add the location of our script to our PATH ###

    $ sudo vim ~/.bash_profile

    $ export PATH=/usr/local/php5/bin:$PATH:/scripts/markdown

    $ . ~/.bash_profile
  
### At a console, cd into any directory that contains a markdown file and run the shell script ###

    $ cd c:/scripts/epub_tools
    $ server.markdown
    
    PHP 5.4.6 Development Server started at Fri Aug 31 10:59:26 2012
    Listening on http://localhost:8000
    Document root is /scripts/markdown
    Press Ctrl-C to quit.
   
## Credit ##

This project builds upon the following:

* [Render Markdown as HTML](http://projects.skurfer.com/Example.mdown)
* [jEditable](http://www.appelsiini.net/projects/jeditable)

Thanks go to [Rob McBroom][rb], [Gruber][df], Michel Fortin and [Mika Tuupola][mt] for their work.

[pms]:    http://projects.skurfer.com/Example.mdown
[md]:     http://daringfireball.net/projects/markdown/
[mmd]:    https://github.com/fletcher/MultiMarkdown/wiki/MultiMarkdown-Syntax-Guide
[readme]: http://projects.skurfer.com/Example.mdown
[phpmd]:  http://michelf.com/projects/php-markdown/
[phpsp]:  http://michelf.com/projects/php-smartypants/
[toc]:    http://www.webdesignlessons.com/creating-a-table-of-contents-generator-in-php/
[df]:     http://daringfireball.net/
[rb]:       http://www.skurfer.com/
[mt]:    http://www.appelsiini.net/