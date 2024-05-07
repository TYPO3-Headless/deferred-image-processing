CREATE TABLE sys_file_processedfile_queue (
  uid int(11) NOT NULL auto_increment,

  public_url text,
  storage int(11) DEFAULT '0' NOT NULL,
  original int(11) DEFAULT '0' NOT NULL,
  task_type varchar(200) DEFAULT '' NOT NULL,
  checksum char(32) DEFAULT '' NOT NULL,
  configuration blob,

  primary key (uid),

  key public_url (public_url(255)),
  key properties (storage, original, task_type(100), checksum)
);
