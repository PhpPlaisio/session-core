<?php

namespace SetBased\Abc\Session\Test;

use SetBased\Stratum\MySql\DataLayer;

/**
 * The data layer.
 */
class TestDataLayer extends DataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets session data for an anonymous user.
   *
   * @param int $pCmpId The ID of the company.
   *                    smallint(5) unsigned
   *
   * @return array
   */
  public function abcAuthSessionFakeSession(?int $pCmpId): array
  {
    return $this->executeRow1('call abc_auth_session_fake_session('.$this->quoteInt($pCmpId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets a session based a session token.
   *
   * @param int    $pCmpId           The ID of the company of the user (safe guard).
   *                                 smallint(5) unsigned
   * @param string $pSesSessionToken The session token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   *
   * @return array|null
   */
  public function abcAuthSessionGetSession(?int $pCmpId, ?string $pSesSessionToken): ?array
  {
    return $this->executeRow0('call abc_auth_session_get_session('.$this->quoteInt($pCmpId).','.$this->quoteString($pSesSessionToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Marks a user as successfully logged in in the current session.
   *
   * @param int    $pCmpId           The ID of the company (safe guard).
   *                                 smallint(5) unsigned
   * @param int    $pSesId           The ID of the session.
   *                                 int(10) unsigned
   * @param int    $pUsrId           The ID of the user.
   *                                 int(10) unsigned
   * @param string $pSesSessionToken The new session token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   * @param string $pSesCsrfToken    The new CSRF token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   *
   * @return array
   */
  public function abcAuthSessionLogin(?int $pCmpId, ?int $pSesId, ?int $pUsrId, ?string $pSesSessionToken, ?string $pSesCsrfToken): array
  {
    return $this->executeRow1('call abc_auth_session_login('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteInt($pUsrId).','.$this->quoteString($pSesSessionToken).','.$this->quoteString($pSesCsrfToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs the current user out of the current session.
   *
   * @param int    $pCmpId           The ID of the company (safe guard).
   *                                 smallint(5) unsigned
   * @param int    $pSesId           The ID of the session.
   *                                 int(10) unsigned
   * @param string $pSesSessionToken The new session token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   * @param string $pSesCsrfToken    The new CSRF token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   *
   * @return array
   */
  public function abcAuthSessionLogout(?int $pCmpId, ?int $pSesId, ?string $pSesSessionToken, ?string $pSesCsrfToken): array
  {
    return $this->executeRow1('call abc_auth_session_logout('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteString($pSesSessionToken).','.$this->quoteString($pSesCsrfToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Deletes a named section of a session.
   *
   * @param int    $pCmpId   The ID of the company (safe guard).
   *                         smallint(5) unsigned
   * @param int    $pSesId   The ID of the session.
   *                         int(10) unsigned
   * @param string $pAnsName The name of the named section.
   *                         varchar(128) character set latin1 collation latin1_swedish_ci
   *
   * @return int
   */
  public function abcAuthSessionNamedSectionDelete(?int $pCmpId, ?int $pSesId, ?string $pAnsName): int
  {
    return $this->executeNone('call abc_auth_session_named_section_delete('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteString($pAnsName).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects a named section of a session.
   *
   * @param int    $pCmpId   The ID of the company (safe guard).
   *                         smallint(5) unsigned
   * @param int    $pSesId   The ID of the session.
   *                         int(10) unsigned
   * @param string $pAnsName The name of the named section.
   *                         varchar(128) character set latin1 collation latin1_swedish_ci
   * @param int    $pMode    The lock mode for getting the named section.
   *                         int(11)
   *
   * @return array|null
   */
  public function abcAuthSessionNamedSectionGet(?int $pCmpId, ?int $pSesId, ?string $pAnsName, ?int $pMode): ?array
  {
    return $this->executeRow0('call abc_auth_session_named_section_get('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteString($pAnsName).','.$this->quoteInt($pMode).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates a named section of a session.
   *
   * @param int    $pCmpId   The ID of the company.
   *                         smallint(5) unsigned
   * @param int    $pSesId   The ID of the session.
   *                         int(10) unsigned
   * @param string $pAnsName The name of the named section.
   *                         varchar(128) character set latin1 collation latin1_swedish_ci
   * @param string $pAnsData The data of the named section.
   *                         longblob
   *
   * @return int
   */
  public function abcAuthSessionNamedSectionUpdate(?int $pCmpId, ?int $pSesId, ?string $pAnsName, ?string $pAnsData)
  {
    $query = 'call abc_auth_session_named_section_update('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteString($pAnsName).',?)';
    $stmt  = $this->mysqli->prepare($query);
    if (!$stmt) $this->mySqlError('mysqli::prepare');

    $null = null;
    $b = $stmt->bind_param('b', $null);
    if (!$b) $this->mySqlError('mysqli_stmt::bind_param');

    $this->getMaxAllowedPacket();

    $n = strlen($pAnsData);
    $p = 0;
    while ($p<$n)
    {
      $b = $stmt->send_long_data(0, substr($pAnsData, $p, $this->chunkSize));
      if (!$b) $this->mySqlError('mysqli_stmt::send_long_data');
      $p += $this->chunkSize;
    }

    if ($this->logQueries)
    {
      $time0 = microtime(true);

      $b = $stmt->execute();
      if (!$b) $this->mySqlError('mysqli_stmt::execute');

      $this->queryLog[] = ['query' => $query,
      'time'  => microtime(true) - $time0];
    }
    else
    {
      $b = $stmt->execute();
      if (!$b) $this->mySqlError('mysqli_stmt::execute');
    }

    $ret = $this->mysqli->affected_rows;

    $stmt->close();
    if ($this->mysqli->more_results()) $this->mysqli->next_result();

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Restarts a session, i.e. all data stored in the session will be deleted.
   *
   * @param int    $pCmpId           The ID of the company of the user (safe guard).
   *                                 smallint(5) unsigned
   * @param int    $pSesId           The ID of the session.
   *                                 int(10) unsigned
   * @param int    $pLanId           The ID of the language of the session.
   *                                 tinyint(3) unsigned
   * @param string $pSesSessionToken The new session token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   * @param string $pSesCsrfToken    The new CSRF token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   *
   * @return array
   */
  public function abcAuthSessionRestartSession(?int $pCmpId, ?int $pSesId, ?int $pLanId, ?string $pSesSessionToken, ?string $pSesCsrfToken): array
  {
    return $this->executeRow1('call abc_auth_session_restart_session('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteInt($pLanId).','.$this->quoteString($pSesSessionToken).','.$this->quoteString($pSesCsrfToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Starts a new session.
   *
   * @param int    $pCmpId           The ID of the company of the user (safe guard).
   *                                 smallint(5) unsigned
   * @param int    $pLanId           The ID of the language of the session.
   *                                 tinyint(3) unsigned
   * @param string $pSesSessionToken The session token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   * @param string $pSesCsrfToken    The CSRF token.
   *                                 varchar(64) character set latin1 collation latin1_swedish_ci
   *
   * @return array
   */
  public function abcAuthSessionStartSession(?int $pCmpId, ?int $pLanId, ?string $pSesSessionToken, ?string $pSesCsrfToken): array
  {
    return $this->executeRow1('call abc_auth_session_start_session('.$this->quoteInt($pCmpId).','.$this->quoteInt($pLanId).','.$this->quoteString($pSesSessionToken).','.$this->quoteString($pSesCsrfToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates the language of a session.
   *
   * @param int $pCmpId The ID of the company (safe guard).
   *                    smallint(5) unsigned
   * @param int $pSesId The ID of the session.
   *                    int(10) unsigned
   * @param int $pLanId The ID of the new language.
   *                    tinyint(3) unsigned
   *
   * @return int
   */
  public function abcAuthSessionUpdateLanId(?int $pCmpId, ?int $pSesId, ?int $pLanId): int
  {
    return $this->executeNone('call abc_auth_session_update_lan_id('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates a session.
   *
   * @param int    $pCmpId   The ID of the company (safe guard).
   *                         smallint(5) unsigned
   * @param int    $pSesId   The ID of the session.
   *                         int(10) unsigned
   * @param string $pSesData The new additional data of the session.
   *                         longblob
   *
   * @return int
   */
  public function abcAuthSessionUpdateSession(?int $pCmpId, ?int $pSesId, ?string $pSesData)
  {
    $query = 'call abc_auth_session_update_session('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).',?)';
    $stmt  = $this->mysqli->prepare($query);
    if (!$stmt) $this->mySqlError('mysqli::prepare');

    $null = null;
    $b = $stmt->bind_param('b', $null);
    if (!$b) $this->mySqlError('mysqli_stmt::bind_param');

    $this->getMaxAllowedPacket();

    $n = strlen($pSesData);
    $p = 0;
    while ($p<$n)
    {
      $b = $stmt->send_long_data(0, substr($pSesData, $p, $this->chunkSize));
      if (!$b) $this->mySqlError('mysqli_stmt::send_long_data');
      $p += $this->chunkSize;
    }

    if ($this->logQueries)
    {
      $time0 = microtime(true);

      $b = $stmt->execute();
      if (!$b) $this->mySqlError('mysqli_stmt::execute');

      $this->queryLog[] = ['query' => $query,
      'time'  => microtime(true) - $time0];
    }
    else
    {
      $b = $stmt->execute();
      if (!$b) $this->mySqlError('mysqli_stmt::execute');
    }

    $ret = $this->mysqli->affected_rows;

    $stmt->close();
    if ($this->mysqli->more_results()) $this->mysqli->next_result();

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all language codes as map from language code to language ID.
   *
   * @return array
   */
  public function abcBabelCoreInternalCodeMap(): array
  {
    $result = $this->query('call abc_babel_core_internal_code_map()');
    $ret = [];
    while (($row = $result->fetch_array(MYSQLI_NUM))) $ret[$row[0]] = $row[1];
    $result->free();
    if ($this->mysqli->more_results()) $this->mysqli->next_result();

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the details of a language.
   *
   * @param int $pLanId The ID of the language.
   *                    tinyint(3) unsigned
   *
   * @return array
   */
  public function abcBabelCoreLanguageGetDetails(?int $pLanId): array
  {
    return $this->executeRow1('call abc_babel_core_language_get_details('.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the value of a text in a language.
   *
   * @param int $pTxtId The ID of the text.
   *                    smallint(5) unsigned
   * @param int $pLanId The ID of the language.
   *                    tinyint(3) unsigned
   *
   * @return string
   */
  public function abcBabelCoreTextGetText(?int $pTxtId, ?int $pLanId): string
  {
    return $this->executeSingleton1('call abc_babel_core_text_get_text('.$this->quoteInt($pTxtId).','.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the value of a word in a language.
   *
   * @param int $pWrdId The ID of the word.
   *                    smallint(5) unsigned
   * @param int $pLanId The ID of the language.
   *                    tinyint(3) unsigned
   *
   * @return string
   */
  public function abcBabelCoreWordGetWord(?int $pWrdId, ?int $pLanId): string
  {
    return $this->executeSingleton1('call abc_babel_core_word_get_word('.$this->quoteInt($pWrdId).','.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
