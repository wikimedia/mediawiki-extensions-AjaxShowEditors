CREATE TABLE /*_*/editings (
  editings_page int(8) NOT NULL,
  editings_actor bigint unsigned NOT NULL,
  editings_started char(14) NOT NULL,
  editings_touched char(14) NOT NULL,
  PRIMARY KEY (editings_page, editings_actor),
  KEY editings_page (editings_page),
  KEY editings_page_started (editings_page, editings_actor, editings_started)
) ENGINE=MEMORY;