<?php

namespace Guru\PhoneNumberFormatterBundle\Tests\Formatter;

use Guru\PhoneNumberFormatterBundle\Formatter\Formatter;
use Guru\PhoneNumberFormatterBundle\Formatter\FormatterMy;
use Guru\PhoneNumberFormatterBundle\Formatter\FormatterId;
use Guru\PhoneNumberFormatterBundle\Formatter\FormatterTh;
use Guru\PhoneNumberFormatterBundle\Formatter\FormatterSg;
use Guru\PhoneNumberFormatterBundle\Model\PhoneNumber;
use \Mockery as m;

/**
 * @group unit
 * @group formatter
 */
class FormatterTest extends \PHPUnit_Framework_TestCase
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
            //malaysia
            'guru_phone_number_formatter.format.my.prefix.landline.short' => array(
                '2' => '02',
                '9' => '09',
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

            //indonesia
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

            //thailand
            'guru_phone_number_formatter.format.th.landline.length' => 9,
            'guru_phone_number_formatter.format.th.prefix.landline' => array(
                2 => '02',
                32 => '032',
            ),
            'guru_phone_number_formatter.format.th.mobile.length' => 10,
            'guru_phone_number_formatter.format.th.prefix.mobile' => array(
                '80' => '080',
                '91' => '091',
            ),

            //singapore
            'guru_phone_number_formatter.format.sg.length' => 8,
            'guru_phone_number_formatter.format.sg.prefix.voip' => 3,
            'guru_phone_number_formatter.format.sg.prefix.landline' => 6,
            'guru_phone_number_formatter.format.sg.mobile.rules' => array(
                '8' => '8[1-9]',
                '9' => '9[0-8]',
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
            'ph' => '63',
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

        $regionFormatter = new FormatterTh($this->container);
        $regionFormatter->setLandlineNumberLength($this->container->getParameter('guru_phone_number_formatter.format.th.landline.length'));
        $regionFormatter->setLandlinePrefixCodes($this->container->getParameter('guru_phone_number_formatter.format.th.prefix.landline'));
        $regionFormatter->setMobileNumberLength($this->container->getParameter('guru_phone_number_formatter.format.th.mobile.length'));
        $regionFormatter->setMobilePrefixCodes($this->container->getParameter('guru_phone_number_formatter.format.th.prefix.mobile'));
        $formatter->addRegionFormatter('th', $regionFormatter);

        $regionFormatter = new FormatterSg($this->container);
        $regionFormatter->setNumberLength($this->container->getParameter('guru_phone_number_formatter.format.sg.length'));
        $regionFormatter->setLandlinePrefix($this->container->getParameter('guru_phone_number_formatter.format.sg.prefix.voip'));
        $regionFormatter->setVoipPrefix($this->container->getParameter('guru_phone_number_formatter.format.sg.prefix.landline'));
        $regionFormatter->setMobileRules($this->container->getParameter('guru_phone_number_formatter.format.sg.mobile.rules'));
        $formatter->addRegionFormatter('sg', $regionFormatter);

        return $formatter;
    }

    /**
     * @dataProvider provideNumberToE164
    **/
    public function testNumberToE164($expected, $countryCode, $number)
    {
        $this->initRegionFormatters($this->formatter);

        $actual = $this->formatter->numberToE164($number, $countryCode);
        $this->assertEquals($expected, $actual->toArray());
    }

    public function provideNumberToE164()
    {
        return array(
            //my
            'my - outside defined params' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => true,
                    'isValid' => true,
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
                    'isMobile' => true,
                    'isValid' => true,
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
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '60',
                '01112345678',
            ),
            'my - contains embeded country code - valid number' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '62',
                '+60101234567',
            ),
            'my - contains embeded country code - invalid number - valid indonesian' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '5678901',
                    'nationalDestinationCode' => '0814',
                    'nationalDestinationCodeInternational' => '814',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '62',
                '+608145678901',
            ),

            // id
            'id - outside defined params' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => true,
                    'isValid' => true,
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
                    'isMobile' => true,
                    'isValid' => true,
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
                    'isMobile' => false, // not mobile because it's not recognised
                    'isValid' => false,
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
                    'isMobile' => true,
                    'isValid' => true,
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
                    'isMobile' => true,
                    'isValid' => true,
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
                    'isMobile' => false, // not mobile because it's not recognised
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '62',
                '02112345678',
            ),
            'id - landline - short - with prefix - too long' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '021123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => false,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => true,
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
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '62',
                '0252123456789',
            ),
            'id - contains embeded country code - valid number' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '60',
                '+6225212345678',
            ),
            'id - contains embeded country code - invalid number - valid malaysian' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '60',
                '+62101234567',
            ),

            //th
            'th - outside defined params' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '66',
                '123',
            ),
            'th - landline - single digit' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '66',
                '21234567',
            ),
            'th - landline - single digit - long' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '02',
                    'nationalDestinationCodeInternational' => '2',
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '66',
                '021234567',
            ),
            'th - landline - multi digit' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '032',
                    'nationalDestinationCodeInternational' => '32',
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '66',
                '32123456',
            ),
            'th - landline - multi digi - long' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '032',
                    'nationalDestinationCodeInternational' => '32',
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '66',
                '032123456',
            ),
            'th - landline - too long' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '03212345678789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '66',
                '03212345678789',
            ),

            //mobile
            'th - mobile - short' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '6493950',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '66',
                '806493950',
            ),
            'th - mobile - long' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '6493950',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '66',
                '0806493950',
            ),
            'th - mobile - too long' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '0806493950789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '66',
                '0806493950789',
            ),
            'th - contains embeded country code - valid number' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '6493950',
                    'nationalDestinationCode' => '080',
                    'nationalDestinationCodeInternational' => '80',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '60',
                '+66806493950',
            ),
            'th - contains embeded country code - invalid number - valid malaysian' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '60',
                '+66101234567',
            ),

            //landline
            'sg - landline' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '61234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '65',
                '61234567',
            ),
            'sg - voip' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '31234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '65',
                '31234567',
            ),
            'sg - mobile - starts with 8 - ok' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '81234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '65',
                '81234567',
            ),
            'sg - mobile - starts with 8 - not ok' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '80234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '65',
                '80234567',
            ),

            'sg - mobile - starts with 9 - ok' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '91234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '65',
                '91234567',
            ),
            'sg - mobile - starts with 9 - not ok' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '99234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '65',
                '99234567',
            ),
            'sg - contains embeded country code - valid number' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '91234567',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '60',
                '+6591234567',
            ),
            'sg - contains embeded country code - invalid number - valid malaysian' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '60',
                '+65101234567',
            ),

            // us - not defined
            'outside defined params - country not defined - us' => array(
                array(
                    'countryCode' => '1',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '1',
                '123',
            ),
            'outside defined params - country defined - missing algo - contains embedded country code - philippines' => array(
                array(
                    'countryCode' => '63',
                    'subscriberNumber' => '123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '62',
                '+63123456789',
            ),
        );
    }

    /**
     * @dataProvider provideFormatByDigitCount
    **/
    public function testFormatByDigitCount($expected, $E164)
    {
        $this->initRegionFormatters($this->formatter);

        $phoneNumber = new PhoneNumber();
        $phoneNumber->setCountryCode($E164['countryCode']);
        $phoneNumber->setSubscriberNumber($E164['subscriberNumber']);
        $phoneNumber->setNationalDestinationCode($E164['nationalDestinationCode']);
        $phoneNumber->setNationalDestinationCodeInternational($E164['nationalDestinationCodeInternational']);

        $actual = $this->formatter->formatByDigitCount($phoneNumber);
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
                '+62 252 12345',
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
                '+62 252 123 456',
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
                '+62 252 123 4567',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - 11 digit' => array(
                '+62 123 4567 8901',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678901',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - 11 digit' => array(
                '+62 252 1234 5678',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '0252',
                    'nationalDestinationCodeInternational' => '252',
                )
            ),

            'id - no code - length test - > 11 digit' => array(
                '+62 12 3456 7890 1123 4567 8901',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '1234567890112345678901',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
            'id - with code - length test - > 11 digit' => array(
                '+62 252 12 3456 7890 1123 4567 8901',
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
                '+60 10 12345',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 8 digits' => array(
                '+60 1234 5678',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - 8 digits' => array(
                '+60 10 123 456',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 9 digits' => array(
                '+60 1 2345 6789',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456789',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),                
            ),
            'my - with code - length test - 9 digits' => array(
                '+60 10 123 4567',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 10 digits' => array(
                '+60 12 3456 7890',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567890',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - 10 digits' => array(
                '+60 10 1234 5678',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - 11 digits' => array(
                '+60 123 4567 8901',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '12345678901',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - 11 digits' => array(
                '+60 10 1 2345 6789',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456789',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'my - no code - length test - > 11 digits' => array(
                '+60 1234 5678 9012',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '123456789012',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                ),
            ),
            'my - with code - length test - > 11 digits' => array(
                '+60 10 12 3456 7890',
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '1234567890',
                    'nationalDestinationCode' => '010',
                    'nationalDestinationCodeInternational' => '10',
                ),
            ),

            'singapore - not defined' => array(
                '+62 123',
                    array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                )
            ),
        );
    }

    /**
     * @dataProvider provideNumberToE164CountryWeights
    **/
    public function testNumberToE164CountryWeights($expected, $countryCode, $number, $regionCode, $countryWeights)
    {
        $this->initRegionFormatters($this->formatter);

        $this->formatter->setDefaultRegionCode($regionCode);
        $this->formatter->setCountryCodeWeights($countryWeights);

        $actual = $this->formatter->numberToE164($number, $countryCode);
        $this->assertEquals($expected, $actual->toArray());
    }

    public function provideNumberToE164CountryWeights()
    {
        return array(
            'both fail - use heaviest' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '60',
                '123',
                'id',
                array(
                    '60' => 1,
                    '62' => 2,
                )
            ),
            'both fail - same weight - use provided country code' => array(
                array(
                    'countryCode' => '62',
                    'subscriberNumber' => '123',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => false,
                    'isValid' => false,
                ),
                '62',
                '123',
                'my',
                array(
                    '60' => 1,
                    '62' => 1,
                )
            ),

            'one matched - use matched' => array(
                array(
                    'countryCode' => '65',
                    'subscriberNumber' => '81234568',
                    'nationalDestinationCode' => null,
                    'nationalDestinationCodeInternational' => null,
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '65',
                '81234568',
                'id',
                array(
                    '60' => 1,
                    '62' => 2,
                    '65' => 0,
                    '66' => 1000000,
                )
            ),
            'two matched - same weight - use provided' => array(
                array(
                    'countryCode' => '66',
                    'subscriberNumber' => '1234567',
                    'nationalDestinationCode' => '091',
                    'nationalDestinationCodeInternational' => '91',
                    'isMobile' => true,
                    'isValid' => true,
                ),
                '66',
                '0911234567',
                'my',
                array(
                    '60' => 1,
                    '62' => 1,
                    '65' => 1,
                    '66' => 1,
                )
            ),
            'two matched - different weight - use provided' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '11234567',
                    'nationalDestinationCode' => '09',
                    'nationalDestinationCodeInternational' => '9',
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '66',
                '0911234567',
                'my',
                array(
                    '60' => 2,
                    '62' => 1,
                    '65' => 1,
                    '66' => 1,
                )
            ),
            'two matched - has embeded code - different weight - use region' => array(
                array(
                    'countryCode' => '60',
                    'subscriberNumber' => '11234567',
                    'nationalDestinationCode' => '09',
                    'nationalDestinationCodeInternational' => '9',
                    'isMobile' => false,
                    'isValid' => true,
                ),
                '66',
                '+65911234567',
                'my',
                array(
                    '60' => 2,
                    '62' => 1,
                    '65' => 1,
                    '66' => 1,
                )
            ),
        );
    }    
}
