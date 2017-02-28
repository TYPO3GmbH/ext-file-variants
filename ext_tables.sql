CREATE TABLE sys_file (
	# Language fields
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob NOT NULL,
	l10n_state text,

	KEY language (l10n_parent,sys_language_uid)
);

CREATE TABLE sys_file_metadata (
	language_variant VARCHAR(240) DEFAULT '' NOT NULL
);
