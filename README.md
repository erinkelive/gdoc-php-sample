# gdoc-php-sample
Sample of Source Code in PHP to acesses Google Docs API

## Version 2 example

The `v2/` directory contains an improved script (`index.php`) showing a
cleaner approach using a small helper class (`DocsManager`). It demonstrates
how to create or open a document and insert formatted text. The original file
`index-github.php` remains unchanged.

This is a type of quick reference sample php code to integrate
Google Docs API in PHP, product of more than 60 hours grindind
and testing codes, not 100% right, with many bugs yet. Because of
the lack of informations (even from google to developers) of how
we can use your GAPIs script's in developer languages like PHP.

This sample is a little important to developers because some times 
we need to host our codes in some REMOTE HOST. And, this remote host, 
sometimes don't give us terminal access to use commands like 
"compose require xxx", so i'd to grind for hours to remove unused files
from google library, grind more hours to edit some part of that librarys
to adapt the functions. Functions that never work good in remote servers.

Importat to say, files in vendor folder MAY BE EDITED or ADAPTED, if u use
this code, i think u can have problems if u update ur vendor composer.

Use this sample at ur own risk!! we don't warrant or grant anything.
It's free to ur use.

So, this is just a simple library to access GDocs API in PHP language
samples of insert text function, create file in GDoc, Authenticate,
and some others functions like set background/foreground colors, bolds,
etc...

Your can use pieces or full code for free, 
but we request some contribution (code updates or sugestions/critics) 
from you to this free utilization of this code sample.

Thx.
---------------------

To test this code u need ur auth-credentials.json downloaded from Google Console
u need to set full URL of response (response URI) of this file in the OAuth Credentials
again in the Google Console before u save and download it.
Put it in the same home base folder (its just a test, move it to other more secure and 
edit the code to set this path correctly)
use ur compose update command to download de APIs librarys
so put it all in a https server (remote host) and access the address by ur browser
good luck
thx again!

----------------------

