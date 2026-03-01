=== jwp-a11y ===
Contributors: jidaikobo
Donate link: https://www.jidaikobo.com/donate.html
Tags: accessibility, checker, WCAG, JIS X 8341-3
Requires PHP: 5.6.0
Requires at least: 4.8.3
Tested up to: 6.0.1
Stable tag: 4.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

[![WP compatibility](https://plugintests.com/plugins/jwp-a11y/wp-badge.svg)](https://plugintests.com/plugins/jwp-a11y/latest)
[![PHP compatibility](https://plugintests.com/plugins/jwp-a11y/php-badge.svg)](https://plugintests.com/plugins/jwp-a11y/latest)

Check accessibility of target page and generate accessibility evaluate page and policy.

== Description ==

Check accessibility of target page and generate accessibility evaluate page and policy.

"Accessibility policy" and "accessibility report" required by WCAG 2.0 and JIS X 8341-3:2016 can be created.

When installing the plug-in, you can automatically check individual posts when saving, so that you can constantly manage the site with accessibility awareness.

[translate by using GlotPress](https://translate.wordpress.org/projects/wp-plugins/jwp-a11y)

thx:
* [spyc](https://github.com/mustangostang/spyc)
* [guzzle](https://github.com/guzzle/guzzle)
* [PluginTests](https://plugintests.com/plugins/jwp-a11y)
* [GitHub](https://github.com)
* [OWASP ZAP](https://www.owasp.org/index.php/OWASP_Zed_Attack_Proxy_Project)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.

1. Activate the plugin through the 'Plugins' screen in WordPress

1. Use the Settings->Plugin Name screen to configure the plugin

== Screenshots ==

1. Checklist - en

2. Checklist - ja

== Changelog ==

= 4.1.7 =
fix PHP 8.0 Deprecated errors again

= 4.1.6 =
fix PHP 8.0 Deprecated errors

= 4.1.5 =
fix docs :Array and string offset access syntax with curly braces is no longer supported

= 4.1.4 =
remove <b> from meanless element
ignore file existence check when check 2.4.4

= 4.1.2 =
document maintenance

= 4.1.1 =
fix session timing

= 4.1.0 =
fix template bug

= 4.0.9 =
fix english resource lack

= 4.0.8 =
fix some messages

= 4.0.7 =
fix add page function by using admin tool bar

= 4.0.6 =
fix redirect trouble
fix in case filter_input(INPUT_SERVER) is not working

= 4.0.5 =
fix unused upgrade method

= 4.0.4 =
fix report page

= 4.0.3 =
fix wrong class name: CssFormat

= 4.0.2 =
fix wrong class name

= 4.0.1 =
fix some tiny bugs and MySQL field size problem

= 4.0.0 =
use new core

= 3.4.0 =
move some directories

= 3.3.9 =
better cache control

= 3.3.8 =
maintenance

= 3.3.7 =
change screen shots

= 3.3.6 =
fix lack of constant

= 3.3.5 =
fix soome undefined bugs

= 3.3.4 =
fix defined bug

= 3.3.3 =
move lib directories

= 3.3.2 =
fix tiny bugs

= 3.3.1 =
refactoring and fix constant

= 3.3.0 =
refactoring and fix tiny bugs

= 3.2.9 =
fix about html escape

= 3.2.8 =
many refactoring

= 3.2.7 =
fix empty_link_element

= 3.2.6 =
start to support aria-label and aria-labelledby

= 3.2.5 =
fix donate link

= 3.2.4 =
fix error count problem
fix sort pages

= 3.2.3 =
delete unused files
at html5, ignore check of existence of table's summary attribute

= 3.2.2 =
no space between attribute
better comment out logic
ignore CDATA section

= 3.2.1 =
fix each result

= 3.2.0 =
fix result

= 3.1.9 =
lower compatible (array_column() php 5.5)

= 3.1.8 =
remove ajax

= 3.1.7 =
improve checklist behaviour

= 3.1.6 =
refactoring
fix bug of error messages

= 3.1.5 =
modify settings table

= 3.1.4 =
at readme: content of was invalid string (Y-n-j -> Y-m-d)(thx @momdo_).
at documentation: fix unexpectedlly escaped HTML and markup HTML by <code>(thx @momdo_).
at labelless check: if action attribute was not exists, use <form> to indicate place.

= 3.1.3 =
upgrade problem

= 3.1.2 =
Refine regular expression of extraction html tag.
Became able to check non UTF-8 page.
Update Japanese title of Techniques for WCAG 2.0.
Better lang check

= 3.1.1 =
add some new checks

= 3.1.0 =
fix evaluate mistake

= 3.0.9 =
count notice

= 3.0.8 =
Contributors can check pages

= 3.0.7 =
first test user is current user

= 3.0.6 =
fix first edit problem

= 3.0.5 =
fix issue edit

= 3.0.4 =
improve issue control

= 3.0.3 =
add nonce

= 3.0.2 =
lower compatibly 2

= 3.0.1 =
lower compatibly

= 3.0.0 =
core update

= 2.0.2 =
better label uniqueness check

= 2.0.1 =
make notice better

= 2.0.0 =
maintenance core

= 1.9.9 =
maintenance core

= 1.9.8 =
add notice

= 1.9.7 =
better ja breaking words logic

= 1.9.6 =
fix check logic

= 1.9.5 =
add errors about pdf

= 1.9.4 =
add alternative content check system and PDF

= 1.9.3 =
fix some notices

= 1.9.2 =
add some nonce

= 1.9.1 =
bug fix. show ng comment

= 1.9.0 =
better title for disclosure

= 1.8.9 =
better css

= 1.8.8 =
review disclosure page

= 1.8.7 =
ssl

= 1.8.6 =
fix update trouble

= 1.8.5 =
fix Db::is_table_exist()

= 1.8.4 =
refactoring

= 1.8.3 =
remove ucfirst()

= 1.8.2 =
fix upgrade bug

= 1.8.1 =
fix remove directory

= 1.8.0 =
use MySQL

= 1.7.9 =
add dashboard Notation

= 1.7.8 =
better doc search

= 1.7.7 =
better explanation

= 1.7.6 =
improve here link error

= 1.7.5 =
apply shortcode

= 1.7.4 =
add option "stop Guzzle"

= 1.7.3 =
better ua

= 1.7.2 =
add about placeholder

= 1.7.1 =
language maintenance

= 1.7.0 =
lower php compatible

= 1.6.9 =
add guzzle to env check

= 1.6.8 =
review link check

= 1.6.7 =
fix $_SERVER issue

= 1.6.6 =
improve link check trust

= 1.6.5 =
fix PHP_SESSION_DISABLED issue

= 1.6.4 =
fix sqlite garbage collector

= 1.6.3 =
update HTML checker.

= 1.6.2 =
update libraries. guzzle and Spyc.

= 1.6.1 =
fix section validate

= 1.6.0 =
fix js

= 1.5.9 =
check h* less section

= 1.5.8 =
suggest file size when Download link exist.

= 1.5.7 =
hide link some controllers for lower php version.

= 1.5.6 =
refactoring of js and css

= 1.5.5 =
fix is post exists

= 1.5.4 =
recover trust_ssl_url by tragic reason

= 1.5.3 =
update guzzle and refactoring

= 1.5.2 =
check custom_fields

= 1.5.1 =
php included html

= 1.5.0 =
fix timezone

= 1.4.9 =
fix notice Validate_Validation::appropriate_heading_descending()

= 1.4.8 =
maintanance

= 1.4.7 =
validator bug fix

= 1.4.6 =
temporal fix of guzzle

= 1.4.5 =
add guzzle to Basic auth

= 1.4.4 =
many improvements

= 1.4.3 =
fix ajax problem

= 1.4.2 =
add icons and improve usability of admin bar

= 1.4.1 =
library update

= 1.4.0 =
fix constant

= 1.3.9 =
improve maintenance

= 1.3.8 =
add db directory safely2

= 1.3.7 =
add db directory safely

= 1.3.6 =
add db directory

= 1.3.5 =
fix data source select

= 1.3.4 =
add version control

= 1.3.3 =
fix link check bug

= 1.3.2 =
fix ssl bug

= 1.3.1 =
improve link check and crawl

= 1.3.0 =
improve link check and crawl

= 1.2.9 =
fix page counting

= 1.2.8 =
third argument of null of mb_substr(), occasionally not return end of string.

= 1.2.7 =
typo

= 1.2.6 =
fix evaluate total

= 1.2.5 =
fix method

= 1.2.4 =
many fixes

= 1.2.3 =
fix total evaluation

= 1.2.2 =
compatible with php <= 5.4

= 1.2.1 =
improve error message of same title

= 1.2.0 =
unchecked criterion can be saved its memo

= 1.1.9 =
pdo transaction was not work well...

= 1.1.8 =
php7.0 issue

= 1.1.7 =
fix some documents

= 1.1.6 =
fix localize issue

= 1.1.5 =
fix setup

= 1.1.4 =
fix setup

= 1.1.3 =
add some explanation to readme

= 1.1.2 =
support php7
fix non post validate

= 1.1.1 =
fix langless check

= 1.1 =
Add English Documents (by Google Translate)
SSL and basic auth.

= 1.0 =
Currently Japanese only.  We are waiting language contribution!

== Upgrade Notice ==

= 1.0 =
Currently Japanese only.  We are waiting language contribution!

== Frequently Asked Questions ==

not yet.
