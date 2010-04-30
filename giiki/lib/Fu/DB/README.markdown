# King Fu DB Documentation

King Fu DB makes it easier to handle the most common database tasks, such as saving, deleting and
searching for records.

<br />
<br />


## Table of Contents

*   [DB](#base)
    *   [function init()](#init)
    *   [set_all()](#set_all)
    *   [find()](#find)
    *   [find_by()](#find_by)
    *   [find_all()](#find_all)
    *   [query()](#query)
    *   [save()](#save)
    *   [delete()](#delete)
    *   [Utility Functions](#utils)
*   [Results](#results)
    *   [fetch()](#fetch)
    *   [current()](#current)
    *   [to_xml()](#to_xml)
    *   [set_class()](#set_class)
*   [Events](#events)
*   [Validations](#validations)
    *   [validates_presence_of](#presence_of)
    *   [validates_uniqueness_of](#uniqueness_of)
    *   [validates_exclusion_of](#exclusion_of)
    *   [validates_inclusion_of](#inclusion_of)
    *   [validates_length_of](#length_of)
    *   [validates_numericality_of](#numericality_of)
    *   [validates_format_of](#format_of)
*   [Associations](#associations)
    *   [belongs_to](#belongs_to)
    *   [has_many](#has_many)
    *   [has_one](#has_one)
    *   [many_to_many](#many_to_many)
    *   [fetch](#assoc_fetch)


<a name="base"></a>
<h3>DB</h3>

A class that represents a DB table should extend Fu\_DB. It should have a property
($_table) that tells Fu\_DB the name of the table and an $id property for the id table.

Each table column should then be represented as a property of the class, with the following
fields that have reserved functionality:

*	id - used for saving, searching and other crucial internal functionality
*	is\_*, has\_* - presumed to be a flag (UNSIGNED INT(1)) and set to either 1 or 0
*	created_at - automatically set to the current DATETIME on INSERT, if present
*	created_on - automatically set to the current DATE on INSERT, if present
*	updated_at - automatically set to the current DATETIME on UPDATE, if present
*	updated_on - automatically set to the current DATE on UPDATE, if present

An example class:

	class DB_Tag extends Fu_DB {
		protected
			$_table = 'tags';

		public
			$id,
			$tag;

		/**
		 * Called by Fu_DB __constuct
		 */
		function init () {
			$this->validates_presence_of('tag');
			$this->validates_uniqueness_of('tag');

			// return Fu_DB_Result object containing all tags
			$this->many_to_many('DB_BlogPost', 'blog_posts', array(
												// type of join: table or class
												'table' 					=> 'blog_post_tags',
												'foreign_key' 				=> 'tag_id',
												'association_foreign_key' 	=> 'blog_post_id'
											));
		}
	}

<a name="init"></a>
<h4>function init()</h4>

The init() method is a replacement for \_\_construct(). It is simply a way of executing some
code when a class is instantiated, but still allowing the Fu\_DB::\_\_construct() to run.


<a name="set_all"></a>
<h4>set_all()</h4>

	set_all (array $properties);

If you have an array that represents a table row, pass it through to set\_all() in order to correctly
set the properties of a Fu_DB based DB object.

	$blog_post = new DB_BlogPost;
	$blog_post->set_all($row);


<a name="find"></a>
<h4>find()</h4>

	find (mixed $id);

Find *ONE* record in the DB and return a DB object.

	$db_blog_post = new DB_BlogPost;
	$my_blog_post = $db_blog_post->find(19);

If no record is found, a Fu\_DB\_Exception is thrown.


<a name="find_by"></a>
<h4>find_by()</h4>

	find_by (string $field_id, $value, [, array $options]);

Similar to [find()](#find), but allows you to pass through the name of another (probably UNIQUE) field
and a value to query by:

	$db_user = new DB_User;
	$my_user = $db_user->find_by('username', 'joetheplumber');

If no record is found, a Fu\_DB\_Exception is thrown.

Options:

*	see the [find_all()](#find_all) options


<a name="find_all"></a>
<h4>find_all()</h4>

	find_all (array $options);

Find all records in a table that match the given options:

	$db_user = new DB_User;
	$results = $db_user->find_all(array(
		'conditions' => array(
			'account_id=?', $account
		)
	));

An instance of Fu\_DB\_Result is always returned, unless a DB error has occurred, in which case a
Fu\_DB\_Exception is thrown.

Options:

*	conditions - this should be an array, where the first value is the SQL statement. If you have
placeholders in your SQL, you can provide an array as the 2nd value.
*	order - full order statement, e.g. name ASC, age DESC
*	limit - limit statement, e.g. 10, 20 or 10 OFFSET 20
*	per_page - if you want pagination baked in, just pass through the number of results you want to show per page
*	current_page - the current page, default: 1


<a name="query"></a>
<h4>query()</h4>

	query (array $options);

Find all records based on the full SQL statement given:

	$db_user = new DB_User;
	$results = $db_user->query(array(
		'sql' => 'SELECT * FROM blahblah INNER JOIN deedum on ...',
		'params' => array(5)
		)
	));

An instance of Fu\_DB\_Result is always returned, unless a DB error has occurred, in which case a
Fu\_DB\_Exception is thrown.

Options:

*	sql - the SQL statement to run.
*	params - the parameters to use for the SQL placeholders
*	order - full order statement, e.g. name ASC, age DESC
*	limit - limit statement, e.g. 10, 20 or 10 OFFSET 20
*	per_page - if you want pagination baked in, just pass through the number of results you want to show per page
*	current_page - the current page, default: 1



<a name="save"></a>
<h4>save()</h4>

	save ();

INSERT or UPDATE a db record, handled by Fu\_DB:

	$user = new DB_User;
	$user->name = 'Pete Shaw';
	$user->login = 'PeteShaw';
	$result = $user->save();

Returns a BOOLEAN depending on success.


<a name="delete"></a>
<h4>delete()</h4>

	delete ([array $options]);

Delete the current record, or the record identified by options['id'], if supplied.

	$db_blog_post = new DB_BlogPost;
	$my_blog_post = $db_blog_post->find(19);
	$deleted = $my_blog_post->delete()

or

	$db_blog_post = new DB_BlogPost;
	$deleted = $db_blog_post->delete(array('id' => 19));

It is also possible to supply conditions to the SQL with the same format as [find_all()](#find_all) conditions option.

E.g.

	$db_blog_post = new DB_BlogPost;
	$deleted = $db_blog_post->delete(array('conditions' => 'published_at < 2000-01-01'));

Returns BOOLEAN depending on success. On a DB error, a Fu\_DB\_Exception is thrown.



<a name="utils"></a>
<h4>Utility Functions</h4>

##### field_exists (string $field)

Do this field exist on the table, e.g. :

	if ($db_user->field_exists('updated_at')) {
		...
	}

Returns a BOOLEAN.


##### is_flag (string $field)

Is the specified field a flag field e.g. :

	if ($db_user->is_flag('is_live')) {
		...
	}

Returns a BOOLEAN.


##### changed ([string $field])

Checks whether an object/specific field has changed since [set_all()](#set_all) has been called on the object, e.g. :

	if ($db_user->changed('url')) {
		...
	}

Returns a BOOLEAN.


##### old (string $field)

Returns the original value of this field. :

	$default_username = $db_user->old('username');

Returns MIXED.


##### get_original_row ()

Return the original db record as an array :

	$values_before_save = $db_user->get_original_row();

Returns an ARRAY.


##### max (string $column='id')

Returns the maximum value in the DB table for the specified field (defaults to id):

	$next_index = $db_user->max() + 1;

Returns an INTEGER.


##### get\_table ()

Returns the name of the corresponding DB table defined in the $_table property:

	$table = $db_user->get_table();

Returns a STRING.

<br />
<br />



<a name="results"></a>
<h3>Results</h3>

Any method on Fu\_DB that returns an object of type Fu\_DB\_Result. Fu\_DB\_Result implements the
iterator & countable interface, which means you can iterate over the results using foreach() and count
the results using count().


<a name="set_class"></a>
<h4>set_class()</h4>

	set_class (string $class_name);

By setting a class on a result set, any row that Fu\_DB\_Result returns will be in the form of an object
instantiated with the current row. Otherwise, an Array is returned.

The Fu\_DB::find\_all() and the [Association::fetch()](#assoc_fetch) method for has_many and
many_to_many associations will internally set the class, and so the result set will return objects.


<a name="fetch"></a>
<h4>fetch()</h4>

	fetch ();

Fetches the next DB result from the PDO object


<a name="current"></a>
<h4>current()</h4>

	current ();

Returns the current row


<a name="to_xml"></a>
<h4>to_xml()</h4>

	to_xml (array $options);

Easy and quick way to return the resultset as XML.

Options:

*	include - an array of fields that should be included, all others will be excluded
*	exclude - the opposite of include


<br />
<br />




<a name="events"></a>
<h3>Events</h3>

Fu\_DB has an events model, which enables you to bind events to a method on your class or
a global function on a particular event.

Example:

	class DB_BlogPost extends Fu_DB {
		protected
			$_table = 'posts';

		public
			$id,
			$title
			$content
			$url,
			$created_at,
			$updated_at;

		function init () {
			$this->bind('before_insert', 'set_url');
		}

		function set_url () {
			$this->url = sprintf('/posts/%s', text_urlify($this->title));
		}
	}

The different trigger points are:

*	before\_validation - runs before any internal validations are run
*	before\_validation\_on\_insert - same as above but only when Fu\_DB is performing an INSERT
*	before\_validation\_on\_update - same on UPDATE
*	after\_validation
*	after\_validation\_on\_insert
*	after\_validation\_on\_update
*	before\_save - before any save operation, INSERT or UPDATE
*	before\_insert
*	before\_update
*	after\_save - after any save operation, INSERT or UPDATE
*	after\_insert
*	after\_update
*	before\_delete
*	after\_delete

<br /><br />

<a name="validations"></a>
<h3>Validations</h3>

Fu\_DB offers a way of validating fields in the db before they're saved to the database.

The first argument to each method is always the field name, and can either be a string, or an array
if the validation is to be applied to multiple fields.

Apart from the validates\_presence\_of validations, all validations will pass as successful if the
field is empty, therefore if the field should not be empty, always check for its presence.

The following validations are available:


<a name="presence_of"></a>
<h4>validates_presence_of()</h4>

Checks that the field is not empty.

	validates_presence_of (mixed $fields[, array $options]);

Options:

*	on - can either be "insert" or "update" to specify a particular action to call this validation on
*	message - overwrite the default message, %s is interchanged with the field name


<a name="uniqueness_of"></a>
<h4>validates_uniqueness_of()</h4>

Checks for the uniqueness of a field.

	validates_uniqueness_of (mixed $fields[, array $options]);

Options:

*	on - can either be "insert" or "update" to specify a particular action to call this validation on
*	message - overwrite the default message, %s is interchanged with the field name
*	conditions - default where clause to narrow down validation. See the conditions option of [find_all()](#find_all)


<a name="exclusion_of"></a>
<h4>validates_exclusion_of()</h4>

Checks that the field does not appear in the given array.

	validates_exclusion_of (mixed $fields[, array $options]);

Options:

*	in - array of values to check against
*	on - can either be "insert" or "update" to specify a particular action to call this validation on
*	message - overwrite the default message, %s is interchanged with the field name


<a name="inclusion_of"></a>
<h4>validates_inclusion_of()</h4>

Checks that the field does appear in the given array.

	validates_inclusion_of (mixed $fields[, array $options]);

Options:

*	in - array of values to check against
*	on - can either be "insert" or "update" to specify a particular action to call this validation on
*	message - overwrite the default message, %s is interchanged with the field name


<a name="length_of"></a>
<h4>validates_length_of()</h4>

Checks that the length of the field adheres to the rules defined

	validates_length_of (mixed $fields[, array $options]);

Options:

*	in - array of min max, e.g. array(5, 10) between 5 and 10 characters long
*	min - minimum chars
*	max - max chars
*	on - can either be "insert" or "update" to specify a particular action to call this validation on
*	too_short - the message to return when the field is too short
*	too_long - the message to return when the field is too long
*	wrong_length - the message when the field is outside a range defined in the 'in' option
*	message - overwrite all above messages on all errors, %s is interchanged with the field name


<a name="numericality_of"></a>
<h4>validates_numericality_of()</h4>

Checks that the field is numeric.

	validates_numericality_of (mixed $fields[, array $options]);

Options:

*	on - can either be "insert" or "update" to specify a particular action to call this validation on
*	message - overwrite the default message, %s is interchanged with the field name


<a name="format_of"></a>
<h4>validates_format_of()</h4>

Checks that the field matches a regular expression.

	validates_format_of (mixed $fields[, array $options]);

Options:

*	with - the regex defined as a string
*	on - can either be "insert" or "update" to specify a particular action to call this validation on
*	message - overwrite the default message, %s is interchanged with the field name



<br /><br />

<a name="associations"></a>
<h3>Associations</h3>

Associations are a simple way to define how a table relates to another, with methods to fetch
associated objects, and soon (probably) to save associated records.


<a name="belongs_to"></a>
<h4>belongs_to()</h4>

When [fetch()](#assoc_fetch) is called on this object, return a single associated object for which the originating object holds the foreign key.

	belongs_to (string $class_name, string $association_name[, string $foreign_key]);

*	association name - the name used to identify the association
*	foreign key - if not defined, will assume it's the same as the association name

E.g.

	$this->belongs_to('DB_BlogPost', 'blog_post_id');


<a name="has_many"></a>
<h4>has_many()</h4>

When [fetch()](#assoc_fetch) is called on this object, return a result set of associated objects where the id is on the target table

	has_many (string $class_name, string $association_name[, string $foreign_key]);

*	association name - the name used to identify the association
*	foreign key - if not defined, will assume it's the same as the association name

E.g.

	$this->has_many('DB_Comment', 'comments', 'blog_post_id');


<a name="has_one"></a>
<h4>has_one()</h4>

Same as [has_many](#has_many), but restricts the [fetch()](#assoc_fetch) method to only return an
object representing 1 record.

	has_one (string $class_name, string $association_name[, string $foreign_key]);

*	association name - the name used to identify the association
*	foreign key - if not defined, will assume it's the same as the association name

E.g.

	$this->has_one('DB_Comment', 'comments', 'blog_post_id');


<a name="has_many"></a>
<h4>has_many()</h4>

When [fetch()](#assoc_fetch) is called on this object, return a result set of associated objects where the id is on the target table

	has_many (string $class_name, string $association_name[, string $foreign_key]);

*	association name - the name used to identify the association
*	foreign key - if not defined, will assume it's the same as the association name

E.g.

	$this->has_many('DB_Comment', 'comments', 'blog_post_id');


<a name="many_to_many"></a>
<h4>many_to_many()</h4>

When [fetch()](#assoc_fetch) is called on this object, return a result set of all matching results on target table

	many_to_many (string $class_name, string $association_name[, array $options]);

*	association name - the name used to identify the association
*	foreign key - if not defined, will assume it's the same as the association name

Options:

*	table - the name of the intersection table
*	foreign_key - name of column on intersection table linked to originating table
*	association_foreign_key - name of column on intersection table linked to associated table

E.g.

	$this->many_to_many('DB_Tag', 'tags', array(
											// type of join: table or class
											'table' 					=> 'blog_post_tags',
											'foreign_key' 				=> 'blog_post_id',
											'association_foreign_key' 	=> 'tag_id'
										));



<a name="assoc_fetch"></a>
<h4>fetch()</h4>

Fetches either an object or a result set, as defined in each of the association methods.

	fetch (string $association_name [, array $options]);

*	association name - the name used to identify the association

Options:

*	for associations that return a result set. See the options of [find_all()](#find_all)

E.g.

	$tags = $blog_post->fetch('tags', array(
											'per_page' 					=> 15,
											'order' 					=> 'tag ASC',
											'current_page' 				=> $_GET['page']
										));
