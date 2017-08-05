/*================================================================================*/
/* DDL SCRIPT                                                                     */
/*================================================================================*/
/*  Title    :                                                                    */
/*  FileName : abc-session-core.ecm                                               */
/*  Platform : MySQL 5.6                                                          */
/*  Version  : Concept                                                            */
/*  Date     : zaterdag 5 augustus 2017                                           */
/*================================================================================*/
/*================================================================================*/
/* CREATE TABLES                                                                  */
/*================================================================================*/

CREATE TABLE ABC_AUTH_SESSION (
  ses_id INT AUTO_INCREMENT NOT NULL,
  cmp_id SMALLINT UNSIGNED NOT NULL,
  lan_id TINYINT UNSIGNED NOT NULL,
  usr_id INT UNSIGNED NOT NULL,
  ses_session_token VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ses_csrf_token VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ses_last_request BIGINT UNSIGNED NOT NULL,
  ses_data BLOB,
  CONSTRAINT PK_ABC_AUTH_SESSION PRIMARY KEY (ses_id)
);

/*================================================================================*/
/* CREATE INDEXES                                                                 */
/*================================================================================*/

CREATE UNIQUE INDEX IX_ABC_AUTH_SESSION1 ON ABC_AUTH_SESSION (ses_session_token);
