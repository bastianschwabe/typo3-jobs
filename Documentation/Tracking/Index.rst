..  _tracking:

=======================
Tracking and statistics
=======================

The extension records three things in the frontend and shows them in the
backend: how often each job is viewed, how often the application link is
clicked, and which filter combinations visitors use.

What is recorded
================

..  t3-field-list-table::
    :header-rows: 1

    -   :Table: Table
        :When: Written when
        :What: One row per

    -   :Table: :sql:`tx_jobs_view_statistic`
        :When: A job detail page is delivered with status 200
        :What: Frontend session and job. A counter grows with every further
               view by the same session, so *visitors* (rows) and *views*
               (counter sum) are both available.

    -   :Table: :sql:`tx_jobs_apply_statistic`
        :When: The :guilabel:`Apply now` link is clicked
        :What: Click, with a *type* (``URL``, ``EMAIL``, ``FORM``) and a
               *status*. The proxy always writes ``STARTED``; ``COMPLETED`` and
               ``ABORTED`` are reserved for an own application form.

    -   :Table: :sql:`tx_jobs_filter_statistic`
        :When: A visitor submits the filter form with at least one value set
               (first page only)
        :What: List request, with every filter value, the number of results and
               a hash of the combination. Identical combinations are grouped in
               the backend by that hash. Plain list views and the plugin's own
               pre-selection are not recorded.

None of the tables has TCA. They are defined in :file:`ext_tables.sql`, written
by the frontend and read by the statistics module only.

Switching it off
================

The whole feature hangs on one switch in the extension configuration
(:guilabel:`Admin Tools > Settings > Extension Configuration > jobs`):

..  confval:: tracking.enabled
    :type: boolean
    :default: 1

    When disabled, nothing is written, visitors get no session cookie from
    this extension, and the apply proxy still redirects but does not count.
    The backend modules and the dashboard widget stay reachable: their content
    is greyed out and a notice with a link to the settings explains why.

Visitors are identified by the TYPO3 frontend session. Anonymous sessions are
normally not persisted; the extension therefore stores a marker in the session
on the first tracked request, which makes TYPO3 send the ``fe_typo_user``
cookie. Only a SHA-256 hash of the session identifier is stored, never the
identifier itself, no IP address and no user agent. Requests of logged-in
backend users are not counted, so editors previewing a job do not skew the
numbers.

..  note::
    The session cookie means the detail page response carries a ``Set-Cookie``
    header on the first view. TYPO3's page cache is unaffected; a reverse
    proxy that refuses to cache responses with cookies will serve those
    requests uncached.

How views are counted
=====================

The detail plugin is cacheable, so its controller only runs on a cache miss.
Views are therefore counted by a PSR-15 middleware
(:php:`BastianSchwabe\Jobs\Middleware\JobViewTracker`) that runs on every
frontend request, reads the job argument the route enhancer decoded, and counts
once the page was delivered successfully. Translations count towards their
default-language record.

The apply proxy
===============

The :guilabel:`Apply now` link no longer points at the application URL or
e-mail address directly. It points at the ``apply`` action of the detail
plugin, which records the click and answers with a redirect to the real target
(``302``; for an e-mail address a ``mailto:`` link). The action is registered
as non-cacheable, so every click is seen while the detail page itself stays
cached.

For speaking URLs, add the action to the route enhancer of the detail plugin:

..  code-block:: yaml
    :caption: config/sites/<site>/config.yaml

    routeEnhancers:
      JobsDetail:
        type: Extbase
        extension: Jobs
        plugin: Show
        routes:
          - routePath: '/{job-slug}'
            _controller: 'Job::show'
            _arguments:
              job-slug: job
          - routePath: '/{job-slug}/apply'
            _controller: 'Job::apply'
            _arguments:
              job-slug: job
        defaultController: 'Job::show'
        aspects:
          job-slug:
            type: PersistedAliasMapper
            tableName: tx_jobs_domain_model_job
            routeFieldName: slug

Without the second route the link still works, but carries the action as query
parameters.

Backend modules
===============

The module group :guilabel:`Jobs` appears in the module menu with two entries.
Both list every job in the installation, regardless of storage folder, and
need no page tree.

:guilabel:`Jobs > Jobs`
    One table with title, reference, level, field of work, locations, dates
    and access state. The filter at the top switches between all, active and
    inactive jobs; *active* is decided by the access fields (hidden, start and
    stop time) alone. A passed :guilabel:`Valid through` date is shown as an
    extra badge but does not change the access state. The right-most column
    shows the view count and links to the statistics of that job.

:guilabel:`Jobs > Statistics`
    Three views, switched in the doc header:

    *   :guilabel:`Jobs` — the five most viewed and the five most applied-to
        jobs as bar charts, followed by a table with views, visitors,
        applications, completed applications and the conversion rate per job.
        Jobs whose record has been deleted keep their numbers and are marked
        as such.
    *   :guilabel:`Filters` — every distinct filter combination, how often it
        was used, the average number of results and when it was used last.
    *   :guilabel:`Functions` — :guilabel:`Reset all statistics` empties all
        three tables after a confirmation. The job records are not touched.

Dashboard widget
================

With `EXT:dashboard` installed, the widget :guilabel:`Job statistics` in the
group :guilabel:`Jobs` shows the same two charts as the statistics module and
links to it. The widget is registered only when the dashboard is present, so
the extension does not depend on it.
