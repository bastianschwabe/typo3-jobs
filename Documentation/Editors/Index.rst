..  _editors:

===========
For editors
===========

Record types
============

..  list-table::
    :header-rows: 1

    *   -   Record
        -   Purpose
    *   -   :guilabel:`Job`
        -   The posting itself.
    *   -   :guilabel:`Level`
        -   Seniority, for example *Junior* or *Team Lead*. Carries the months
            of experience that jobs on this level inherit.
    *   -   :guilabel:`Field of work`
        -   Department or discipline. Carries the default industry and
            occupational category.
    *   -   :guilabel:`Location`
        -   Address and coordinates. Several can be assigned to one job.
    *   -   :guilabel:`Contact person`
        -   Shown on the detail page. Not part of the structured data.
    *   -   :guilabel:`Company profile`
        -   The hiring company. The first one assigned to a job becomes its
            :sql:`hiringOrganization`.

Creating a job
==============

#.  Fill in :guilabel:`Job title`, :guilabel:`Description` and
    :guilabel:`Published on`. These are required by Google.
#.  Assign at least one :guilabel:`Location`, or tick
    :guilabel:`Fully remote` and choose the :guilabel:`Eligible countries`.
#.  Assign a :guilabel:`Company profile`.
#.  Pick the :guilabel:`Employment type`. Several may apply.

Fields worth filling in
=======================

These are optional but visibly improve how the posting appears in search:

:guilabel:`Valid through`
    The last day the job is listed. Leave it empty for open-ended postings —
    they then never expire. Filled in, the job disappears from the list on its
    own the day after.

:guilabel:`Salary`
    Either a fixed amount or a from/to range, together with a currency and a
    period. An incomplete salary is skipped entirely rather than emitted
    half-filled.

:guilabel:`Job ID`
    Your internal reference number. Helps search engines recognise the same
    posting across sites.

:guilabel:`Education level`
    Search engines only evaluate the fixed vocabulary in this select. The free
    text field below it is for human readers.

..  warning::
    Only tick :guilabel:`Direct application` if applicants can really complete
    the application on this website without a further sign-in. Claiming it
    falsely is a policy violation.

Job titles
==========

Put the job title and nothing else into :guilabel:`Job title` — no reference
codes, locations, dates or salaries. Those belong in their own fields, and a
title carrying them is rejected by Google.
