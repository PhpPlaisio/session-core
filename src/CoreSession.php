<?php
declare(strict_types=1);

namespace Plaisio\Session;

use Plaisio\Kernel\Nub;
use SetBased\Exception\FallenException;

/**
 * A session handler that stores the session data in a database table.
 */
class CoreSession implements Session
{
  //--------------------------------------------------------------------------------------------------------------------
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
   * The named sections of this session.
   *
   * @var array
   */
  protected $sections = [];

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
  private static function getRandomToken(): string
  {
    return hash('sha256', random_bytes(self::$entropyLength));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns stateful double submit token to prevent CSRF attacks.
   *
   * @return string
   */
  public function getCsrfToken(): string
  {
    return $this->session['ses_csrf_token'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of preferred language of the user of the current session.
   *
   * @return int
   */
  public function getLanId(): int
  {
    return $this->session['lan_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a reference to the data of a named section of the session.
   *
   * If the named section does not yet exists a reference to null is returned. Only named sessions opened in exclusive
   * mode will be saved by @see save.
   *
   * @param string $name The name of the named section.
   * @param int    $mode The mode for getting the named section.
   *
   * @return mixed
   *
   * @since 1.0.0
   * @api
   */
  public function &getNamedSection(string $name, int $mode)
  {
    if (!isset($this->sections[$name]))
    {
      if ($this->session['ses_id']===null)
      {
        $section = null;
      }
      else
      {
        $section = Nub::$DL->abcSessionCoreNamedSectionGet(Nub::$companyResolver->getCmpId(),
                                                           $this->session['ses_id'],
                                                           $name,
                                                           $mode);
      }

      if ($section===null)
      {
        $section = ['mode' => $mode,
                    'data' => null];
      }
      else
      {
        $section = ['mode' => $mode,
                    'data' => unserialize($section['ans_data'])];
      }

      $this->sections[$name] = $section;
    }

    return $this->sections[$name]['data'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of the profile of the user of the current session.
   *
   * @return int
   */
  public function getProId(): int
  {
    return $this->session['pro_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of the current session.
   *
   * @return int|null
   */
  public function getSesId(): ?int
  {
    return $this->session['ses_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the session token.
   *
   * @return string
   */
  public function getSessionToken(): string
  {
    return $this->session['ses_session_token'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of the user of the current session.
   *
   * @return int
   */
  public function getUsrId(): int
  {
    return $this->session['usr_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns true if the user is anonymous (i.e. not a user who has logged in). Otherwise, returns false.
   *
   * @return bool
   */
  public function isAnonymous(): bool
  {
    return !empty($this->session['usr_anonymous']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates the session that an user has successfully logged in.
   *
   * @param int $usrId The ID the user.
   */
  public function login(int $usrId): void
  {
    // Return immediately for fake (a.k.a. non-persistent) sessions.
    if ($this->session['ses_id']===null) return;

    $this->session = Nub::$DL->abcSessionCoreLogin($this->session['cmp_id'],
                                                   $this->session['ses_id'],
                                                   $usrId,
                                                   self::getRandomToken(),
                                                   self::getRandomToken());

    $this->unpackSession();
    $this->setCookies();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Terminates the current session.
   */
  public function logout(): void
  {
    // Return immediately for fake (a.k.a. non-persistent) sessions.
    if ($this->session['ses_id']===null) return;

    $this->session = Nub::$DL->abcSessionCoreLogout($this->session['cmp_id'],
                                                    $this->session['ses_id'],
                                                    Nub::$languageResolver->getLanId(),
                                                    self::getRandomToken(),
                                                    self::getRandomToken());

    $this->unpackSession();
    $this->setCookies();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Saves the current state of the session.
   */
  public function save(): void
  {
    // Return immediately for fake (a.k.a. non-persistent) sessions.
    if ($this->session['ses_id']===null) return;

    $serial = (!empty($_SESSION)) ? serialize($_SESSION) : null;
    Nub::$DL->abcSessionCoreUpdateSession($this->session['cmp_id'], $this->session['ses_id'], $serial);

    foreach ($this->sections as $name => $section)
    {
      switch ($section['mode'])
      {
        case Session::SECTION_EXCLUSIVE:
        case Session::SECTION_SHARED:
          $this->saveNamedSection($name, $section['data']);
          break;

        case Session::SECTION_READ_ONLY:
          // Nothing to do.
          break;

        default:
          throw new FallenException('mode', $section['mode']);
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Changes the language of the current session.
   *
   * @param int $lanId The ID of the language.
   */
  public function setLanId(int $lanId): void
  {
    // Return immediately for fake (a.k.a. non-persistent) sessions.
    if ($this->session['ses_id']===null) return;

    $this->session['lan_id'] = $lanId;
    Nub::$DL->abcSessionCoreUpdateLanId($this->session['cmp_id'], $this->session['ses_id'], $lanId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a session or resumes the current session based on the session cookie.
   */
  public function start(): void
  {
    $sesSessionToken = $_COOKIE['ses_session_token'] ?? null;
    if ($sesSessionToken===null)
    {
      // Start a new session.
      $this->session = Nub::$DL->abcSessionCoreStartSession(Nub::$companyResolver->getCmpId(),
                                                            Nub::$languageResolver->getLanId(),
                                                            self::getRandomToken(),
                                                            self::getRandomToken());
    }
    else
    {
      $this->session = Nub::$DL->abcSessionCoreGetSession(Nub::$companyResolver->getCmpId(), $sesSessionToken);

      if (empty($this->session))
      {
        // Session has expired and removed from the database or the session token was not generated by this web site.
        // Start a new session with new tokens.
        $this->session = Nub::$DL->abcSessionCoreStartSession(Nub::$companyResolver->getCmpId(),
                                                              Nub::$languageResolver->getLanId(),
                                                              self::getRandomToken(),
                                                              self::getRandomToken());
      }
      elseif (($this->session['ses_last_request'] + self::$timeout)<=time())
      {
        // Session has expired. Restart the session, i.e. delete all data stored in the session and use new tokens.
        $this->session = Nub::$DL->abcSessionCoreLogout($this->session['cmp_id'],
                                                        $this->session['ses_id'],
                                                        Nub::$languageResolver->getLanId(),
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
  protected function setCookies(): void
  {
    if (isset($_SERVER['HTTPS']))
    {
      $domain = Nub::$canonicalHostnameResolver->getCanonicalHostname();

      // Set session cookie.
      setcookie('ses_session_token',
                $this->session['ses_session_token'],
                0,
                '/',
                $domain,
                true,
                true);

      // Set CSRF cookie.
      setcookie('ses_csrf_token',
                $this->session['ses_csrf_token'],
                0,
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
  protected function unpackSession(): void
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
  /**
   * Saves a named section of the session.
   *
   * Normally will return true. However, in NAMED_FIRST_COME_FIRST_SERVED mode will return false when the named section
   * of the session could not be updated (due to some other request has updated the named section before).
   *
   * @param string $name The name of the named section.
   * @param mixed  $data The value of the named section of the session. A null value will delete the named section
   *                     of the session.
   *
   * @since 1.0.0
   * @api
   */
  private function saveNamedSection(string $name, $data): void
  {
    if ($data===null)
    {
      Nub::$DL->abcSessionCoreNamedSectionDelete(Nub::$companyResolver->getCmpId(),
                                                 $this->session['ses_id'],
                                                 $name);
    }
    else
    {
      Nub::$DL->abcSessionCoreNamedSectionUpdate(Nub::$companyResolver->getCmpId(),
                                                 $this->session['ses_id'],
                                                 $name,
                                                 serialize($data));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
