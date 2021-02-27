---
title: Braintacle - Keep track of all your hard- and software
---
<link rel="stylesheet" href="assets/css/gallery.css">
<link rel="stylesheet" href="assets/css/photoswipe.css">
<link rel="stylesheet" href="assets/css/default-skin/default-skin.css">
<script src="assets/js/photoswipe.min.js"></script>
<script src="assets/js/photoswipe-ui-default.min.js"></script>
<script src="assets/js/gallery.js" defer></script>

Screenshots
-----------

<div class="gallery">
{% for image in site.data.gallery %}
<figure>
  <a href="assets/screenshots/{{ image.filename }}">
    <img src="assets/thumbnails/{{ image.filename }}" alt="{{ image.title }}">
  </a>
  <figcaption>{{ image.title }}</figcaption>
</figure>
{% endfor %}
</div>

Requirements
------------

- A [PostgreSQL](https://postgresql.org) or [MySQL](https://www.mysql.com) (or one of its derivates, like [MariaDB](https://mariadb.org)) database.
  Support for other database backends may be added in the future.

- [Apache httpd](http://httpd.apache.org) with [mod_perl](https://perl.apache.org) and a database-specific DBD module for the
  main server component.

- A web server with [PHP](https://php.net) 7.3 or later for the administration
  console. PHP is also required for most of the command line tools. The following
  PHP extensions are required:

  - A database-specific PHP extension, see [https://docs.laminas.dev/laminas-db/adapter/](https://docs.laminas.dev/laminas-db/adapter/)

  - The "intl" extension

  - The "mbstring" extension

  - The "zip" extension is optional. If present, the package builder can create ZIP
    archives on the fly.

  - The "gmp" extension is required on 32 bit PHP installations only.

- [Composer](https://getcomposer.org/)

- On every client that should be managed through Braintacle, either the
  [OCS Inventory NG agent](https://github.com/OCSInventory-NG/) for Windows/UNIX/Android or
  [FusionInventory Agent](http://www.fusioninventory.org/documentation/agent/installation/)
  must be installed.


Installation
------------

Refer to the INSTALL.md in the downloaded archive.

License
-------

Braintacle is released under the [GNU General Public License v2](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html) or later.

For third party code, see README.md in the downloaded archive.

{% include photoswipe.html %}
