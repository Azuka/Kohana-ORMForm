<?php defined('SYSPATH') or die('No direct script access.');

$states = array('None'=>'','AL'=>"Alabama",
			'AK'=>"Alaska",
			'AZ'=>"Arizona",
			'AR'=>"Arkansas",
			'CA'=>"California",
			'CO'=>"Colorado",
			'CT'=>"Connecticut",
			'DE'=>"Delaware",
			'DC'=>"District Of Columbia",
			'FL'=>"Florida",
			'GA'=>"Georgia",
			'HI'=>"Hawaii",
			'ID'=>"Idaho",
			'IL'=>"Illinois",
			'IN'=>"Indiana",
			'IA'=>"Iowa",
			'KS'=>"Kansas",
			'KY'=>"Kentucky",
			'LA'=>"Louisiana",
			'ME'=>"Maine",
			'MD'=>"Maryland",
			'MA'=>"Massachusetts",
			'MI'=>"Michigan",
			'MN'=>"Minnesota",
			'MS'=>"Mississippi",
			'MO'=>"Missouri",
			'MT'=>"Montana",
			'NE'=>"Nebraska",
			'NV'=>"Nevada",
			'NH'=>"New Hampshire",
			'NJ'=>"New Jersey",
			'NM'=>"New Mexico",
			'NY'=>"New York",
			'NC'=>"North Carolina",
			'ND'=>"North Dakota",
			'OH'=>"Ohio",
			'OK'=>"Oklahoma",
			'OR'=>"Oregon",
			'PA'=>"Pennsylvania",
			'RI'=>"Rhode Island",
			'SC'=>"South Carolina",
			'SD'=>"South Dakota",
			'TN'=>"Tennessee",
			'TX'=>"Texas",
			'UT'=>"Utah",
			'VT'=>"Vermont",
			'VA'=>"Virginia",
			'WA'=>"Washington",
			'WV'=>"West Virginia",
			'WI'=>"Wisconsin",
			'WY'=>"Wyoming",
			'Other Country'=>"Other Country"
);

/*=====================================================================================*/

$config['states']     = $states;
$config['months']     = array('0' => 'None') + array_combine(range(1, 12), array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'));
$config['days']       = array('0' => 'None') + array_combine(range(1, 31), range(1, 31));
$config['years']      = array('0' => 'None') + array_combine(range(date('Y') - 60, date('Y')), range(date('Y') - 60, date('Y')));
$config['hours']      = array_combine(range(1, 12), ORM_Form::zerofill(range(1, 12)));
$config['minutes']    = array_combine(range(0, 59), ORM_Form::zerofill(range(0, 59)));
$config['meridiens']  = array('AM'=>'AM', 'PM'=>'PM');

$config['bulk']   = array(
    'default' => array(
        ''       => 'Checkbox Action Menu',
        'delete' => 'Delete',
    ),
    'requests' => array(
        ''     => 'Checkbox Action Menu',
        'deny' => 'Deny',
    ),
    'approvedeny' => array(
        ''     => 'Checkbox Action Menu',
        'approve' => 'Approve',
        'deny' => 'Deny',
    ),
);

$config['monthnames'] = array(
    'jan' => 'January',
    'feb' => 'February',
    'mar' => 'March',
    'apr' => 'April',
    'may' => 'May',
    'jun' => 'June',
    'jul' => 'July',
    'aug' => 'August',
    'sep' => 'September',
    'oct' => 'October',
    'nov' => 'November',
    'dec' => 'December',
);
$config['yearnames'] = array_combine(range(date('Y') - 10, date('Y') + 10), range(date('Y') - 10, date('Y') + 10));

return $config;