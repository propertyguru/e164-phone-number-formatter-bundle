# E194-phone-number-formatter-bundle
Bundle to format phone numbers in E194 format

## Examples
$formatter = $this->container->get('guru_phone_number_formatter.formatter');

### 
$e194 = $formatter->numberToE194('010123456789', '60');
var_export($e194);

array (
  'countryCode' => '60',
  'subscriberNumber' => '123456789',
  'nationalDestinationCode' => '010',
  'nationalDestinationCodeInternational' => '10',
)