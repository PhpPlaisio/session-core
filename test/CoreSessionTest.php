<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Abc\Session\Test;

use PHPUnit\Framework\TestCase;
use SetBased\Abc\Abc;
use SetBased\Abc\C;
use SetBased\Abc\CompanyResolver\UniCompanyResolver;
use SetBased\Abc\Session\CoreSession;

/**
 * Test cases for class CoreSession.
 */
class CoreSessionTest extends TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Our concrete instance of Abc.
   *
   * @var Abc
   */
  private static $abc;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Creates the concrete implementation of the ABC TestAbc.
   */
  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();

    self::$abc = new TestAbc();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test method getCsrfToken().
   */
  public function testGetCsrfToken()
  {
    unset($_COOKIE['ses_session_token']);

    $session = new CoreSession();
    $session->start();

    self::assertNotNull($session->getSessionToken());
    self::assertNotNull($session->getCsrfToken());
    self::assertNotEquals($session->getSessionToken(), $session->getCsrfToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test method getProId().
   */
  public function testGetProId()
  {
    $_COOKIE['ses_session_token'] = null;

    $session = new CoreSession();
    $session->start();
    $proId = $session->getProId();

    self::assertEquals(1, $proId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test login destroys all previous session data and generates new session token.
   */
  public function testLogin01()
  {
    $_COOKIE['ses_session_token'] = null;

    $session = new CoreSession();
    $session->start();
    $_SESSION['array']  = ['hello' => 'world'];
    $_SESSION['string'] = 'hello world';

    $token1 = $session->getSessionToken();
    $session->login(3);
    $token2 = $session->getSessionToken();

    self::assertEmpty($_SESSION);
    self::assertEquals(3, $session->getUsrId());
    self::assertEquals(C::LAN_ID_NL, $session->getLanId());
    self::assertNotEquals($token1, $token2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test logout destroys all previous session data.
   */
  public function testLogout01()
  {
    $_COOKIE['ses_session_token'] = null;

    $session1 = new CoreSession();
    $session1->start();
    $session1->login(3);
    $token1             = $session1->getSessionToken();
    $_SESSION['array']  = ['hello' => 'world'];
    $_SESSION['string'] = 'hello world';
    $session1->save();

    $_COOKIE['ses_session_token'] = $token1;

    $session2 = new CoreSession();
    $session2->start();

    self::assertEquals(['hello' => 'world'], $_SESSION['array']);
    self::assertEquals('hello world', $_SESSION['string']);

    $session2->logout();
    $token2 = $session2->getSessionToken();

    self::assertEmpty($_SESSION);
    self::assertNotEquals($token1, $token2);

    // Asset that token1 does not valid any more.
    $_COOKIE['ses_session_token'] = $token1;

    $session3 = new CoreSession();
    $session3->start();
    $token3 = $session3->getSessionToken();

    self::assertNotEquals($token1, $token3);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session with empty session token.
   */
  public function testStart01()
  {
    $_COOKIE['ses_session_token'] = null;

    $session = new CoreSession();
    $session->start();

    self::assertNotNull($session->getSessionToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test case session start with empty session token.
   */
  public function testStart02()
  {
    unset($_COOKIE['ses_session_token']);

    $session = new CoreSession();
    $session->start();

    self::assertNotNull($session->getSessionToken());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session with non-empty session token.
   */
  public function testStart03()
  {
    $_COOKIE['ses_session_token'] = null;

    $session1 = new CoreSession();
    $session1->start();
    $token1 = $session1->getSessionToken();
    $session1->save();

    $_COOKIE['ses_session_token'] = $token1;

    $session2 = new CoreSession();
    $session2->start();
    $token2 = $session2->getSessionToken();
    $session2->save();

    self::assertSame($token1, $token2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session with unknown session token.
   */
  public function testStart04()
  {
    $_COOKIE['ses_session_token'] = 'not-a-known-session-token';

    $session = new CoreSession();
    $session->start();
    $token = $session->getSessionToken();

    self::assertNotEquals($_COOKIE['ses_session_token'], $token);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test start session ith known session token from other company.
   */
  public function testStart05()
  {
    $_COOKIE['ses_session_token'] = null;

    $session1 = new CoreSession();
    $session1->start();
    $token1 = $session1->getSessionToken();
    $sesId1 = $session1->getSesId();
    $cmpId1 = Abc::$companyResolver->getCmpId();
    $session1->save();

    Abc::$companyResolver = new UniCompanyResolver(C::CMP_ID_SYS);

    $_COOKIE['ses_session_token'] = $token1;

    $session2 = new CoreSession();
    $session2->start();
    $token2 = $session2->getSessionToken();
    $sesId2 = $session2->getSesId();
    $cmpId2 = Abc::$companyResolver->getCmpId();
    $session2->save();

    self::assertNotEquals($cmpId1, $cmpId2);
    self::assertNotEquals($sesId1, $sesId2);
    self::assertNotEquals($token1, $token2);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Connects to the MySQL server and cleans the BLOB tables.
   */
  protected function setUp()
  {
    Abc::$DL->connect('localhost', 'test', 'test', 'test');
    Abc::$DL->begin();
    Abc::$babel->setLanguage(C::LAN_ID_EN);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Disconnects from the MySQL server.
   */
  protected function tearDown()
  {
    Abc::$DL->commit();
    Abc::$DL->disconnect();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------