<?php
declare(strict_types=1);

namespace Plaisio\Session\Test;

use PHPUnit\Framework\TestCase;
use Plaisio\C;
use Plaisio\Cookie\Cookie;
use Plaisio\PlaisioKernel;
use Plaisio\Session\CoreSession;
use Plaisio\Session\Session;

/**
 * Test cases for class CoreSession.
 */
class CoreSessionTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Our concrete instance of Nub.
   *
   * @var PlaisioKernel
   */
  private $kernel;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test method destroyOtherSessionsOfUser.
   */
  public function testDestroyAllSessionsOfUser(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);
    $session31 = new CoreSession($this->kernel);
    $session31->start();
    $session31->login(3);
    $section1          = &$session31->getNamedSection('section_exclusive', CoreSession::SECTION_EXCLUSIVE);
    $section2          = &$session31->getNamedSection('section_exclusive', CoreSession::SECTION_SHARED);
    $section1['hello'] = 'world';
    $section2['foo']   = 'bar';
    $session31->save();

    $this->kernel->cookie->remove('ses_session_token', false);
    $session4 = new CoreSession($this->kernel);
    $session4->start();
    $session4->login(4);
    $session31->save();

    $this->kernel->cookie->remove('ses_session_token', false);
    $session32 = new CoreSession($this->kernel);
    $session32->start();
    $session32->login(3);
    $session31->save();

    // Destroy all sessions of user 3.
    $session32->destroyAllSessions();

    // Assert session31 has been destroyed.
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $session31->getSessionToken()]));
    $session = new CoreSession($this->kernel);
    $session->start();
    self::assertNotEquals($session31->sesId, $session->sesId);
    self::assertNotEquals($session31->getSessionToken(), $session->getSessionToken());

    // Assert session32 has been destroyed.
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $session32->getSessionToken()]));
    $session = new CoreSession($this->kernel);
    $session->start();
    self::assertNotEquals($session31->sesId, $session->sesId);
    self::assertNotEquals($session31->getSessionToken(), $session->getSessionToken());

    // Assert session4 (other user) is still alive.
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $session4->getSessionToken()]));
    $session = new CoreSession($this->kernel);
    $session->start();
    self::assertSame($session4->sesId, $session->sesId);
    self::assertSame($session4->getSessionToken(), $session->getSessionToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test method destroyOtherSessionsOfUser.
   */
  public function testDestroyOtherSessionsOfUser(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);
    $session31 = new CoreSession($this->kernel);
    $session31->start();
    $session31->login(3);
    $section1          = &$session31->getNamedSection('section_exclusive', CoreSession::SECTION_EXCLUSIVE);
    $section2          = &$session31->getNamedSection('section_exclusive', CoreSession::SECTION_SHARED);
    $section1['hello'] = 'world';
    $section2['foo']   = 'bar';
    $session31->save();

    $this->kernel->cookie->remove('ses_session_token', false);
    $session4 = new CoreSession($this->kernel);
    $session4->start();
    $session4->login(4);
    $session31->save();

    $this->kernel->cookie->remove('ses_session_token', false);
    $session32 = new CoreSession($this->kernel);
    $session32->start();
    $session32->login(3);
    $session31->save();

    // Destroy other sessions (hence session31)
    $session32->destroyOtherSessions();

    // Assert session31 (same user, other session) has been destroyed.
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $session31->getSessionToken()]));
    $session = new CoreSession($this->kernel);
    $session->start();
    self::assertNotEquals($session31->sesId, $session->sesId);
    self::assertNotEquals($session31->getSessionToken(), $session->getSessionToken());

    // Assert session32 is still alive.
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $session32->getSessionToken()]));
    $session = new CoreSession($this->kernel);
    $session->start();
    self::assertSame($session32->sesId, $session->sesId);
    self::assertSame($session32->getSessionToken(), $session->getSessionToken());

    // Assert session4 (other user) is still alive.
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $session4->getSessionToken()]));
    $session = new CoreSession($this->kernel);
    $session->start();
    self::assertSame($session4->sesId, $session->sesId);
    self::assertSame($session4->getSessionToken(), $session->getSessionToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test an expired anonymous session will be replaced with new session.
   */
  public function testExpiredSession1(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session1 = new CoreSession($this->kernel);
    $session1->start();
    $token1 = $session1->getSessionToken();
    $this->kernel->cookie->add(new Cookie(['name' => 'string', 'value' => 'hello world']));
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    CoreSession::$timeout = 3;
    sleep(5);

    $session2 = new CoreSession($this->kernel);
    $session2->start();
    $token2 = $session2->getSessionToken();

    self::assertNotEquals($token1, $token2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test an expired logged in session will be replaced with new session.
   */
  public function testExpiredSession2(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session1 = new CoreSession($this->kernel);
    $session1->start();
    $session1->login(3);
    $token1 = $session1->getSessionToken();
    $this->kernel->cookie->add(new Cookie(['name' => 'string', 'value' => 'hello world']));
    $session1->save();

    self::assertFalse($session1->isAnonymous());
    self::assertEquals(3, $session1->usrId);

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    CoreSession::$timeout = 3;
    sleep(5);

    $session2 = new CoreSession($this->kernel);
    $session2->start();
    $token2 = $session2->getSessionToken();

    self::assertNotEquals($token1, $token2);
    self::assertEquals(2, $session2->usrId);
    self::assertTrue($session2->isAnonymous());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test flash message: initial no flash message.
   */
  public function testFlashMessage01(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session = new CoreSession($this->kernel);
    $session->start();
    $hasFlashMessage = $session->getHasFlashMessage();

    self::assertFalse($hasFlashMessage);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test flash message: consistently saved.
   */
  public function testFlashMessage02(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session1 = new CoreSession($this->kernel);
    $session1->start();
    $token1            = $session1->getSessionToken();
    $hasFlashMessage1a = $session1->getHasFlashMessage();
    self::assertFalse($hasFlashMessage1a);
    $session1->setHasFlashMessage(true);
    $hasFlashMessage1b = $session1->getHasFlashMessage();
    self::assertTrue($hasFlashMessage1b);
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();
    $token2           = $session2->getSessionToken();
    $hasFlashMessage2 = $session2->getHasFlashMessage();
    self::assertTrue($hasFlashMessage2);
    $session2->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token2]));

    $session3 = new CoreSession($this->kernel);
    $session3->start();
    $token3            = $session3->getSessionToken();
    $hasFlashMessage3a = $session3->getHasFlashMessage();
    self::assertTrue($hasFlashMessage3a);
    $session3->setHasFlashMessage(false);
    $hasFlashMessage3b = $session3->getHasFlashMessage();
    self::assertFalse($hasFlashMessage3b);
    $session3->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token3]));

    $session4 = new CoreSession($this->kernel);
    $session4->start();
    $hasFlashMessage4 = $session4->getHasFlashMessage();
    self::assertFalse($hasFlashMessage4);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test method getCsrfToken().
   */
  public function testGetCsrfToken(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session = new CoreSession($this->kernel);
    $session->start();

    self::assertNotNull($session->getSessionToken());
    self::assertNotNull($session->getCsrfToken());
    self::assertNotEquals($session->getSessionToken(), $session->getCsrfToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test method getProId().
   */
  public function testGetProId(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session = new CoreSession($this->kernel);
    $session->start();
    $proId = $session->proId;

    self::assertEquals(1, $proId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test login destroys all previous session data and generates new session token.
   */
  public function testLogin01(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session = new CoreSession($this->kernel);
    $session->start();
    $this->kernel->cookie->add(new Cookie(['name' => 'string', 'value' => 'hello world']));

    $token1 = $session->getSessionToken();
    $session->login(3);
    $token2 = $session->getSessionToken();

    self::assertEquals(3, $session->usrId);
    self::assertEquals(C::LAN_ID_NL, $session->getLanId());
    self::assertNotEquals($token1, $token2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test logout destroys all previous session data.
   */
  public function testLogout01(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session1 = new CoreSession($this->kernel);
    $session1->start();
    $session1->login(3);
    $token1             = $session1->getSessionToken();
    $this->kernel->cookie->add(new Cookie(['name' => 'string', 'value' => 'hello world']));
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();

    self::assertEquals('hello world',$this->kernel->cookie->getValue('string'));

    $session2->logout();
    $token2 = $session2->getSessionToken();

    self::assertNotEquals($token1, $token2);

    // Asset that token1 does not valid any more.
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session3 = new CoreSession($this->kernel);
    $session3->start();
    $token3 = $session3->getSessionToken();

    self::assertNotEquals($token1, $token3);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Basic test with named sections.
   */
  public function testNamedSection1(): void
  {
    $session1 = new CoreSession($this->kernel);
    $session1->start();

    $data = &$session1->getNamedSection(__CLASS__, Session::SECTION_EXCLUSIVE);
    self::assertNull($data);

    $data = ['Hello', 'world'];

    $token1 = $session1->getSessionToken();
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();

    $data = &$session2->getNamedSection(__CLASS__, Session::SECTION_READ_ONLY);
    self::assertEquals(['Hello', 'world'], $data);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Basic test with named sections read-only: data must not be saved.
   */
  public function testNamedSectionReadOnly1(): void
  {
    $session0 = new CoreSession($this->kernel);
    $session0->start();

    $data = &$session0->getNamedSection(__CLASS__, Session::SECTION_EXCLUSIVE);
    self::assertNull($data);

    $data = ['Hello', 'world'];

    $token0 = $session0->getSessionToken();
    $session0->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token0]));

    $session1 = new CoreSession($this->kernel);
    $session1->start();

    $data = &$session1->getNamedSection(__CLASS__, Session::SECTION_READ_ONLY);
    self::assertEquals(['Hello', 'world'], $data);

    // Change the data.
    $data = null;

    $token1 = $session1->getSessionToken();
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();

    $data = &$session2->getNamedSection(__CLASS__, Session::SECTION_READ_ONLY);
    self::assertEquals(['Hello', 'world'], $data);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Basic test with named sections read-only: data must not be saved or changes.
   */
  public function testNamedSectionReadOnly2(): void
  {
    $session1 = new CoreSession($this->kernel);
    $session1->start();

    $data = &$session1->getNamedSection(__CLASS__, Session::SECTION_READ_ONLY);
    self::assertNull($data);

    $data = ['Hello', 'world'];

    $token1 = $session1->getSessionToken();
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();

    $data = &$session2->getNamedSection(__CLASS__, Session::SECTION_READ_ONLY);
    self::assertNull($data);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Tests for method setLanId().
   */
  public function testSetLanId(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session1 = new CoreSession($this->kernel);
    $session1->start();
    $token1 = $session1->getSessionToken();
    $lanId1 = $session1->getLanId();
    $session1->setLanId(C::LAN_ID_NL);
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();
    $lanId2 = $session1->getLanId();

    self::assertNotEquals(C::LAN_ID_NL, $lanId1);
    self::assertEquals(C::LAN_ID_NL, $lanId2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session with empty session token.
   */
  public function testStart01(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session = new CoreSession($this->kernel);
    $session->start();

    self::assertNotNull($session->getSessionToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case session start with empty session token.
   */
  public function testStart02()
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session = new CoreSession($this->kernel);
    $session->start();

    self::assertNotNull($session->getSessionToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session with non-empty session token.
   */
  public function testStart03(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session1 = new CoreSession($this->kernel);
    $session1->start();
    $token1 = $session1->getSessionToken();
    $session1->save();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();
    $token2 = $session2->getSessionToken();
    $session2->save();

    self::assertSame($token1, $token2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session with unknown session token.
   */
  public function testStart04(): void
  {
    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => 'not-a-known-session-token']));

    $session = new CoreSession($this->kernel);
    $session->start();
    $token = $session->getSessionToken();

    self::assertNotEquals($this->kernel->cookie->getValue('ses_session_token'), $token);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session with known session token from other company.
   */
  public function testStart05(): void
  {
    $this->kernel->cookie->remove('ses_session_token', false);

    $session1 = new CoreSession($this->kernel);
    $session1->start();
    $token1 = $session1->getSessionToken();
    $sesId1 = $session1->sesId;
    $cmpId1 = $this->kernel->company->cmpId;
    $session1->save();
    $this->kernel->DL->commit();

    $this->kernel = new TestKernelSys();

    $this->kernel->cookie->add(new Cookie(['name' => 'ses_session_token', 'value' => $token1]));

    $session2 = new CoreSession($this->kernel);
    $session2->start();
    $token2 = $session2->getSessionToken();
    $sesId2 = $session2->sesId;
    $cmpId2 = $this->kernel->company->cmpId;
    $session2->save();

    self::assertNotEquals($cmpId1, $cmpId2);
    self::assertNotEquals($sesId1, $sesId2);
    self::assertNotEquals($token1, $token2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  protected function setUp(): void
  {
    $this->kernel = new TestKernelPlaisio();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Disconnects from the MySQL server.
   */
  protected function tearDown(): void
  {
    $this->kernel->DL->commit();
    $this->kernel->DL->disconnect();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
