<?php

namespace Guru\PhoneNumberFormatterBundle\Tests\Formatter;

use Guru\PhoneNumberFormatterBundle\Formatter\Formatter;
use Guru\PhoneNumberFormatterBundle\Formatter\FormatterMy;
use Guru\PhoneNumberFormatterBundle\Formatter\FormatterId;
use \Mockery as m;

/**
 * @group unit
 * @group formatter
 */
class PhoneNumberFormatterFormatterTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    private $formatter;
    private $defaultEmptyExpectation;

    private function mockEntity($className, $methods = array())
    {
        $methods = array_merge(array('__toString' => $className), $methods);

        return m::mock($className, $methods)->shouldIgnoreMissing();
    }

    public function setUp()
    {
        $const = array(
            'guru_phone_number_formatter.format.my.prefix.landline.short' => array(
                '2' => '02'
            ),
            'guru_phone_number_formatter.format.my.prefix.landline.long' => array(
                '80' => '080'
            ),
            'guru_phone_number_formatter.format.my.prefix.mobile.short' => array(
                '10' => '010',
            ),
            'guru_phone_number_formatter.format.my.prefix.mobile.long' => array(
                '011',
            ),
            'guru_phone_number_formatter.format.id.prefix.landline.lengths' => array(
                7,8
            ),
            'guru_phone_number_formatter.format.id.prefix.landline.short' => array(
                '21' => '021',
            ),
            'guru_phone_number_formatter.format.id.prefix.landline.long' => array(
                '252' => '0252',
            ),
            'guru_phone_number_formatter.format.id.prefix.mobile' => array(
                '0814' => [11,12],
            ),
        );

        $this->container = m::mock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->container->shouldReceive('getParameter')
            ->andReturnUsing(function($key) use ($const) {
                return $const[$key];
            }
        );
        $this->container->shouldReceive('hasParameter')
            ->andReturnUsing(function($key) use ($const) {
                return array_key_exists($key, $const);
            }
        );

        $this->formatter = new Formatter($this->container);
    }

    private function initRegionFormatters($formatter)
    {
        $formatter->setCountryCodes(array(
            'my' => '60',
            'id' => '62',
            'sg' => '65',
            'th' => '66',
        ));

        $regionFormatter = new FormatterMy($this->container);
        $regionFormatter->setLandlinePrefixCodesShort($this->container->getParameter('guru_phone_number_formatter.format.my.prefix.landline.short'));
        $regionFormatter->setLandlinePrefixCodesLong($this->container->getParameter('guru_phone_number_formatter.format.my.prefix.landline.long'));
        $regionFormatter->setMobilePrefixCodesShort($this->container->getParameter('guru_phone_number_formatter.format.my.prefix.mobile.short'));
        $regionFormatter->setMobilePrefixCodesLong($this->container->getParameter('guru_phone_number_formatter.format.my.prefix.mobile.long'));
        $formatter->addRegionFormatter('my', $regionFormatter);

        $regionFormatter = new FormatterId($this->container);
        $regionFormatter->setLandlineLengths($this->container->getParameter('guru_phone_number_formatter.format.id.prefix.landline.lengths'));
        $regionFormatter->setLandlinePrefixCodesShort($this->container->getParameter('guru_phone_number_formatter.format.id.prefix.landline.short'));
        $regionFormatter->setLandlinePrefixCodesLong($this->container->getParameter('guru_phone_number_formatter.format.id.prefix.landline.long'));
        $regionFormatter->setMobileCodes($this->container->getParameter('guru_phone_number_formatter.format.id.prefix.mobile'));
        $formatter->addRegionFormatter('id', $regionFormatter);

        return $formatter;
    }

    /**
     * @dataProvider provideNumberToE194
    **/
    public function testNumberToE194($expected, $countryCode, $number)
    {
        $this->initRegionFormatters($this->formatter);

        $actual = $this->formatter->numberToE194($number, $countryCode);
        $this->assertEquals($expected, $actual);
    }

    public function provideNumberToE194()
    {
        return array(
            //my
            'my - outside defined params' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '60',
                '123',
            ),
            'my - single digit - landline' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                ),
                '60',
                '2123456',
            ),
            'my - single digit - landline - bigger number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                ),
                '60',
                '21234567',
            ),
            'my - single digit - landline - biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                ),
                '60',
                '212345678',
            ),
            'my - single digit - landline - more than biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '2123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '60',
                '2123456789',
            ),


            // multi digit landline
            'my - multi digit - landline' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                ),
                '60',
                '02123456',
            ),
            'my - multi digit - landline - bigger number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                ),
                '60',
                '021234567',
            ),
            'my - multi digit - landline - biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                ),
                '60',
                '0212345678',
            ),
            'my - multi digit - landline - more than biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '2123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '60',
                '02123456789',
            ),



            // long prefix - single digit
            'my - long prefix - single digit - landline' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                ),
                '60',
                '80123456',
            ),
            'my - long prefix - single digit - landline - bigger number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                ),
                '60',
                '801234567',
            ),
            'my - long prefix - single digit - landline - biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                ),
                '60',
                '8012345678',
            ),
            'my - long prefix - single digit - landline - more than biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '80123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '60',
                '80123456789',
            ),

            // my - long prefix - multi digit
            'my - long prefix - multi digit - landline' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                ),
                '60',
                '080123456',
            ),
            'my - long prefix - multi digit - landline - bigger number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                ),
                '60',
                '0801234567',
            ),
            'my - long prefix - multi digit - landline - biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                ),
                '60',
                '08012345678',
            ),
            'my - long prefix - multi digit - landline - more than biggest number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '080123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '60',
                '080123456789',
            ),


            // my - mobile - short - no prefix
            'my - mobile - short - no prefix' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
                '60',
                '101234567',
            ),
            'my - mobile - short - with prefix' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
                '60',
                '0101234567',
            ),
            'my - mobile - long' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '011',
                    'nationalDestinationCodeInternational' => '11',
                ),
                '60',
                '01112345678',
            ),

            // id
            'id - outside defined params' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '62',
                '123',
            ),
            'id - mobile - short - no prefix' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '5678901',
                    'nationalDestinationCode' => '0814',
                    'nationalDestinationCodeInternational' => '814',
                ),
                '62',
                '8145678901',
            ),
            'id - mobile - short - no prefix - longer' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '56789012',
                    'nationalDestinationCode' => '0814',
                    'nationalDestinationCodeInternational' => '814',
                ),
                '62',
                '81456789012',
            ),
            'id - mobile - short - no prefix - too long' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '814567890123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '62',
                '814567890123',
            ),
            'id - mobile - short - with prefix' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '5678901',
                    'nationalDestinationCode' => '0814',
                    'nationalDestinationCodeInternational' => '814',
                ),
                '62',
                '08145678901',
            ),
            'id - mobile - short - with prefix - longer' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '56789012',
                    'nationalDestinationCode' => '0814',
                    'nationalDestinationCodeInternational' => '814',
                ),
                '62',
                '081456789012',
            ),
            'id - mobile - short - with prefix - too long' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '0814567890123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '62',
                '0814567890123',
            ),

            //id - landlines
            'id - landline - short - no prefix' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '021',
                    'nationalDestinationCodeInternational' => '21',
                ),
                '62',
                '211234567',
            ),
            'id - landline - short - no prefix - longer' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '021',
                    'nationalDestinationCodeInternational' => '21',
                ),
                '62',
                '2112345678',
            ),
            'id - landline - short - no prefix - too long' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '21123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '62',
                '21123456789',
            ),

            // id - landlines - with prefix
            'id - landline - short - with prefix' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '021',
                    'nationalDestinationCodeInternational' => '21',
                ),
                '62',
                '0211234567',
            ),
            'id - landline - short - with prefix - longer' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '021',
                    'nationalDestinationCodeInternational' => '21',
                ),
                '62',
                '02112345678',
            ),
            'id - landline - short - with prefix - too long' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '21123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '62',
                '021123456789',
            ),


            //id - landlines - long
            'id - landline - long - no prefix' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                ),
                '62',
                '2521234567',
            ),
            'id - landline - long - no prefix - longer' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                ),
                '62',
                '25212345678',
            ),
            'id - landline - long - no prefix - too long' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '252123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '62',
                '252123456789',
            ),

            // id - landlines - with prefix
            'id - landline - long - with prefix' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                ),
                '62',
                '02521234567',
            ),
            'id - landline - long - with prefix - longer' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                ),
                '62',
                '025212345678',
            ),
            'id - landline - long - with prefix - too long' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '0252123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '62',
                '0252123456789',
            ),

            // singapore - not defined
            'outside defined params - sg' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
                '65',
                '123',
            ),
        );
    }

    /**
     * @dataProvider provideFormatByDigitCount
    **/
    public function testFormatByDigitCount($expected, $E194)
    {
        $this->initRegionFormatters($this->formatter);

        $actual = $this->formatter->formatByDigitCount($E194);
        $this->assertEquals($expected, $actual);
    }

    public function provideFormatByDigitCount()
    {
        return array(
            'id - no code - length test - 6 digit' => array(
                '+62 123 456',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - 6 digit' => array(
                '+62 252 123',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - 7 digit' => array(
                '+62 123 4567',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - 7 digit' => array(
                '+62 252 1234',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - 8 digit' => array(
                '+62 1234 5678',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - 8 digit' => array(
                '+62 2521 2345',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - 9 digit' => array(
                '+62 1 2345 6789',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - 9 digit' => array(
                '+62 2 5212 3456',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - 10 digit' => array(
                '+62 12 3456 7890',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567890',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - 10 digit' => array(
                '+62 25 2123 4567',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - 11 digit' => array(
                '+62 1 23 4567 8901',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678901',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - 11 digit' => array(
                '+62 2 52 1234 5678',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - > 11 digit' => array(
                '+62 12 34 56 78 90 11 23 4567 8901',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567890112345678901',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - > 11 digit' => array(
                '+62 2 52 12 34 56 78 90 11 23 4567 8901',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567890112345678901',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),


            'my - no code - length test - 7 digits' => array(
                '+60 123 4567',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - 7 digits' => array(
                '+60 101 2345',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 8 digits' => array(
                '+601 234 5678',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - 8 digits' => array(
                '+601 012 3456',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 9 digits' => array(
                '+6012 345 6789',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),                
            ),
            'my - with code - length test - 9 digits' => array(
                '+6010 123 4567',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 10 digits' => array(
                '+6012 3456 7890',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567890',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - 10 digits' => array(
                '+6010 1234 5678',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 11 digits' => array(
                '+60123 4567 8901',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678901',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - 11 digits' => array(
                '+60101 2345 6789',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456789',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - > 11 digits' => array(
                '+601234 5678 9012',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456789012',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - > 11 digits' => array(
                '+601012 3456 7890',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567890',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),



            'singapore - not defined' => array(
                '+62123',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),            
        );
    }
}
