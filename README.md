Introduction
============

I started using Kohana's ORM in 2.3 just after making the move from CodeIgniter. When I moved on to KO3 (Kohana v3), I tried a hand at Sprig. Unfortunately, I didn't like it. I disliked having to define column types for every field, so I went back to Kohana ORM.

One of the things that stood out from my experience with Sprig was the auto form generation. Thus *ORMForm* came about. My primary inspirations for this were Sprig and Formo (a form generation module which was still in the middle of being ported to KO3 at the time I started work on ORMForm).

Installation
============
Extract to a module folder 'ormform' under your modules directory and enable in your bootstrap.php file.

Usage
============
I have tried to add as many hints on usage to the class documentation which should show up if you have the userguide module enabled.

Examples
===========
Controller
----------
<pre>
&lt;?php
class Controller_Example extends Controller
{
    public function action_index()
    {
        $person   = ORM::factory('person');

        $template = View::factory('person/save');

        if (Request::$method === 'POST')
        {
            $person->get_form();
            if (!$person->check())
                echo join('&lt;br /&gt;', $person->errors(NULL));
            else
                $person->save();
        }        

        $person->setup_form();        

        $view->person = $person;
    }
}
</pre>

View
------
<pre>&lt;?php

echo Form::open().$person->generate_form().Form::close();</pre>

Model (Partial)
--------------
<pre>&lt;?php
/// Columns
/// id - Primary Key
/// first_name varchar
/// last_name  varchar
/// gender     tinyint(1)
/// email      varchar
/// address    text
/// company_id int foreign_key
/// active     tinyint(1)
class Model_Person extends ORM_Form
{
    const GENDER_MALE   = 0;
    const GENDER_FEMALE = 1;
    
    protected $_form_labels = array(
        'company_id' => 'Company',
    );

    protected $_rules = array(
        'first_name' => array(
            'not_empty' => NULL,
        ),
        'last_name' => array(
            'not_empty' => NULL,
        ),
        'email' => array(
            'not_empty' => NULL,
            'email' => NULL,
        ),
    );
    
    protected $_attributes = array(
        'address' => array(
            'cols' => 200,
            'rows' => 5,
        ),
    );
    
    protected $_choices = array(
        'gender' => array(
            self::GENDER_MALE   => 'Male',
            self::GENDER_FEMALE => 'Female',
        ),
    );
    
    public function init()
    {
        $this->_choices['company_id'] = ORM::factory('company')->find_all()->as_array('id', 'name');
    }
    
}</pre>


Suggestions
============
Shoot me an email to azuka [at] zatechcorp.com.