..  _configuration:

=============
Configuration
=============

Site set settings
=================

All settings live in the :guilabel:`Jobs` site set and can be edited per site in
:guilabel:`Site Management > Settings`.

..  confval-menu::
    :name: jobs-settings

    ..  confval:: jobs.storagePid
        :type: string
        :default: (empty)

        Comma separated page IDs the job records are stored on.

    ..  confval:: jobs.detailPid
        :type: int
        :default: 0

        Page containing the detail plugin. Used to link from the list.

    ..  confval:: jobs.itemsPerPage
        :type: int
        :default: 10

        Number of jobs per page in the list view.

    ..  confval:: jobs.organization.name
        :type: string
        :default: (empty)

        Fallback :sql:`hiringOrganization` name, used when a job has no company
        profile assigned. Google requires this property, so setting it is
        strongly recommended even if you always assign a profile.

    ..  confval:: jobs.organization.url
        :type: string
        :default: (empty)

        Fallback organization URL.

    ..  confval:: jobs.organization.logo
        :type: string
        :default: (empty)

        Absolute URL of the company logo.

Plugin options
==============

Both plugins carry a FlexForm that overrides the site settings per instance.

**Jobs: List**

*   :guilabel:`Detail page` — overrides :confval:`jobs.detailPid`.
*   :guilabel:`Jobs per page` — ``0`` keeps the value from the site settings.
*   :guilabel:`Restrict to level / field of work / location` — pre-filters the
    list, for example to show only one department on a landing page.

**Jobs: Detail**

*   :guilabel:`List page` — target of the back link.

Filtering
=========

The list filter is a plain :html:`GET` form. The query string stays readable and
cacheable, and the list works without JavaScript:

..  code-block:: text

    /jobs?tx_jobs_list[demand][occupationalField]=3&tx_jobs_list[demand][remoteOnly]=1

..  important::
    Because of this the list action is registered as **non-cacheable** and its
    parameters are excluded from the cache hash. TYPO3 rejects cache-relevant
    query parameters that carry no ``cHash``, and a plain :html:`GET` form
    cannot produce one. The page itself stays cached; only the plugin is
    rendered per request.

    The alternative would be a route enhancer that maps each filter into the
    path, which keeps the list cacheable at the price of a much larger route
    configuration. That is a worthwhile addition once the filter set is stable.

Overriding templates
====================

Add your own paths with a higher index in your site package:

..  code-block:: typoscript

    plugin.tx_jobs.view {
        templateRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/Jobs/Templates/
        partialRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/Jobs/Partials/
        layoutRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/Jobs/Layouts/
    }
