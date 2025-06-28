<?php
declare(strict_types=1);

namespace Plaisio\Session\Test\Plaisio;

use Plaisio\C;
use Plaisio\CompanyResolver\CompanyResolver;
use Plaisio\CompanyResolver\UniCompanyResolver;

/**
 * Kernel for testing purposes.
 */
class TestKernelPlaisio extends TestKernelSys
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the helper object for deriving the company.
   *
   * @return CompanyResolver
   */
  public function getCompany(): CompanyResolver
  {
    return new UniCompanyResolver(C::CMP_ID_PLAISIO);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
