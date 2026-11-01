<?php

/**
 * The time zone a page names once, in place of a link beside every date.
 *
 * @group headless
 */
class CRM_Elections_Helper_TimeZoneTest extends \PHPUnit\Framework\TestCase {

  public function testTheSiteTimeZoneIsNamedNotGivenAsAnOffset() {
    $name = CRM_Elections_Helper_TimeZone::getTimeZoneLabel();
    $this->assertSame(date_default_timezone_get(), $name);
    $this->assertDoesNotMatchRegularExpression('/^GMT[+-]\d/', $name);
    $this->assertSame('Europe/Paris', CRM_Elections_Helper_TimeZone::getTimeZoneLabel('Europe/Paris'));
  }

  public function testThePageSaysItOnceInOneSentence() {
    $line = CRM_Elections_Helper_TimeZone::getTimeZoneLine();
    $this->assertSame('Times are shown in ' . date_default_timezone_get() . '.', $line);
    $this->assertStringNotContainsString('<a', $line, 'No link to another site');
  }

}
