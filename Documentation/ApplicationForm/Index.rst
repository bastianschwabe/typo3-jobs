..  _application-form:

================
Application form
================

The :guilabel:`Apply now` button can lead to an application form built with
EXT:form instead of to the job's application URL or e-mail address. The job is
handed over to the form, and the application is sent to the address of that
job.

EXT:form is optional. Without it the extension works as before, and the
setting below must stay empty.

Setting it up
=============

#.  Put a form on a page: either the one the extension ships — it is offered
    read-only in the form manager — or one you build yourself with the form
    editor.
#.  Set :confval:`jobs.applicationFormPid` to that page. From then on the form
    wins: the apply button leads there for every job, whatever the job's own
    application URL or e-mail address says.
#.  If you build the form yourself, add the elements and the option described
    below to the form definition. The shipped form already carries them.

What the form needs
===================

..  t3-field-list-table::
    :header-rows: 1

    -   :Name: Name
        :Purpose: Purpose

    -   :Name: Hidden element :yaml:`jobUid`
        :Purpose: Carries the uid of the job. Required: without it the form
                  behaves like a plain form.

    -   :Name: Text element :yaml:`jobTitle`
        :Purpose: Optional. Pre-filled with "Title (reference)". Visitors may
                  change it, for example to apply without a concrete job.

    -   :Name: Option :yaml:`jobsRecipient: true`
        :Purpose: Set on the email finisher that delivers the application. Its
                  :yaml:`recipients` are replaced by the address of the job.

    -   :Name: Option :yaml:`jobsReplyTo: true`
        :Purpose: Set on the email finisher that confirms to the applicant. Its
                  :yaml:`replyToRecipients` are replaced by the address of the
                  job, so that a reply reaches the same person. The applicant
                  stays the recipient.

..  code-block:: yaml
    :caption: Excerpt of a form definition

    finishers:
      -
        identifier: EmailToSender
        options:
          recipients:
            '{email}': '{firstName} {lastName}'
          replyToRecipients:
            careers@example.org: ''
          jobsReplyTo: true
      -
        identifier: EmailToReceiver
        options:
          subject: 'Application received: {jobTitle}'
          # Fallback: used when the form is opened without a job.
          recipients:
            careers@example.org: 'Careers'
          jobsRecipient: true
    renderables:
      -
        type: Page
        identifier: page-1
        renderables:
          -
            type: Hidden
            identifier: jobUid
          -
            type: Text
            identifier: jobTitle
            label: Job

The shipped form
================

The extension ships one complete form,
`job-application.form.yaml <https://github.com/bastianschwabe/typo3-jobs/blob/main/Resources/Private/Forms/job-application.form.yaml>`__,
and reads it through a form set of its own,
`Configuration/Form/ApplicationForm/config.yaml <https://github.com/bastianschwabe/typo3-jobs/blob/main/Configuration/Form/ApplicationForm/config.yaml>`__.
EXT:form discovers that set by itself since TYPO3 v14.2, so the form is offered
in the form manager and can be selected in the form plugin as it is. It is
read-only, because it ships with the extension: duplicate it if you want to
edit it in the form editor.

Everything in it is ordinary EXT:form, including the check against robots,
which the framework's honeypot performs by default.

Translations
============

The definition is English, and it is translated the way EXT:form translates any
form: by translation key, taken from the form identifier ``job-application`` and
the element identifiers. The keys live in
`Resources/Private/Language/Form/locallang.xlf <https://github.com/bastianschwabe/typo3-jobs/blob/main/Resources/Private/Language/Form/locallang.xlf>`__
with its
`de.locallang.xlf <https://github.com/bastianschwabe/typo3-jobs/blob/main/Resources/Private/Language/Form/de.locallang.xlf>`__,
which the form set adds to its ``translationFiles``. A German page therefore
shows German labels, buttons and mail subjects without a second form definition.
Another language needs another XLF file, added to the same list.

Which address receives the application
======================================

#.  The :guilabel:`Application e-mail` of the job.
#.  Otherwise the e-mail address of the first contact of the job that has one.
#.  Otherwise the recipients written in the form definition.

The form definition also applies when the form is opened without a job, or with
a job that does not exist, is hidden or has expired. Editors never see these
addresses in the page source, and a visitor cannot change them: only the uid
travels with the form, and the address is looked up again on the server when
the application is sent.

The click is recorded with the type ``FORM`` in the :ref:`statistics <tracking>`.
