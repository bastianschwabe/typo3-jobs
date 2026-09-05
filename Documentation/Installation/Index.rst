..  _installation:

============
Installation
============

Requirements
============

..  list-table::
    :header-rows: 1

    *   -   Component
        -   Version
    *   -   TYPO3
        -   14.3 LTS or newer
    *   -   PHP
        -   8.2 – 8.5

The extension is v14-only on purpose. That keeps it free of compatibility
layers: no :file:`ext_tables.php`, plugins as their own :sql:`CType`,
configuration as a site set, and a database schema that is derived from TCA.
:file:`ext_tables.sql` only carries what TCA cannot express: the two
coordinate columns of a location and the three tracking tables, which have no
TCA on purpose.

Install with Composer
=====================

..  code-block:: bash

    composer require bastianschwabe/jobs

Assign the site set
===================

Open :guilabel:`Site Management > Sites`, edit your site and add the
:guilabel:`Jobs` set under :guilabel:`Sets for this Site`. The set brings the
Fluid template paths, the plugin TypoScript and the settings described in
:ref:`configuration`.

..  note::
    The set depends on :composer:`typo3/cms-fluid-styled-content`, because every
    Extbase plugin renders through :typoscript:`lib.contentElement`.

Add the plugins
===============

#.  Create a storage folder for the records.
#.  Place the :guilabel:`Jobs: List` plugin on your overview page.
#.  Place the :guilabel:`Jobs: Detail` plugin on a separate detail page.
#.  In the site settings, set :guilabel:`Detail page` to that page so the list
    can link to it.

Run :guilabel:`Admin Tools > Maintenance > Analyze Database Structure` after
installing, so the record tables (from TCA) and the tracking tables (from
:file:`ext_tables.sql`) are created.

Optional: statistics
====================

Job views, application clicks and filter usage are recorded out of the box and
shown in the module group :guilabel:`Jobs`. See :ref:`tracking` for what is
stored, the route enhancer entry for the apply link, and the switch in the
extension configuration that turns the feature off.
