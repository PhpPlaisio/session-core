#!/bin/bash -e -x

cat test/ddl/0010_create_database.sql                         | mysql -v -u root      -h 127.0.0.1
cat test/ddl/0020_create_user.sql                             | mysql -v -u root      -h 127.0.0.1
cat vendor/plaisio/db-company/lib/ddl/0100_create_tables.sql  | mysql -v -u root test -h 127.0.0.1
cat vendor/plaisio/db-profile/lib/ddl/0100_create_tables.sql  | mysql -v -u root test -h 127.0.0.1
cat vendor/plaisio/babel-core/lib/ddl/0100_create_tables.sql  | mysql -v -u root test -h 127.0.0.1
cat vendor/plaisio/db-user/lib/ddl/0100_create_tables.sql     | mysql -v -u root test -h 127.0.0.1
cat lib/ddl/0100_create_tables.sql                            | mysql -v -u root test -h 127.0.0.1
cat test/ddl/0200_abc_auth_company.sql                        | mysql -v -u root test -h 127.0.0.1
cat test/ddl/0200_abc_babel_word_group.sql                    | mysql -v -u root test -h 127.0.0.1
cat test/ddl/0210_abc_auth_profile.sql                        | mysql -v -u root test -h 127.0.0.1
cat test/ddl/0210_abc_babel_word.sql                          | mysql -v -u root test -h 127.0.0.1
cat test/ddl/0220_abc_babel_language.sql                      | mysql -v -u root test -h 127.0.0.1
cat test/ddl/0230_abc_auth_user.sql                           | mysql -v -u root test -h 127.0.0.1

./bin/stratum -vv stratum test/etc/stratum.ini

./bin/phpunit
