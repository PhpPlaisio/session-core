<?php
declare(strict_types=1);

namespace Plaisio\Session\Test;

use SetBased\Stratum\MySql\Exception\MySqlQueryErrorException;
use SetBased\Stratum\Middle\Exception\ResultException;
use SetBased\Stratum\MySql\Exception\MySqlDataLayerException;
use SetBased\Stratum\MySql\MySqlDataLayer;

/**
 * The data layer.
 */
class TestDataLayer extends MySqlDataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all language codes as map from language code to language ID.
   *
   * @return array
   *
   * @throws MySqlQueryErrorException;
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
   * @param int|null $pLanId The ID of the language.
   *                         tinyint(3) unsigned
   *
   * @return array
   *
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcBabelCoreLanguageGetDetails(?int $pLanId): array
  {
    return $this->executeRow1('call abc_babel_core_language_get_details('.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the value of a text in a language.
   *
   * @param int|null $pTxtId The ID of the text.
   *                         smallint(5) unsigned
   * @param int|null $pLanId The ID of the language.
   *                         tinyint(3) unsigned
   *
   * @return string
   *
   * @throws MySqlDataLayerException;
   * @throws ResultException;
   */
  public function abcBabelCoreTextGetText(?int $pTxtId, ?int $pLanId): string
  {
    return $this->executeSingleton1('call abc_babel_core_text_get_text('.$this->quoteInt($pTxtId).','.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the value of a word in a language.
   *
   * @param int|null $pWrdId The ID of the word.
   *                         smallint(5) unsigned
   * @param int|null $pLanId The ID of the language.
   *                         tinyint(3) unsigned
   *
   * @return string
   *
   * @throws MySqlDataLayerException;
   * @throws ResultException;
   */
  public function abcBabelCoreWordGetWord(?int $pWrdId, ?int $pLanId): string
  {
    return $this->executeSingleton1('call abc_babel_core_word_get_word('.$this->quoteInt($pWrdId).','.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Deletes all sessions of a user.
   *
   * @param int|null $pCmpId The ID of the company (safeguard).
   *                         smallint(5) unsigned
   * @param int|null $pUsrId The ID of the user.
   *                         int(10) unsigned
   *
   * @return int
   *
   * @throws MySqlQueryErrorException;
   */
  public function abcSessionCoreDestroyAllSessionsOfUser(?int $pCmpId, ?int $pUsrId): int
  {
    return $this->executeNone('call abc_session_core_destroy_all_sessions_of_user('.$this->quoteInt($pCmpId).','.$this->quoteInt($pUsrId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Deletes all other sessions of the user of a session.
   *
   * @param int|null $pCmpId The ID of the company (safeguard).
   *                         smallint(5) unsigned
   * @param int|null $pSesId The ID of the session.
   *                         int(10) unsigned
   *
   * @return int
   *
   * @throws MySqlQueryErrorException;
   */
  public function abcSessionCoreDestroyOtherSessionsOfUser(?int $pCmpId, ?int $pSesId): int
  {
    return $this->executeNone('call abc_session_core_destroy_other_sessions_of_user('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets session data for an anonymous user.
   *
   * @param int|null $pCmpId The ID of the company.
   *                         smallint(5) unsigned
   *
   * @return array
   *
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreFakeSession(?int $pCmpId): array
  {
    return $this->executeRow1('call abc_session_core_fake_session('.$this->quoteInt($pCmpId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Gets a session based a session token.
   *
   * @param int|null    $pCmpId           The ID of the company of the user (safe guard).
   *                                      smallint(5) unsigned
   * @param string|null $pSesSessionToken The session token.
   *                                      varchar(64) character set ascii collation ascii_general_ci
   *
   * @return array|null
   *
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreGetSession(?int $pCmpId, ?string $pSesSessionToken): ?array
  {
    return $this->executeRow0('call abc_session_core_get_session('.$this->quoteInt($pCmpId).','.$this->quoteString($pSesSessionToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Marks a user as successfully logged in in the current session.
   *
   * @param int|null    $pCmpId           The ID of the company (safe guard).
   *                                      smallint(5) unsigned
   * @param int|null    $pSesId           The ID of the session.
   *                                      int(10) unsigned
   * @param int|null    $pUsrId           The ID of the user.
   *                                      int(10) unsigned
   * @param string|null $pSesSessionToken The new session token.
   *                                      varchar(64) character set ascii collation ascii_general_ci
   * @param string|null $pSesCsrfToken    The new CSRF token.
   *                                      varchar(64) character set ascii collation ascii_general_ci
   *
   * @return array
   *
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreLogin(?int $pCmpId, ?int $pSesId, ?int $pUsrId, ?string $pSesSessionToken, ?string $pSesCsrfToken): array
  {
    return $this->executeRow1('call abc_session_core_login('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteInt($pUsrId).','.$this->quoteString($pSesSessionToken).','.$this->quoteString($pSesCsrfToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs the current user out of the current session.
   *
   * @param int|null    $pCmpId           The ID of the company (safe guard).
   *                                      smallint(5) unsigned
   * @param int|null    $pSesId           The ID of the session.
   *                                      int(10) unsigned
   * @param int|null    $pLanId           The ID of the language of the session.
   *                                      tinyint(3) unsigned
   * @param string|null $pSesSessionToken The new session token.
   *                                      varchar(64) character set ascii collation ascii_general_ci
   * @param string|null $pSesCsrfToken    The new CSRF token.
   *                                      varchar(64) character set ascii collation ascii_general_ci
   *
   * @return array
   *
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreLogout(?int $pCmpId, ?int $pSesId, ?int $pLanId, ?string $pSesSessionToken, ?string $pSesCsrfToken): array
  {
    return $this->executeRow1('call abc_session_core_logout('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteInt($pLanId).','.$this->quoteString($pSesSessionToken).','.$this->quoteString($pSesCsrfToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Deletes a named section of a session.
   *
   * @param int|null    $pCmpId   The ID of the company (safe guard).
   *                              smallint(5) unsigned
   * @param int|null    $pSesId   The ID of the session.
   *                              int(10) unsigned
   * @param string|null $pAnsName The name of the named section.
   *                              varchar(128) character set latin1 collation latin1_swedish_ci
   *
   * @return int
   *
   * @throws MySqlQueryErrorException;
   */
  public function abcSessionCoreNamedSectionDelete(?int $pCmpId, ?int $pSesId, ?string $pAnsName): int
  {
    return $this->executeNone('call abc_session_core_named_section_delete('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteString($pAnsName).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects a named section of a session.
   *
   * @param int|null    $pCmpId   The ID of the company (safe guard).
   *                              smallint(5) unsigned
   * @param int|null    $pSesId   The ID of the session.
   *                              int(10) unsigned
   * @param string|null $pAnsName The name of the named section.
   *                              varchar(128) character set latin1 collation latin1_swedish_ci
   * @param int|null    $pMode    The lock mode for getting the named section.
   *                              int(11)
   *
   * @return array|null
   *
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreNamedSectionGet(?int $pCmpId, ?int $pSesId, ?string $pAnsName, ?int $pMode): ?array
  {
    return $this->executeRow0('call abc_session_core_named_section_get('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteString($pAnsName).','.$this->quoteInt($pMode).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates a named section of a session.
   *
   * @param int|null    $pCmpId   The ID of the company.
   *                              smallint(5) unsigned
   * @param int|null    $pSesId   The ID of the session.
   *                              int(10) unsigned
   * @param string|null $pAnsName The name of the named section.
   *                              varchar(128) character set latin1 collation latin1_swedish_ci
   * @param string|null $pAnsData The data of the named section.
   *                              longblob
   *
   * @return int
   *
   * @throws MySqlDataLayerException;
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreNamedSectionUpdate(?int $pCmpId, ?int $pSesId, ?string $pAnsName, ?string $pAnsData)
  {
    $query = 'call abc_session_core_named_section_update('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteString($pAnsName).',?)';
    $stmt  = @$this->mysqli->prepare($query);
    if (!$stmt) throw $this->dataLayerError('mysqli::prepare');

    $null = null;
    $success = @$stmt->bind_param('b', $null);
    if (!$success) throw $this->dataLayerError('mysqli_stmt::bind_param');

    $this->getMaxAllowedPacket();

    $this->sendLongData($stmt, 0, $pAnsData);

    if ($this->logQueries)
    {
      $time0 = microtime(true);

      $success = @$stmt->execute();
      if (!$success) throw $this->queryError('mysqli_stmt::execute', $query);

      $this->queryLog[] = ['query' => $query,
                           'time'  => microtime(true) - $time0];
    }
    else
    {
      $success = $stmt->execute();
      if (!$success) throw $this->queryError('mysqli_stmt::execute', $query);
    }

    $ret = $this->mysqli->affected_rows;

    $stmt->close();
    if ($this->mysqli->more_results()) $this->mysqli->next_result();

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Starts a new session.
   *
   * @param int|null    $pCmpId           The ID of the company of the user (safe guard).
   *                                      smallint(5) unsigned
   * @param int|null    $pLanId           The ID of the language of the session.
   *                                      tinyint(3) unsigned
   * @param string|null $pSesSessionToken The session token.
   *                                      varchar(64) character set ascii collation ascii_general_ci
   * @param string|null $pSesCsrfToken    The CSRF token.
   *                                      varchar(64) character set ascii collation ascii_general_ci
   *
   * @return array
   *
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreStartSession(?int $pCmpId, ?int $pLanId, ?string $pSesSessionToken, ?string $pSesCsrfToken): array
  {
    return $this->executeRow1('call abc_session_core_start_session('.$this->quoteInt($pCmpId).','.$this->quoteInt($pLanId).','.$this->quoteString($pSesSessionToken).','.$this->quoteString($pSesCsrfToken).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates the language of a session.
   *
   * @param int|null $pCmpId The ID of the company (safe guard).
   *                         smallint(5) unsigned
   * @param int|null $pSesId The ID of the session.
   *                         int(10) unsigned
   * @param int|null $pLanId The ID of the new language.
   *                         tinyint(3) unsigned
   *
   * @return int
   *
   * @throws MySqlQueryErrorException;
   */
  public function abcSessionCoreUpdateLanId(?int $pCmpId, ?int $pSesId, ?int $pLanId): int
  {
    return $this->executeNone('call abc_session_core_update_lan_id('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).','.$this->quoteInt($pLanId).')');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates a session.
   *
   * @param int|null    $pCmpId   The ID of the company (safe guard).
   *                              smallint(5) unsigned
   * @param int|null    $pSesId   The ID of the session.
   *                              int(10) unsigned
   * @param string|null $pSesData The new additional data of the session.
   *                              longblob
   *
   * @return int
   *
   * @throws MySqlDataLayerException;
   * @throws MySqlQueryErrorException;
   * @throws ResultException;
   */
  public function abcSessionCoreUpdateSession(?int $pCmpId, ?int $pSesId, ?string $pSesData)
  {
    $query = 'call abc_session_core_update_session('.$this->quoteInt($pCmpId).','.$this->quoteInt($pSesId).',?)';
    $stmt  = @$this->mysqli->prepare($query);
    if (!$stmt) throw $this->dataLayerError('mysqli::prepare');

    $null = null;
    $success = @$stmt->bind_param('b', $null);
    if (!$success) throw $this->dataLayerError('mysqli_stmt::bind_param');

    $this->getMaxAllowedPacket();

    $this->sendLongData($stmt, 0, $pSesData);

    if ($this->logQueries)
    {
      $time0 = microtime(true);

      $success = @$stmt->execute();
      if (!$success) throw $this->queryError('mysqli_stmt::execute', $query);

      $this->queryLog[] = ['query' => $query,
                           'time'  => microtime(true) - $time0];
    }
    else
    {
      $success = $stmt->execute();
      if (!$success) throw $this->queryError('mysqli_stmt::execute', $query);
    }

    $ret = $this->mysqli->affected_rows;

    $stmt->close();
    if ($this->mysqli->more_results()) $this->mysqli->next_result();

    return $ret;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
