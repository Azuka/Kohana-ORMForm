<?php defined('SYSPATH') or die('No direct access allowed');
/**
 * ORM Form
 *
 * @package    ORM
 * @author     Azuka Okuleye
 * @copyright  (c) 2009 CongregateOnline.com
 * @license    http://zatechcorp.com/license.html
 * @tutorial   <code>$test = ORM::factory('test');<br />$test->setup_form($_POST);<br />$test->generate_form('field1', 'field2', field3');</code>
 *
 * @method Kohana_ORM_Form where() where(string $column, string $operator, mixed $value)
 */
class Kohana_ORM_Form extends ORM
{
    /**
     * Where to get error messages for the model
     * @var string
     */
    protected $_error_config = NULL;
    /**
     * Form labels
     * @var array
     */
    protected $_form_labels  = array();
    /**
     * Dropdowns for fields
     * @var array
     */
    protected $_choices      = array();
    /**
     * Radio button set
     * @var array
     */
    protected $_radio        = array();
    /**
     * Checkbox set
     * @var array
     */
    protected $_checkboxes   = array();
    /**
     * The list of password columns.
     * @todo Allow options to generate a confirmation form field for passwords and emails
     * @var array
     */
    protected $_password   = array();
    /**
     * The list of columns. I didn't want to make it private
     * @var array
     */
    protected $_orm_columns  = array();
    /**
     * Fields to exclude from the form
     * @var array
     */
    protected $_exclude      = array('id' ,'created', 'modified');
    /**
     * Allow none
     * @var array
     * @todo Remove 'None' from dropdowns for any dates not defined as allowing nothing
     */
    protected $_date_none    = array();
    /**
     * Exclude the month dropdown.
     * @todo remove or use
     * @var array
     */
    protected $_date_ex_m    = array();
    /**
     * Exclude the day dropdown
     * @var array
     */
    protected $_date_ex_d    = array();
    /**
     * Exclude the year dropdown
     * @var array
     */
    protected $_date_ex_y    = array();
    /**
     * Extra attributes for form fields
     * @var array
     */
    protected $_attributes   = array();
    /**
     * Field sizes
     * @var array
     * @todo Remove. Can be handled by attributes
     */
    protected $_field_sizes  = array();

    /**
     * Name of object
     * @var string
     */
    protected $_orm_name     = NULL;


    /**
     * Form values before comitting to orm
     * @var array
     * @todo Remember why this is even necessary. For all I remember, it works well whether $object->setup_form($_POST) is there or not
     *
     */
    protected $_form           = array();
    /**
     * Init called.
     * @var boolean
     */
    protected $_inited         = false;

    /**
     * Have any changes been made to this?
     * @return boolean
     */
    public function changed()
    {
        return !empty($this->_changed);
    }

    /**
     * Quick and dirty way to take off any content from the form labels.
     * @param string $text
     * @return string
     * @todo Remove this, and implement something simpler to use
     */
    protected function _format_labels($text)
    {
        return ucfirst(trim(preg_replace(
            array(
                '@<span[^>]*?.*?</span>@siu',
            ),
            array(
                '',
            ),
            $text )
        ));
    }

    /**
     * Get a list of errors after validating
     * @return string Error message
     * @usage <code>$this->get_form()->errors();</code>
     * @todo Fix the ugly mess from lines 150-157
     */
    public function errors($callback = 'Alert::error')
    {        
        //$errors = $this->validate()->errors($this->_error_config);
        $errors   = $this->validate()->errors();
        $messages = array();

        $errors_trans = $this->validate()->errors(TRUE);

        foreach ($errors as $index=>$error)
        {
            $messages[$index] = Kohana::message(
                'models/' . $this->_error_config,
                $index . '.' . $error[0],
                str_replace(
                    array('%field', '%value'),
                    array(ucfirst(Arr::get($this->_form_labels, $index, Inflector::humanize($index))), $this->$index),
                    Kohana::message('models/default', $error[0], Arr::get($errors_trans, $index))
                )
            );
        }

        if ($callback && is_callable($callback))
            return join("\n", array_map($callback, $messages));
        return $messages;
    }

