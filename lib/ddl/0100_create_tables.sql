/*================================================================================*/
/* DDL SCRIPT                                                                     */
/*================================================================================*/
/*  Title    : Plaisio: Core Session                                              */
/*  FileName : session-core.ecm                                                   */
/*  Platform : MySQL 5.6                                                          */
/*  Version  :                                                                    */
/*  Date     : vrijdag 7 augustus 2020                                            */
/*================================================================================*/
/*================================================================================*/
/* CREATE TABLES                                                                  */
/*================================================================================*/

CREATE TABLE ABC_AUTH_SESSION (
  ses_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  cmp_id SMALLINT UNSIGNED NOT NULL,
  lan_id TINYINT UNSIGNED NOT NULL,
  usr_id INT UNSIGNED NOT NULL,
  ses_session_token VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ses_csrf_token VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ses_last_request BIGINT UNSIGNED NOT NULL,
  ses_has_flash_message BOOL DEFAULT 0 NOT NULL,
  ses_data LONGBLOB,
  CONSTRAINT PK_ABC_AUTH_SESSION PRIMARY KEY (ses_id)
);

/*
COMMENT ON COLUMN ABC_AUTH_SESSION.ses_has_flash_message
If and only if 1 there are one or more flash messages.
*/

/*
COMMENT ON COLUMN ABC_AUTH_SESSION.ses_data
The optional additional session data.
*/

CREATE TABLE ABC_AUTH_SESSION_NAMED (
  ses_id INT UNSIGNED NOT NULL,
  cmp_id SMALLINT UNSIGNED NOT NULL,
  ans_name VARCHAR(128) NOT NULL,
  ans_data LONGBLOB,
  CONSTRAINT PK_ABC_AUTH_SESSION_NAMED PRIMARY KEY (ses_id, ans_name)
);

CREATE TABLE ABC_AUTH_SESSION_NAMED_LOCK (
  ses_id INT NOT NULL,
  cmp_id SMALLINT UNSIGNED NOT NULL,
  ans_name VARCHAR(128) NOT NULL,
  CONSTRAINT PK_ABC_AUTH_SESSION_NAMED_LOCK PRIMARY KEY (ses_id, ans_name)
);

/*================================================================================*/
/* CREATE INDEXES                                                                 */
/*================================================================================*/

CREATE UNIQUE INDEX IX_ABC_AUTH_SESSION1 ON ABC_AUTH_SESSION (ses_session_token);
