..  _development:

===========
Development
===========

The repository carries a complete TYPO3 instance for development: a DDEV
environment, a development-only site package and a seed command that builds the
demo content. Nothing of it ships with the extension — :file:`Build/`,
:file:`Tests/` and :file:`config/` are marked ``export-ignore``.

Getting started
===============

..  code-block:: bash

    ddev start
    ddev typo3-install

..  note::
    The first :bash:`ddev start` may ask for your password once, to add the
    project hostname to :file:`/etc/hosts`. Run it in an interactive terminal.

:bash:`ddev typo3-install` is a full reset. It drops the database, installs
TYPO3, applies the committed site configuration, seeds the demo content and
prints the URLs and the backend login. Afterwards a plain :bash:`ddev start` is
enough — the database persists between sessions.

What the instance contains
==========================

..  list-table::
    :header-rows: 1

    *   -   URL
        -   Contents
    *   -   ``/``
        -   Home page with a text element
    *   -   ``/jobs``
        -   List plugin, English
    *   -   ``/de/stellenangebote``
        -   List plugin, German
    *   -   ``/jobs/detail/<slug>``
        -   Detail plugin including its JSON-LD, reached through a route enhancer
    *   -   ``/typo3``
        -   Backend, ``test`` / ``test``

The demo records are deliberately varied, so every branch of the JSON-LD builder
is reachable by hand:

*   **Backend Developer** — location, salary range, expiry date, education level
*   **Marketing Manager** — fixed salary, no expiry date, two employment types
*   **Remote UX Designer** — no location at all, exercising ``TELECOMMUTE`` and
    ``applicantLocationRequirements``
*   **Working Student Frontend** — deliberately left untranslated, so the German
    language fallback is visible in the list

Why a seed command instead of a database dump
=============================================

The page tree, the plugins and the records are created through DataHandler in
:file:`Build/TestSite/Classes/Command/SeedCommand.php`. Slugs, relations and
translations therefore end up exactly as they would if an editor had clicked
them together.

A committed database dump would be faster to restore but would rot: every new
field would need a re-export, and reviewing a change to the demo data would mean
reading a binary diff. The seed is a readable array that grows with the model.

For a fast reset during a working session, take a snapshot right after the
install:

..  code-block:: bash

    ddev snapshot --name jobs-clean
    ddev snapshot restore jobs-clean

Resetting after a model change
==============================

Whenever TCA changes, run :bash:`ddev typo3-install` again. It clears
:file:`var/cache` before the setup, which matters: the TYPO3 cache identifier
does not track the contents of TCA files, so a stale cache would make the seed
write records against the previous schema.

..  note::
    The password ``test`` does not satisfy TYPO3's default password policy. The
    development site package relaxes the policy, but only when ``TYPO3_CONTEXT``
    is ``Development``, and the package itself is a ``require-dev`` dependency
    that is excluded from the released archive.

Full reference
==============

:file:`DEVELOPMENT.md` in the repository root documents the setup in detail: how
:file:`.ddev`, :file:`config` and :file:`Build` fit together, what every step of
the install command is for, how to add records, pages, content elements and
languages, and the TYPO3 quirks the instance uncovered.

Tests
=====

..  code-block:: bash

    ddev composer ci

This runs PHP lint, coding standards, PHPStan level 8, unit tests and functional
tests. Functional tests use SQLite by default and additionally run against
MariaDB in CI.