    /**
     * Useful for initializing whatever fields necessary, especially for foreign keys
     * Called in _fill_columns
     * @usage <code>$this->_choices['book_id'] = ORM::factory('book')->find_all()->as_array('id', 'name');</code>
     */
    protected function _init()
    {}

    /**
     * Fill the names of columns for form generation
     */
    protected function _fill_columns()
    {
        if (empty($this->_orm_columns))
        {
            // Load database columns and convert to lowercase
            $this->_orm_columns = array_map('array_change_key_case',
                Database::instance()
                ->query(Database::SELECT, 'SHOW COLUMNS FROM '. Database::instance()->table_prefix() . $this->_table_name, false)
                ->as_array('Field')
            );
        }
        if (!$this->_inited)
        {
            if (!$this->_orm_name)
                $this->_orm_name = $this->_object_name;
            $this->_init();
            // Get friendlier names
            foreach ($this->_object as $column=>$value)
            {
                if (!isset($this->_form_labels[$column]))
                {
                    // Quicker than Inflector::humanize, except you're using stranger symbols than underscore in your table names
                    $this->_labels[$column] = ucfirst(str_replace('_', ' ', $column));
                }
            }

            $this->_labels += array_map(array($this, '_format_labels'), $this->_form_labels);
            $this->_inited = true;
        }
    }

    /**
     * Get all the columns. Init when necessary
     * @return array
     */
    public function orm_columns()
    {
        $this->_fill_columns();
        return $this->_orm_columns;
    }

    /**
     * Setup the form fields
     * @param array|NULL $form
     * @return ORM_Form
     * @chainable
     */
    public function setup_form($form = NULL)
    {
        if ($form === NULL)
        {
            $this->_form = $this->as_array();
        }
        else
        {
            $this->_form = (array) ($this->loaded() ? Arr::get($form, $this->_orm_name) : Arr::path($form, $this->_orm_name.'.'.$this->pk()));
        }

        return $this;
    }

    /**
     * Set an attribute's value
     * @param string $field
     * @param string $attribute
     * @param string $value
     * @chainable
     */
    public function set_attribute($field, $attribute, $value)
    {
        $this->_attributes[$field][$attribute] = $value;
        return $this;
    }

    /**
     * Field form name
     * @param string $field
     * @return string
     */
    public function field_name($field)
    {
        return $this->empty_pk()
                ?
                $this->_orm_name . '[' . $field . ']'
                :
                $this->_orm_name . '[' . $this->pk() . '][' . $field . ']';
    }

    /**
     * Path in form array
     * @param string $field
     * @return string
     */
    public function field_path($field)
    {
        return $this->empty_pk()
                ?
                $this->_orm_name . '.' . $field
                :
                $this->_orm_name . '.' . $this->pk() . '.' . $field;
    }

    /**
     * Form ID
     * @param string $field
     * @return string
     */
    public function field_id($field)
    {
        return str_replace('.', '_', $this->field_path($field));
    }

