<?php
//----------------------------------------------------------------------------------------------------------------------
namespace Nahouw\Abc;

use SetBased\Abc\Abc;
use SetBased\Abc\Session\Session;

/**
 * A session handler that stores the session data in a database table.
 */
class CoreSession implements Session
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The source for randomness.
   *
   * @var string
   */
  public static $entropyFile = '/dev/urandom';

  /**
   * The number of bytes the be read from the source of randomness.
   *
   * @var int
   */
  public static $entropyLength = 32;

  /**
   * The number of seconds before a session expires (default is 20 minutes).
   *
   * @var int
   */
  public static $timeout = 1200;

  /**
   * The session data.
   *
   * @var array
   */
  protected $session;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a secure random token that can be safely used for session IDs. The length of the token is 64 HEX
   * characters.
   *
   * @return string
   */
  private static function getRandomToken()
  {
    $handle = fopen(self::$entropyFile, 'rb');
    $token  = bin2hex(hash('sha256', fgets($handle, self::$entropyLength), true));
    fclose($handle);

    return $token;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getCmpId()
  {
    return $this->session['cmp_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getCsrfToken()
  {
    return $this->session['ses_csrf_token'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the code of preferred language of the user of the current session.
   *
   * @return string
   */
  public function getLanCode()
  {
    return $this->session['lan_code'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getLanId()
  {
    return $this->session['lan_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getProId()
  {
    return $this->session['pro_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getSesId()
  {
    return $this->session['ses_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getSessionToken()
  {
    return $this->session['ses_session_token'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getUsrId()
  {
    return $this->session['usr_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function isAnonymous()
  {
    return $this->session['usr_anonymous'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function login($usrId)
  {
    $this->session['ses_session_token'] = self::getRandomToken();
    $this->session['ses_csrf_token']    = self::getRandomToken();

    $this->session = Abc::$DL->abcAuthSessionLogin($this->session['cmp_id'],
                                                   $this->session['ses_id'],
                                                   $usrId,
                                                   $this->session['ses_session_token'],
                                                   $this->session['ses_csrf_token']);

    $this->unpackSession();
    $this->setCookies();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function logout()
  {
    $this->session['ses_session_token'] = self::getRandomToken();
    $this->session['ses_csrf_token']    = self::getRandomToken();

    $this->session = Abc::$DL->abcAuthSessionLogout($this->session['cmp_id'],
                                                    $this->session['ses_id'],
                                                    $this->session['ses_session_token'],
                                                    $this->session['ses_csrf_token']);

    $this->unpackSession();
    $this->setCookies();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function save()
  {
    if ($this->session['ses_id']!==null)
    {
      $serial = (!empty($_SESSION)) ? serialize($_SESSION) : null;

      Abc::$DL->abcAuthSessionUpdateSession($this->session['cmp_id'], $this->session['ses_id'], $serial);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Changes the language of the current session.
   *
   * @param int $lanId The ID of the language.
   */
  public function setLanId($lanId)
  {
    $this->session['lan_id'] = $lanId;

    Abc::$DL->abcAuthSessionUpdateLanId($this->session['cmp_id'], $this->session['ses_id'], $lanId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function start()
  {
    $sesSessionToken = $_COOKIE['ses_session_token'] ?? null;
    $cmpAbbr         = Abc::$domainResolver->getDomain();

    if ($sesSessionToken===null)
    {
      // Start a new session.
      $this->session = Abc::$DL->abcAuthSessionStartSession($cmpAbbr,
                                                            Abc::$languageResolver->getLanguageCode(),
                                                            self::getRandomToken(),
                                                            self::getRandomToken());
    }
    else
    {
      $this->session = Abc::$DL->abcAuthSessionGetSession($cmpAbbr, $sesSessionToken);

      if (empty($this->session))
      {
        // Session has expired and removed from the database or the session token was not generated by this web site.
        // Start a new session with new tokens.
        $this->session = Abc::$DL->abcAuthSessionStartSession($cmpAbbr,
                                                              Abc::$languageResolver->getLanguageCode(),
                                                              self::getRandomToken(),
                                                              self::getRandomToken());
      }
      elseif (($this->session['ses_last_request'] + self::$timeout)<=time())
      {
        // Session has expired. Restart the session, i.e. delete all data stored in the session and use new tokens.
        $this->session = Abc::$DL->abcAuthSessionRestartSession($this->session['ses_id'],
                                                                $cmpAbbr,
                                                                Abc::$languageResolver->getLanguageCode(),
                                                                self::getRandomToken(),
                                                                self::getRandomToken());
      }
    }

    $this->unpackSession();
    $this->setCookies();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the session and CSRF cookies.
   */
  protected function setCookies()
  {
    if (isset($_SERVER['HTTPS']))
    {
      $domain = Abc::$canonicalHostnameResolver->getCanonicalHostname();

      // Set session cookies.
      setcookie('ses_session_token',
                $this->session['ses_session_token'],
                false,
                '/',
                $domain,
                true,
                true);

      // Set CSRF cookies.
      setcookie('ses_csrf_token',
                $this->session['ses_csrf_token'],
                false,
                '/',
                $domain,
                true,
                false);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Unpacks the session data and initializes $_SESSION.
   */
  protected function unpackSession()
  {
    if ($this->session['ses_data']!==null)
    {
      $_SESSION = unserialize($this->session['ses_data']);
    }
    else
    {
      $_SESSION = [];
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
