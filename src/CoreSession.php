<?php
declare(strict_types=1);

namespace Plaisio\Session;

use Plaisio\Cookie\Cookie;
use Plaisio\PlaisioObject;
use SetBased\Exception\FallenException;
use SetBased\Exception\LogicException;

/**
 * A session handler that stores the session data in a database table.
 */
class CoreSession extends PlaisioObject implements Session
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The number of bytes to be read from the source of randomness.
   *
   * @var int
   */
  public static int $entropyLength = 32;

  /**
   * The number of seconds before a session expires (default is 20 minutes).
   *
   * @var int
   */
  public static int $timeout = 1200;

  /**
   * The named sections of this session.
   *
   * @var array
   */
  protected array $sections = [];

  /**
   * The session data.
   *
   * @var array|null
   */
  protected ?array $session = null;

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
   * Returns the value of a property.
   *
   * Do not call this method directly as it is a PHP magic method that
   * will be implicitly called when executing `$value = $object->property;`.
   *
   * @param string $property The name of the property.
   *
   * @return mixed The value of the property.
   *
   * @throws \LogicException If the property is not defined.
   */
  public function __get(string $property)
  {
    $getter = 'get'.ucfirst($property);
    if (method_exists($this, $getter))
    {
      return $this->$property = $this->$getter();
    }

    throw new \LogicException(sprintf('Unknown property %s::%s', __CLASS__, $property));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function destroyAllSessions(): void
  {
    // Return immediately for fake (a.k.a. non-persistent) sessions.
    if ($this->session['ses_id']===null) return;

    if ($this->isAnonymous())
    {
      throw new LogicException('Method %s must not be invoked for anonymous users', __METHOD__);
    }

    $this->nub->DL->abcSessionCoreDestroyAllSessionsOfUser($this->nub->company->cmpId, $this->session['usr_id']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function destroyAllSessionsOfUser(int $usrId): void
  {
    $this->nub->DL->abcSessionCoreDestroyAllSessionsOfUser($this->nub->company->cmpId, $usrId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function destroyOtherSessions(): void
  {
    // Return immediately for fake (a.k.a. non-persistent) sessions.
    if ($this->session['ses_id']===null) return;

    if ($this->isAnonymous())
    {
      throw new LogicException('Method % must not be invoked for anonymous users', __METHOD__);
    }

    $this->nub->DL->abcSessionCoreDestroyOtherSessionsOfUser($this->nub->company->cmpId, $this->session['ses_id']);
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
   * Returns true if and only if there are one or more flash messages saved in the current sessions.
   *
   * @return bool
   *
   * @since 4.0.0
   * @api
   */
  public function getHasFlashMessage(): bool
  {
    return ($this->session['ses_has_flash_message']===1);
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
   * If the named section does not yet exist a reference to null is returned. Only named sessions opened in exclusive
   * mode will be saved by @param string $name The name of the named section.
   *
   * @param int $mode The mode for getting the named section.
   *
   * @return mixed
   *
   * @see   save.
   *
   * @since 1.0.0
   * @api
   */
  public function &getNamedSection(string $name, int $mode): mixed
  {
    if (!isset($this->sections[$name]))
    {
      if ($this->session['ses_id']===null)
      {
        $section = null;
      }
      else
      {
        $section = $this->nub->DL->abcSessionCoreNamedSectionGet($this->nub->company->cmpId,
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

    $this->session = $this->nub->DL->abcSessionCoreLogin($this->session['cmp_id'],
                                                         $this->session['ses_id'],
                                                         $usrId,
                                                         self::getRandomToken(),
                                                         self::getRandomToken());

    $this->unsetProperties();
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

    $this->session = $this->nub->DL->abcSessionCoreLogout($this->session['cmp_id'],
                                                          $this->session['ses_id'],
                                                          $this->nub->languageResolver->getLanId(),
                                                          self::getRandomToken(),
                                                          self::getRandomToken());

    $this->unsetProperties();
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
    $this->nub->DL->abcSessionCoreUpdateSession($this->session['cmp_id'],
                                                $this->session['ses_id'],
                                                $this->session['ses_has_flash_message'],
                                                $serial);

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
   * Sets whether the current session has one or more flash messages.
   *
   * @param bool $hasFlashMessage If and only if true the current session has on or more flash messages.
   *
   * @return void
   *
   * @since 4.0.0
   * @api
   */
  public function setHasFlashMessage(bool $hasFlashMessage): void
  {
    $this->session['ses_has_flash_message'] = $hasFlashMessage ? 1 : 0;
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
    if ($this->session['ses_id']===null)
    {
      return;
    }

    $this->session['lan_id'] = $lanId;
    $this->nub->DL->abcSessionCoreUpdateLanId($this->session['cmp_id'], $this->session['ses_id'], $lanId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a session or resumes the current session based on the session cookie.
   */
  public function start(): void
  {
    $sesSessionToken = $this->nub->request->getCookie('ses_session_token');
    if ($sesSessionToken===null)
    {
      // Start a new session.
      $this->session = $this->nub->DL->abcSessionCoreStartSession($this->nub->company->cmpId,
                                                                  $this->nub->languageResolver->getLanId(),
                                                                  self::getRandomToken(),
                                                                  self::getRandomToken());
    }
    else
    {
      $this->session = $this->nub->DL->abcSessionCoreGetSession($this->nub->company->cmpId, $sesSessionToken);

      if (empty($this->session))
      {
        // Session has expired and removed from the database or the session token was not generated by this web site.
        // Start a new session with new tokens.
        $this->session = $this->nub->DL->abcSessionCoreStartSession($this->nub->company->cmpId,
                                                                    $this->nub->languageResolver->getLanId(),
                                                                    self::getRandomToken(),
                                                                    self::getRandomToken());
      }
      elseif (($this->session['ses_last_request'] + self::$timeout)<=time())
      {
        // Session has expired. Restart the session, i.e. delete all data stored in the session and use new tokens.
        $this->session = $this->nub->DL->abcSessionCoreLogout($this->session['cmp_id'],
                                                              $this->session['ses_id'],
                                                              $this->nub->languageResolver->getLanId(),
                                                              self::getRandomToken(),
                                                              self::getRandomToken());
      }
    }

    $this->unsetProperties();
    $this->unpackSession();
    $this->setCookies();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of the profile of the user of the current session.
   *
   * @return int
   */
  protected function getProId(): int
  {
    return $this->session['pro_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of the current session.
   *
   * @return int|null
   */
  protected function getSesId(): ?int
  {
    return $this->session['ses_id'] ?? null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of the user of the current session.
   *
   * @return int
   */
  protected function getUsrId(): int
  {
    return $this->session['usr_id'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets the session and CSRF cookies.
   */
  protected function setCookies(): void
  {
    if ($this->nub->request->isSecureChannel())
    {
      $domain = $this->nub->canonicalHostnameResolver->getCanonicalHostname();

      $this->nub->cookie->add(new Cookie(['name'   => 'ses_session_token',
                                          'value'  => $this->session['ses_session_token'],
                                          'domain' => $domain]));

      $this->nub->cookie->add(new Cookie(['name'     => 'ses_csrf_token',
                                          'value'    => $this->session['ses_csrf_token'],
                                          'domain'   => $domain,
                                          'httpOnly' => false]));
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
  private function saveNamedSection(string $name, mixed $data): void
  {
    if ($data===null)
    {
      $this->nub->DL->abcSessionCoreNamedSectionDelete($this->nub->company->cmpId,
                                                       $this->session['ses_id'],
                                                       $name);
    }
    else
    {
      $this->nub->DL->abcSessionCoreNamedSectionUpdate($this->nub->company->cmpId,
                                                       $this->session['ses_id'],
                                                       $name,
                                                       serialize($data));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Unset properties that are accessible via magic getter,
   */
  private function unsetProperties(): void
  {
    unset($this->proId);
    unset($this->sesId);
    unset($this->usrId);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
