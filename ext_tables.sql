#
# Hand-written schema. Everything not listed here is derived from TCA.
#
# Latitude and longitude need eight decimals to be worth anything. TCA type
# "number" with format "decimal" cannot express that: DataHandler rounds every
# value to two decimals and the generated column is decimal(10,2).
#
CREATE TABLE tx_jobs_domain_model_location (
	latitude decimal(11,8) DEFAULT NULL,
	longitude decimal(11,8) DEFAULT NULL
);

#
# Tracking tables. They carry no TCA on purpose: the rows are written by the
# frontend and read by the statistics module, never edited by hand. Without TCA
# the Core does not generate any columns, so the full definition lives here.
#

#
# One row per frontend session and job. The counter grows with every view the
# same session makes, so "sessions" and "views" can both be read from it.
#
CREATE TABLE tx_jobs_view_statistic (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	job int(11) unsigned DEFAULT '0' NOT NULL,
	session_hash varchar(64) DEFAULT '' NOT NULL,
	counter int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY job_session (job,session_hash),
	KEY job (job)
);

#
# One row per click on the "apply" proxy. "type" says where the visitor was
# sent (URL, EMAIL, FORM), "status" how far the application got. Every row
# starts as STARTED; later steps of an own application form can add rows with
# COMPLETED or ABORTED.
#
CREATE TABLE tx_jobs_apply_statistic (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	job int(11) unsigned DEFAULT '0' NOT NULL,
	session_hash varchar(64) DEFAULT '' NOT NULL,
	type varchar(32) DEFAULT '' NOT NULL,
	status varchar(32) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY job (job),
	KEY job_status (job,status)
);

#
# One row per rendered job list. The filter values are stored verbatim and
# additionally hashed, so identical combinations can be grouped with one
# GROUP BY in the statistics module.
#
CREATE TABLE tx_jobs_filter_statistic (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	session_hash varchar(64) DEFAULT '' NOT NULL,
	filter_hash varchar(40) DEFAULT '' NOT NULL,
	level int(11) unsigned DEFAULT '0' NOT NULL,
	occupational_field int(11) unsigned DEFAULT '0' NOT NULL,
	location int(11) unsigned DEFAULT '0' NOT NULL,
	employment_type varchar(32) DEFAULT '' NOT NULL,
	remote_only smallint(1) unsigned DEFAULT '0' NOT NULL,
	search varchar(255) DEFAULT '' NOT NULL,
	result_count int(11) unsigned DEFAULT '0' NOT NULL,
	language int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY filter_hash (filter_hash)
);
