<?php
declare(strict_types=1);

namespace Plaisio\Session\Test;

use Plaisio\Babel\CoreBabel;
use Plaisio\C;
use Plaisio\CompanyResolver\UniCompanyResolver;
use Plaisio\Kernel\Nub;
use Plaisio\LanguageResolver\CoreLanguageResolver;
use SetBased\Stratum\MySql\MySqlDefaultConnector;

/**
 * Mock framework for testing purposes.
 */
class TestNub extends Nub
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   */
  public function __construct()
  {
    parent::__construct();

    $connector = new MySqlDefaultConnector('localhost', 'test', 'test', 'test');

    self::$babel            = new CoreBabel();
    self::$DL               = new TestDataLayer($connector);
    self::$companyResolver  = new UniCompanyResolver(C::CMP_ID_ABC);
    self::$languageResolver = new CoreLanguageResolver(C::LAN_ID_EN);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
