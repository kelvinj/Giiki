# King Fu Documentation

King Fu is a small library of classes that do shit no other library could do, or not.

<h3>Forms</h3>

King Fu forms extend Quickform. Documentation ain't great but some here:

*	http://pear.php.net/package/html_quickform/
*	http://wiki.triangle-solutions.com/index.php/PEAR_HTML_QuickForm

<h4>Basic usage</h4>

$fu_form = new Fu_Form($_dbh, $options);

if ($fu_form->is_valid()) {
    // do some crap
} else {
    // show errors or summit
    echo $fu_form->display();
}

You can freeze a form by:
echo $fu_form->display(1);

Freezing a form will display the fields in uneditable format - its upto us to style etc
$options is where dem magic happens

<h4>Options</h4>

Within options you will pass through the form fields, default values and much much more!
You can take a look at much of what to pass through within the Fu_Form class - the main ones are below. Where possible we will set the
default values either in Fu_Form or in a the corresponding config file

$options = array(
    'form_name'         => 'peteshaw',
    'fields'            => $form_fields,
    'default_values'    => $default_values
);

$fu_form = new Fu_Form($_dbh, $options);

$form_fields is an array of the fields we want to pass through
$default_values are an array of $name => $value where name is the form field name and value is the value that that form field should take
Usually this will come from a database etc - so we may want to allow simply passing through the corresponding object and allow the form to act on it
However this will require specifiying within the object which fields to display etc..... lets see!

<h4>Form fields</h4>

The form fields will probably be stored in a separate config file somewhere ... maybe
You will build up the form in the order in which you want the fields to appear

e.g.
$form_fields[] = array(..... field params ......);
$form_fields[] = array(..... field params ......);

etc.
You'll then pass that to $options and in turn pass that through the form when you instaniate it. aye.
Where possible the config will take care of a form fields defaults - although these can be overidden if needed...



<h5>Sample: Input</h5>
$form_fields[] = array(
    'type'          => 'input',
    'name'          => 'email',
    'is_required'   => true,
    'is_email'      => true
);


<h5>Sample: Texarea</h5>
$form_fields[] = array(
    'type'          => 'textarea',
    'name'          => 'about',
    'label'         => 'more about you', // label will normally be constructed from name replacing underscores for spaces - You can specifiy yourself with label
    'size'          => 'large' // we'll set up some defaults in the main Fu_Form class (or somewhere else in config)
);

<h5>Sample: Select</h5>
If you don't pass any values through or some kind of database set or way to get that set (yet to be determined) - the select created will simply have a yes/no option
if you want to pass through an array you can do so with $form_fields['values'] = array(); It should be in format $id => $value
Also you can pass through 'is_numeric' => true and 'min_val' => x, 'max_val' => xx to display a numeric dropdown of values.

$form_fields[] = array(
    'type'              => 'select',
    'name'              => 'terms_and_conditions',
    'form_note'         => 'Please select yes to say you agree with the terms and conditions' // forms notes will be applied somewhere on the field to "help" the user.
    'is_required'       => true,
    'required_message'  => 'Hey mo-fo do something!' // required message is normally constructed automagically - you can over-ride with required_message
);
