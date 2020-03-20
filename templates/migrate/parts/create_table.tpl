CREATE TABLE <!----table_name----> (
  id int(9) NOT NULL AUTO_INCREMENT,
  <!----columns---->
  created_at datetime NOT NULL,
  modified_at datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY index_<!----table_name---->_<!----pk_name----> (<!----pk_name---->)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
