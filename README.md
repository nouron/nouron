[nouron - a free space opera browsergame](http://www.nouron.de.vu)
================

Quickstart
----------
Nouron currently uses an SQLite database that is delivered with the project (located under data/db). So for quick testing you can start Nouron out of the box! However: the used database engine for production can and will be changed in the future.

```bash
# after cloning run composer update
(project_root)$ php composer.phar update

# start local testserver
(project_root)$ php -S localhost:10000 -t public/

# (optional) run unittests
(project_root)$ phpunit
```


Versioning
----------

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

more informations will be follow...



Bug tracker
-----------

https://github.com/nouron/nouron/issues


Blog and Social Media
---------------------

* [Tec's Playground](http://tector.wordpress.com).
* [Facebook](http://facebook.com/nouronbg).
* [Twitter](http://twitter.com/_nouron).
* [Google+](http://plus.google.com/106638531327318351915)

Authors and Supporters
----------------------

+ Mario Gehnke http://github.com/tector
+ Thanks to Peter Wippermann (www.todoz.de) and Jacqueline Wiesenberg for some of the graphics.


Copyright and license
---------------------

Copyright 2012-2014 Mario Gehnke

The sourcecode is licensed unter the conditions of GNU General Public License V3. See:
* LICENSE.txt
* http://www.gnu.org/licenses/

All graphics and texts are licensed (unless otherwise noted) under the conditions of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany (CC BY-NC-SA 3.0).
See:
* (de) http://creativecommons.org/licenses/by-nc-sa/3.0/de
* (en) http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en

The project uses code of frameworks or libraries which have their own licenses. Please see the license informations in these libraries too:
* Zend Framework 2
* Bootstrap
* JQuery
* JParallax
*
