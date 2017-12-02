<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Abc\Session\Test;

use SetBased\Abc\Abc;
use SetBased\Abc\Babel\CoreBabel;
use SetBased\Abc\C;
use SetBased\Abc\CompanyResolver\UniCompanyResolver;
use SetBased\Abc\LanguageResolver\CoreLanguageResolver;

/**
 * Mock framework for testing purposes.
 */
class TestAbc extends Abc
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   */
  public function __construct()
  {
    parent::__construct();

    self::$babel            = new CoreBabel();
    self::$DL               = new TestDataLayer();
    self::$companyResolver  = new UniCompanyResolver(C::CMP_ID_ABC);
    self::$languageResolver = new CoreLanguageResolver(C::LAN_ID_EN);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