    /**
     * Generate the form field for this
     * @param string $field
     * @param string $value
     * @param array $attributes
     * @return string
     * @todo Maybe create a helper class, or functions that help define new types and handlers for fields. I was going to use views but that's overkill, especially when thinking about the performance requirements of include()
     */
    public function form_field($field, $value, $attributes = array())
    {
        $column     = Arr::get($this->orm_columns(), $field);
        $attributes = (array) $attributes;
        // This should throw an exception if the field is not in the table
        extract($column);
        // Choices set
        if (isset($this->_choices[$field]))
        {
            return Form::select(
                $this->field_name($field),
                $this->_choices[$field],
                $value,
                $attributes +
                array(
                    'id' => $this->field_id($field)
                )
            );
        }
        // Radio buttons
        elseif (isset($this->_radio[$field]))
        {
            $checkboxes = '';
            $i = 0;
            foreach ($this->_radio[$field] as $checkbox)
            {
                $checkboxes .= '<span class="checkboxlist">';
                $checkboxes .= Form::radio(
                    $this->field_name($field),
                    $checkbox['value'],
                    $checkbox['value'] === $value,
                    Arr::get($checkbox, 'attributes', array()) +
                    array(
                        'id' => $this->field_id($field) . ($i === 0 ? '' : '_' . $i),
                    )
                );
                $checkboxes .= ' ' . Form::label(
                    $this->field_id($field) . ($i === 0 ? '' : '_' . $i),
                    $checkbox['label'],
                    Arr::get($checkbox, 'lattributes', array()) +
                    array(
                        'class' => 'inline',
                    )
                ) . '</span> ';
                $i++;
            }

            return $checkboxes;
        }
        // Checkboxes
        elseif (isset($this->_checkboxes[$field]))
        {
            $checkboxes = '';
            $i = 0;
            foreach ($this->_checkboxes[$field] as $checkbox)
            {
                $checkboxes .= '<span class="checkboxlist">';
                $checkboxes .= Form::checkbox(
                    $this->field_name($field) . '[]',
                    $checkbox['value'],
                    is_array($value) ? in_array($checkbox['value'], $value) : in_array($checkbox['value'], explode('/', $value)),
                    Arr::get($checkbox, 'attributes', array()) +
                    array(
                        'id' => $this->field_id($field) . ($i === 0 ? '' : '_' . $i),
                    )
                );
                $checkboxes .= ' ' . Form::label(
                    $this->field_id($field) . ($i === 0 ? '' : '_' . $i),
                    $checkbox['label'],
                    Arr::get($checkbox, 'lattributes', array()) +
                    array(
                        'class' => 'inline',
                    )
                ) . '</span> ';
                $i++;
            }

            return $checkboxes;

        }
        // Tinyint. Boolean == Checkbox
        elseif (strpos($type, 'tinyint') !== FALSE)
        {
            return Form::checkbox(
                $this->field_name($field),
                1,
                (bool) $value,
                $attributes +
                array(
                    'id' => $this->field_id($field),
                    'class' => 'checkbox'
                )
            );
        }
        // Textarea
        elseif (strpos($type, 'text') !== FALSE)
        {
            return Form::textarea(
                $this->field_name($field),
                $value,
                $attributes +
                array(
                    'id' => $this->field_id($field),
                    'rows' => 5,
                    'cols' => 40,
                )
            );
        }
        // Date field
        elseif ($type === 'date')
        {
            if ($value != '0000-00-00' && $value)
            {
                $month = (int) date('n', strtotime($value));
                $day   = (int) date('j', strtotime($value));
                $year  = (int) date('Y', strtotime($value));
            }
            elseif (!in_array($field, $this->_date_none))
            {
                $month = (int) date('n');
                $day   = (int) date('j');
                $year  = (int) date('Y');
            }
			else
			{
				$year  = (int) date('Y');
			}
            return Form::select(
                    $this->field_name($field) . '[month]',
                    Kohana::config('formo.months'),
                    $month,
                    $attributes +
                    array(
                        'id' => $this->field_id($field),
                    )
                )
                . ' ' .
                ((in_array($field, $this->_date_ex_d)) ?
                    Form::hidden(
                        $this->field_name($field) . '[day]',
                        1,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_day',
                        )
                    )
                    :
                    Form::select(
                        $this->field_name($field) . '[day]',
                        Kohana::config('formo.days'),
                        $day,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_day',
                        )
                    )
                )
                . ' ' .
                ((in_array($field, $this->_date_ex_y)) ?
                    Form::hidden(
                        $this->field_name($field) . '[year]',
                        $year,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_year',
                        )
                    )
                    :
                    Form::select(
                        $this->field_name($field) . '[year]',
                        Kohana::config('formo.years'),
                        $year,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_year',
                        )
                    )
                );
        }
        // Datetime field
        elseif ($type === 'datetime')
        {
            if ($value != '0000-00-00 00:00:00' && $value)
            {
                $month    = (int) date('n', strtotime($value));
                $day      = (int) date('j', strtotime($value));
                $year     = (int) date('Y', strtotime($value));
                $hour     = (int) date('h', strtotime($value));
                $minute   = (int) date('i', strtotime($value));
                $meridien = (int) date('A', strtotime($value));
            }
            elseif (!in_array($field, $this->_date_none))
            {
                $month    = (int) date('n');
                $day      = (int) date('j');
                $year     = (int) date('Y');
                $hour     = (int) date('h', strtotime($value));
                $minute   = (int) date('i', strtotime($value));
                $meridien = (int) date('A', strtotime($value));
            }
            return Form::select(
                    $this->field_name($field) . '[month]',
                    Kohana::config('formo.months'),
                    $month,
                    $attributes +
                    array(
                        'id' => $this->field_id($field),
                    )
                )
                . ' ' .
                ((in_array($field, $this->_date_ex_d)) ?
                    Form::hidden(
                        $this->field_name($field) . '[day]',
                        1,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_day',
                        )
                    )
                    :
                    Form::select(
                        $this->field_name($field) . '[day]',
                        Kohana::config('formo.days'),
                        $day,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_day',
                        )
                    )
                )
                . ' ' .
                ((in_array($field, $this->_date_ex_y)) ?
                    Form::hidden(
                        $this->field_name($field) . '[year]',
                        $year,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_year',
                        )
                    )
                    :
                    Form::select(
                        $this->field_name($field) . '[year]',
                        Kohana::config('formo.years'),
                        $year,
                        $attributes +
                        array(
                            'id' => $this->field_id($field) . '_year',
                        )
                    )
                )
				. ' ' .
				Form::select(
                    $this->field_name($field) . '[hour]',
                    (Kohana::config('formo.hours')),
                    $hour,
                    $attributes +
                    array(
                        'id' => $this->field_id($field),
                    )
                )
                . ' ' .
                Form::select(
                    $this->field_name($field) . '[minute]',
                    (Kohana::config('formo.minutes')),
                    $minute,
                    $attributes +
                    array(
                        'id' => $this->field_id($field) . '_minute',
                    )
                )
                . ' ' .
                Form::select(
                    $this->field_name($field) . '[meridien]',
                    Kohana::config('formo.meridiens'),
                    $meridien,
                    $attributes +
                    array(
                        'id' => $this->field_id($field) . '_meridien',
                    )
                );
        }
        // Time field
        elseif ($type === 'time')
        {		
            if (/*$value != '00:00:00' && */$value)
            {
                $hour     = (int) date('h', strtotime('5/2/1968 ' . $value));
                $minute   = (int) date('i', strtotime('5/2/1968 ' . $value));
                $meridien = date('A', strtotime('5/2/1968 ' . $value));
            }
            elseif (!in_array($field, $this->_date_none))
            {
                $hour     = date('h');
                $minute   = date('i');
                $meridien = date('A');
            }
            return Form::select(
                    $this->field_name($field) . '[hour]',
                    (Kohana::config('formo.hours')),
                    $hour,
                    $attributes +
                    array(
                        'id' => $this->field_id($field),
                    )
                )
                . ' ' .
                Form::select(
                    $this->field_name($field) . '[minute]',
                    (Kohana::config('formo.minutes')),
                    $minute,
                    $attributes +
                    array(
                        'id' => $this->field_id($field) . '_minute',
                    )
                )
                . ' ' .
                Form::select(
                    $this->field_name($field) . '[meridien]',
                    Kohana::config('formo.meridiens'),
                    $meridien,
                    $attributes +
                    array(
                        'id' => $this->field_id($field) . '_meridien',
                    )
                );
        }
        elseif (in_array($field, $this->_password))
        {
            return Form::password(
                $this->field_name($field),
                $value,
                $attributes +
                array(
                    'id' => $this->field_id($field),
                    'class' => 'textbox',
                    'size'  => Arr::get($this->_field_sizes, $field, 45),
                )
            );
        }
        // All others
        else
        {
            return Form::input(
                $this->field_name($field),
                $value,
                $attributes +
                array(
                    'id' => $this->field_id($field),
                    'class' => 'textbox',
                    'size'  => Arr::get($this->_field_sizes, $field, 45),
                )
            );
        }
    }

    /**
     *
     * @param string $field
     * @param array $attributes
     * @return string
     */
    public function form_label($field, array $attributes = array())
    {
        return isset($this->_form_labels[$field]) ?

        Form::label($this->field_id($field), ucwords($this->_form_labels[$field]), $attributes)
        :
        Form::label($this->field_id($field), ucwords(Inflector::humanize($field)), $attributes);
    }

    /**
     * Load values from _POST
     * @return $this
     * @chainable
     */
    public function get_form()
    {
        foreach ($this->orm_columns() as $column)
        {
            extract($column);
            
            if (isset($this->_checkboxes[$field]))
            {
                $value = Arr::path($_POST, $this->field_path($field));
                if (is_array($value))
                {
                    $value = join('/', $value);
                }
                $this->$field = $value;
                continue;
            }

            if ($this->empty_pk() && !isset($_POST[$this->_orm_name][$field]) && strpos($type, 'tinyint') === false && !in_array($type, array('date', 'datetime', 'time')))
                continue;
            elseif ($this->pk() && !isset($_POST[$this->_orm_name][$this->pk()][$field]) && strpos($type, 'tinyint') === false && !in_array($type, array('date', 'datetime', 'time')))
                continue;

            if (in_array($field, $this->_exclude))
                continue;

            if ($type == 'date')
            {
                if (!(
                (int) Arr::path($_POST, $this->field_path($field) . '.year')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.month')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.day')
                ))
                {
                    $this->$field = '0000-00-00';
                }
                else
                {
                    $this->$field = date('Y-m-d',
                        mktime(
                        0,
                        0,
                        0,
                        (int) Arr::path($_POST, $this->field_path($field) . '.month'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.day'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.year')
                        )
                    );
                }

                continue;
            }
            if ($type == 'datetime')
            {
                if (!(
                (int) Arr::path($_POST, $this->field_path($field) . '.year')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.month')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.day')
                ))
                {
                    $this->$field = '0000-00-00 00:00:00';
                }
                else
                {
                    $hour = (int) Arr::path($_POST, $this->field_path($field) . '.hour') + (Arr::path($_POST, $this->field_path($field) . '.meridien') == 'AM' ? 0 : 12);

                    if ((int) Arr::path($_POST, $this->field_path($field) . '.hour') === 12)
                    {
                        if (Arr::path($_POST, $this->field_path($field) . '.meridien') == 'AM')
                        {
                            $hour = 0;
                        }
                        else
                        {
                            $hour = 12;
                        }
                    }

                    $this->$field = date('Y-m-d H:i:s',
                        mktime(
                        $hour,
                        (int) Arr::path($_POST, $this->field_path($field) . '.minute'),
                        0,
                        (int) Arr::path($_POST, $this->field_path($field) . '.month'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.day'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.year')
                        )
                    );
                }

                continue;
            }
            if ($type == 'time')
            {
                if (!Arr::path($_POST, $this->field_path($field) . '.meridien'))
                {
                    $this->$field = '00:00:00';
                }
                else
                {
                    $hour = (int) Arr::path($_POST, $this->field_path($field) . '.hour') + (Arr::path($_POST, $this->field_path($field) . '.meridien') == 'AM' ? 0 : 12);

                    if ((int) Arr::path($_POST, $this->field_path($field) . '.hour') === 12)
                    {
                        if (Arr::path($_POST, $this->field_path($field) . '.meridien') == 'AM')
                        {
                            $hour = 0;
                        }
                        else
                        {
                            $hour = 12;
                        }
                    }

                    $this->$field = date('H:i:s',
                        mktime(
                        $hour,
                        (int) Arr::path($_POST, $this->field_path($field) . '.minute'),
                        0
                    ));
                }

                continue;
            }
            if (strpos($type, 'tinyint') !== false && !isset($this->_choices[$field]))
            {
                $this->$field = Arr::path($_POST, $this->field_path($field)) ? 1 : 0;
                continue;
            }

            $this->$field = Arr::path($_POST, $this->field_path($field));
        }

        //echo Kohana::debug($_POST, $this->as_array());
        //exit;

        return $this;

    }

    /**
     * Load values from _POST
     * @return $this
     * @chainable
     * @todo Merge the definitions of get_only() and get_form()
     */
    public function get_only($args)
    {
        $args = func_get_args();

        foreach ($args as $arg)
        {
            $column = Arr::get($this->orm_columns(), $arg);
            if (!$column)
                continue;
            extract($column);

            if (isset($this->_checkboxes[$field]))
            {
                $value = Arr::path($_POST, $this->field_path($field));
                if (is_array($value))
                {
                    $value = join('/', $value);
                }
                $this->$field = $value;
                continue;
            }

            if (!isset($_POST[$field]) && strpos('tinyint', $type) === false && !in_array($type, array('date', 'datetime', 'time')))
                continue;

            if (in_array($field, $this->_exclude))
                continue;

            if ($type == 'date')
            {
                if (!(
                (int) Arr::path($_POST, $this->field_path($field) . '.year')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.month')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.day')
                ))
                {
                    $this->$field = '0000-00-00';
                }
                else
                {
                    $this->$field = date('Y-m-d',
                        mktime(
                        0,
                        0,
                        0,
                        (int) Arr::path($_POST, $this->field_path($field) . '.month'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.day'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.year')
                        )
                    );
                }

                continue;
            }
            if ($type == 'datetime')
            {
                if (!(
                (int) Arr::path($_POST, $this->field_path($field) . '.year')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.month')
                    &&
                    (int) Arr::path($_POST, $this->field_path($field) . '.day')
                ))
                {
                    $this->$field = '0000-00-00 00:00:00';
                }
                else
                {
                    $hour = (int) Arr::path($_POST, $this->field_path($field) . '.hour') + (Arr::path($_POST, $this->field_path($field) . '.meridien') == 'AM' ? 0 : 12);

                    if ((int) Arr::path($_POST, $this->field_path($field) . '.hour') === 12)
                    {
                        if (Arr::path($_POST, $this->field_path($field) . '.meridien') == 'AM')
                        {
                            $hour = 0;
                        }
                        else
                        {
                            $hour = 12;
                        }
                    }

                    $this->$field = date('Y-m-d H:i:s',
                        mktime(
                        $hour,
                        (int) Arr::path($_POST, $this->field_path($field) . '.minute'),
                        0,
                        (int) Arr::path($_POST, $this->field_path($field) . '.month'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.day'),
                        (int) Arr::path($_POST, $this->field_path($field) . '.year')
                        )
                    );
                }

                continue;
            }
            if ($type == 'time')
            {
                if (!Arr::path($_POST, $this->field_path($field) . '.meridien'))
                {
                    $this->$field = '00:00:00';
                }
                else
                {
                    $hour = (int) Arr::path($_POST, $this->field_path($field) . '.hour') + (Arr::path($_POST, $this->field_path($field) . '.meridian') == 'AM' ? 0 : 12);

                    if ((int) Arr::path($_POST, $this->field_path($field) . '.hour') === 12)
                    {
                        if (Arr::path($_POST, $this->field_path($field) . '.meridien') == 'AM')
                        {
                            $hour = 0;
                        }
                        else
                        {
                            $hour = 12;
                        }
                    }

                    $this->$field = date('H:i:s',
                        mktime(
                        $hour,
                        (int) Arr::path($_POST, $this->field_path($field) . '.minute'),
                        0
                    ));
                }

                continue;
            }
            if (strpos('tinyint', $type) !== false)
            {
                $this->$field = Arr::path($_POST, $this->field_path($field)) ? 1 : 0;
                continue;
            }

            $this->$field = Arr::path($_POST, $this->field_path($field));
        }

        return $this;

    }

    /**
     * Generate a form field along with the values and attributes
     * @param string $field
     * @return void
     * @todo Make this easier to extend
     */
    public function generate_field($field)
    {
        $form   = $this->_form;
        $column = Arr::get($this->orm_columns(), $field);
        extract($column);
        if ($extra == 'auto_increment' || in_array($field, $this->_exclude)) return;
        if (!is_array($form)) return;
        ?>
        <div class="form-field <?php echo $field; ?>" id="<?php echo 'field_' . $this->field_id($field); ?>">
            <?php echo $this->form_label($field); ?>
            <div class="form-field-wrap"><?php echo $this->form_field($field, Arr::get($form, $field), Arr::get($this->_attributes, $field, array())); ?></div>
        </div>
        <?php
    }

    /**
     * Generate a form based on information from array
     * @param mixed $columns,...
     */
    public function generate_form($columns = NULL)
    {
        $form = $this->_form;

        $args = func_get_args();

        if (empty($args) || $columns === NULL)
        {
            foreach ($this->orm_columns() as $field=>$column)
            {
                $this->generate_field($field);
            }
            return;
        }
        elseif (is_array($columns))
        {
            foreach ($columns as $field)
            {
                $this->generate_field($field);
            }
            return;
        }
        else
        {
            foreach ($args as $field)
            {
                $this->generate_field($field);
            }
            return;
        }
    }

    /**
     * Exclude field (temporarily)
     * @param string $field
     * @return ORM_Form
     * @usage <code>$object->exclude('book_id');</code>
     */
    public function exclude($field)
    {
        $this->_exclude[] = $field;
        return $this;
    }

    /**
     * Date last modified
     * @return date
     * Quick and dirty. For when your auto created and updated columns are called created and modified respectively
     */
    public function modified()
    {
        return $this->modified == '0000-00-00 00:00:00' ? $this->created : $this->modified;
    }

    /**
     * Prevent negative offsets in MySQL
     * @param int $offset
     * @return $this
     * @chainable
     */
    public function offset($offset)
    {
        if ($offset < 0)
            $offset = 0;

        return parent::offset($offset);
    }

    /**
     * Count the number of records returned by the last query
     * @return int
     */
    public function count_last_query()
    {
        $sql = $this->last_query();
        if ($sql)
        {
            if (stripos($sql, 'LIMIT') !== FALSE)
            {
                // Remove LIMIT from the SQL
                //$sql = preg_replace('/sLIMITs+[^a-z]+/i', ' ', $sql);
                $sql = preg_replace('#LIMIT ([0-9]+),?#', '', $sql);
            }

            if (stripos($sql, 'OFFSET') !== FALSE)
            {
                // Remove OFFSET from the SQL
                //$sql = preg_replace('/sOFFSETs+d+/i', '', $sql);
                $sql = preg_replace('#OFFSET ([0-9]+),?#', '', $sql);
            }

            // Get the total rows from the last query executed
            $result = DB::query
                    (
                    Database::SELECT,
                    'SELECT COUNT(*) AS total_rows '.
                    'FROM ('.trim($sql).') AS counted_results'
                    )->execute()->current();

            // Return the total number of rows from the query
            return (int) $result["total_rows"];
        }

        return FALSE;
    }

    /**
     * Return the list of choices for a given field
     * @param string $field
     * @return array
     */
    public function choices($field)
    {
        return Arr::get($this->_choices, $field, array());
    }

    /**
     * I think this is specifically for the days dropdown
     * @param array $array
     * @param int $length
     * @return array
     */
    static function zerofill($array, $length = 2)
    {
        foreach ($array as $key=>$value) {
            $array[$key] = str_pad($value, $length, '0', STR_PAD_LEFT);
        }
        return $array;
    }

    /**
     * Handles setting of all model values, and tracks changes between values.
     * There was something about changed I just didn't like. Ln 1133
     * @param   string  column name
     * @param   mixed   column value
     * @return  void     *
     */
    public function __set($column, $value)
    {
            if ( ! isset($this->_object_name))
            {
                    // Object not yet constructed, so we're loading data from a database call cast
                    $this->_preload_data[$column] = $value;

                    return;
            }

            if (array_key_exists($column, $this->_ignored_columns))
            {
                    // No processing for ignored columns, just store it
                    $this->_object[$column] = $value;
            }
            elseif (array_key_exists($column, $this->_object))
            {
                    // Store previous value to see if there is a change
                    $previous = $this->_object[$column];

                    $this->_object[$column] = $this->_load_type($column, $value);

                    if (/*isset($this->_table_columns[$column]) AND */$this->_object[$column] !== $previous)
                    {
                            // Data has changed
                            $this->_changed[$column] = $column;

                            // Object is no longer saved
                            $this->_saved = FALSE;
                    }
            }
            elseif (isset($this->_belongs_to[$column]))
            {
                    // Update related object itself
                    $this->_related[$column] = $value;

                    // Update the foreign key of this model
                    $this->_object[$this->_belongs_to[$column]['foreign_key']] = $value->pk();
            }
            else
            {
                    throw new Kohana_Exception('The :property: property does not exist in the :class: class',
                            array(':property:' => $column, ':class:' => get_class($this)));
            }
    }

    /**
     * Remove all many to many associations
     * @param string $alias
     * @return $this
     */
    public function remove_all($alias)
    {
        DB::delete($this->_has_many[$alias]['through'])
                ->where($this->_has_many[$alias]['foreign_key'], '=', $this->pk())
                //->where($this->_has_many[$alias]['far_key'], '=', $model->pk())
                ->execute($this->_db);

        return $this;
    }

    /**
     * Binds another one-to-one object to this model.  One-to-one objects
     * can be nested using 'object1:object2' syntax
     * Fixes problems with table prefixes.
     *
     * @param   string  target model to bind to
     * @return  void
     */
    public function with($target_path)
    {
            if (isset($this->_with_applied[$target_path]))
            {
                    // Don't join anything already joined
                    return $this;
            }

            // Split object parts
            $aliases = explode(':', $target_path);
            $target	 = $this;
            foreach ($aliases as $alias)
            {
                    // Go down the line of objects to find the given target
                    $parent = $target;
                    $target = $parent->_related($alias);

                    if ( ! $target)
                    {
                            // Can't find related object
                            return $this;
                    }
            }

            // Target alias is at the end
            $target_alias = $alias;

            // Pop-off top alias to get the parent path (user:photo:tag becomes user:photo - the parent table prefix)
            array_pop($aliases);
            $parent_path = implode(':', $aliases);

            if (empty($parent_path))
            {
                    // Use this table name itself for the parent path
                    $parent_path = $this->_table_name;
            }
            else
            {
                    if( ! isset($this->_with_applied[$parent_path]))
                    {
                            // If the parent path hasn't been joined yet, do it first (otherwise LEFT JOINs fail)
                            $this->with($parent_path);
                    }
            }

            // Add to with_applied to prevent duplicate joins
            $this->_with_applied[$target_path] = TRUE;

            // Use the keys of the empty object to determine the columns
            foreach (array_keys($target->_object) as $column)
            {
                    $name   = $target_path.'.'.$column;
                    $alias  = $target_path.':'.$column;

                    // Add the prefix so that load_result can determine the relationship
                    $this->select(array($name, $alias));
            }

            if (isset($parent->_belongs_to[$target_alias]))
            {
                    // Parent belongs_to target, use target's primary key and parent's foreign key
                    $join_col1 = $target_path.'.'.$target->_primary_key;
                    $join_col2 = $parent_path.'.'.$parent->_belongs_to[$target_alias]['foreign_key'];
            }
            else
            {
                    // Parent has_one target, use parent's primary key as target's foreign key
                    $join_col1 = $parent_path.'.'.$parent->_primary_key;
                    $join_col2 = $target_path.'.'.$parent->_has_one[$target_alias]['foreign_key'];
            }

            // Join the related object into the result
            $this->join(array($target->_table_name, $this->_db->table_prefix() . $target_path), 'LEFT')->on($join_col1, '=', $join_col2);

            return $this;
    }

    public function load_all()
    {
		if ( ! empty($this->_load_with))
		{
			foreach ($this->_load_with as $alias)
			{
				// Bind relationship
				$this->with($alias);
			}
		}

        $this->_db_builder = DB::select("{$this->_table_name}.*");

		// Process pending database method calls
		foreach ($this->_db_pending as $method)
		{
			$name = $method['name'];
			$args = $method['args'];

			$this->_db_applied[$name] = $name;

			switch (count($args))
			{
				case 0:
					$this->_db_builder->$name();
				break;
				case 1:
					$this->_db_builder->$name($args[0]);
				break;
				case 2:
					$this->_db_builder->$name($args[0], $args[1]);
				break;
				case 3:
					$this->_db_builder->$name($args[0], $args[1], $args[2]);
				break;
				case 4:
					$this->_db_builder->$name($args[0], $args[1], $args[2], $args[3]);
				break;
				default:
					// Here comes the snail...
					call_user_func_array(array($this->_db_builder, $name), $args);
				break;
			}
		}

		return $this->_load_result(TRUE);
    }

}