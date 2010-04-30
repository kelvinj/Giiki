<?php
/**
 * All site form goodness
 */

class Fu_Form  {

    protected
        $default_options = array(
            'form_name'         => 'name', //string - Form's name.
            'method'            => 'POST', //string - (optional) Form's method
            'action'            => '/',  //string - (optional) Form's action
            'target'            => '', //string - (optional) Form's target
            'form_attributes'   => '', //mixed - (optional) Extra attributes for <form> tag
            'track_submit'      => 'true', //boolean - (optional) Whether to track if the form was submitted by adding a special hidden field. If the name of such field is not present in the $_GET or $_POST values, the form will be considered as not submitted.
            'validation'        => 'server', // server or client

            'fields'            => array(), // fields to pass through
            'default_values'    => '', // vals for fields

            'form_template'     => '', // can pass through on fly... or will be in Form config (i.e. default)
            'element_template'  => '', // as above
            'header_template'   => '', // as above
            'group_template'    => '', // as above
			'required_note_template' => '',
            'required_note'     => '', // as above

            'hidden_fields'     => array(),
            'submit_text'       => 'update',
            'submit_attributes' => 'class="button"',

            'input_attributes' 		=> '', // e.g size="50" and/or class="blah"
            'textarea_sizes'		=> array(
				'small' => 'rows="3" cols="100"',
				'medium' => 'rows="6" cols="100"',
				'large' => 'rows="9" cols="100"'),
			'checkbox_attributes'	=> '',
			'error_context'			=> 'default', // Fu_Feedback stack

			'note_element'			=> '', // this should be html element that the form note appears in.... should set this in config
			'note_element_class'	=> '', // likewise

			'compare_function'	=> '', // can pass through specical compare function to use... see Quickform documentation (not used)
			'renderer'			=> 'HTML_QuickForm_Renderer_Tableless'
        );

	public $form;

    /**
	 * Constructor class
	 *
	 * @param obj $dbh db handler
	 * @param array $options options
	 */
    public function __construct ($options = array()) {

		//print_r($options);

        $this->options = array_merge($this->default_options, $options);
        $this->form = new HTML_QuickForm(
            $this->options['form_name'],
            $this->options['method'],
            $this->options['action'],
            $this->options['target'],
            $this->options['form_attributes'],
            $this->options['track_submit']
        );

		//print_r($this->options);

		$renderer = $this->options['renderer'];
        $this->renderer = new $renderer;

		$this->set_form_template();
		$this->set_element_template();
		$this->set_header_template();
		$this->set_required_note_template();

        $this->add_fields();

		if ($this->options['default_values']) {
			$this->form->setDefaults($this->options['default_values']);
		}

		if ($this->options['submit_text']) {
			$this->renderer->setElementTemplate($this->renderer->_elementSubmitTemplate);
			$this->form->addElement('submit','submit',$this->options['submit_text'],$this->options['submit_attributes']);
		}

		if ($this->options['required_note']) {
			$this->form->setRequiredNote($this->options['required_note']);
		}
    }

    /**
	 * Output form
	 */
    public function display($freeze = false) {
		echo $this->to_html($freeze);
	}

    /**
	 * Return HTML of form
	 */
    public function to_html($freeze = false) {
		$this->form->accept($this->renderer);
		if ($freeze==true) {
			$this->form->freeze();
			return $this->form->toHtml();
		} else {
			return $this->renderer->toHtml();
		}
	}

	/**
	 * get form template
	 **/
	private function set_form_template() {
		if ($this->options['form_template']) {
			$this->renderer->setFormTemplate($this->options['form_template']);
		}
	}

	private function set_element_template() {
		if ($this->options['element_template']) {
			$this->renderer->setElementTemplate($this->options['element_template']);
		} else {
			$this->options['element_template'] = $this->renderer->_elementTemplate;
		}
	}

	private function set_header_template() {
		if ($this->options['header_template']) {
			$this->renderer->setHeaderTemplate($this->options['header_template']);
		}
	}

	private function set_required_note_template() {
		if ($this->options['required_note_template']) {
			$this->renderer->setRequiredNoteTemplate($this->options['required_note_template']);
		}
	}


    /**
	 * Add fields to form
	 */
    private function add_fields() {
		if (count($this->options['hidden_fields'])>0) {
			foreach ($this->options['hidden_fields'] as $name => $value) {
				$this->form->addElement('hidden', $name, $value);
			}
		}

        if (count($this->options['fields'])>0) {
			foreach ($this->options['fields'] as $options) {
				$this->add_field($options);
			}
		}

    }

    /**
	 * Add field to form
	 */
    private function add_field($options) {
        $options['label'] = ($options['label']) ? $options['label'] : str_replace('_',' ',$options['name']);

		// we have some HTML to insert before or after the {element} part of the template
		if ($options['before_element'] || $options['after_element']) {

			$element_template = ($options['template']) ? $options['template'] : $this->options['element_template'];

			if ($options['before_element']) {
				$element_template = str_replace('{element}', $options['before_element'].'{element}', $element_template);
			}

			if ($options['after_element']) {
				$element_template = str_replace('{element}', '{element}'.$options['after_element'], $element_template);
			}

			$this->renderer->setElementTemplate($element_template, $options['name']);
		}
		else if ($options['template']) {
			$this->renderer->setElementTemplate($options['template'],$options['name']);
		}

        switch ($options['type']) {
            case 'fieldset':
				$this->form->addElement('header', null, $options['name']);
            break;

            case 'input':
				$field_attributes = ($options['attributes']) ? $options['attributes'] : $this->input_attributes; // pass through class or size etc.
                $this->form->addElement('text', $options['name'], $options['label'], $field_attributes);
            break;

            case 'textarea':
				// need to work out best way of defining the size of a text area box and standard sizes...
				if ($options['cols'] && $options['rows']) {
					$attributes = 'rows="'.$options['rows'].'" cols="'.$options['cols'].'"';
				} elseif (array_key_exists($options['size'],$this->options['textarea_sizes'])) {
					$size = $options['size'];
					$attributes = $this->options['textarea_sizes'][$size];
				}
                $this->form->addElement('textarea',$options['name'], $options['label'], $attributes);
            break;

            case 'password':
				$this->form->addElement('password',$options['name'], $options['label'], $this->input_attributes);
				if ($options['add_compare']) {
                    $this->form->addElement('password', 'confirm_'.$options['name'], 'repeat '.$options['label'], $this->input_attributes);
                    $this->form->addRule('confirm_'.$options['name'],'repeat '.$field_text.' is required', 'required' , false, $options['validation']);
                    $this->set('compare_field_name','confirm_'.$options['name']);
                    $this->set('orginal_field_name',$options['name']);
                    $this->form->addFormRule($this->compare_function);
				}
            break;

            case 'checkbox':
				// null here relates to // label output after advcheckbox
				// not used it yet but could be handy at some point...
				// see http://wiki.triangle-solutions.com/index.php/PEAR_HTML_QuickForm#AdvCheckbox

				/*$form->addElement(
				'advcheckbox',
				string element-name,  // name of advcheckbox
				string element-label, // label output before advcheckbox
				string text,          // label output after advcheckbox
				mixed attributes,     // string or array of attributes
				mixed values);        // see below
				*/

				if (!$options['template']) {
					$this->renderer->setElementTemplate($this->renderer->_elementCheckboxTemplate,$options['name']);
				}

				$attributes = ($options['attributes']) ? $options['attributes'] : $this->checkbox_attributes;

				$this->form->addElement('advcheckbox',$options['name'], $options['label'], null, $attributes);
            break;

			case 'checkbox_multiple':
				// we might want to allow passing through of a table and which fields to use for id and value
				// previously i passed through ref_data table and then worked out creating the select from there...
				if (!$options['template']) {
					$this->renderer->setElementTemplate($this->renderer->_elementCheckboxTemplate,$options['name']);
				}

				if ($options['values'] && is_array($options['values']) && count($options['values'])>0) {
					foreach ($options['values'] as $id => $value) {
						$checkboxes[] = &$this->form->createElement('advcheckbox', $id, $value, $value);
					}
				}

				// $options['group_spacer'] relates to the options are split up.... or something.
				$this->form->addGroup($checkboxes, $options['name'], $options['label'], $options['group_spacer']);
            break;

            case 'file':
				$this->form->addElement('file',$options['name'], $options['label'], $field_attributes);
            break;

            case 'select':
				// we might want to allow passing through of a table and which fields to use for id and value
				// previously i passed through ref_data table and then worked out creating the select from there...

				$start_option_text = $options['start_option_text'] ? $options['start_option_text'] : '-- please select --';
				$start = ($options['remove_start_option']) ? array() : array('' => $start_option_text);

				if ($options['values']) {
					if (is_array($options['values']) && count($options['values'])>0) {
						$select_options = $start+$options['values'];
					}
					else {
						$select_options = $start;
					}
					$this->form->addElement('select', $options['name'], $options['label'], $select_options, $this->select_attributes);
				}
				// Fu DB object or class name
				else if ($options['dbo'] instanceof Fu_DB_Result || is_string($options['dbo'])) {
					$select_options = $start;
					$value_column = ($options['value_column']) ? $options['value_column'] : 'id';
					$display_column = ($options['display_column']) ? $options['display_column'] : 'name';

					if (is_string($options['dbo'])) {
						$klass = $options['dbo'];
						$db_class = new $klass;
						$options['dbo'] = $db_class->find_all(array('order' => $display_column));
					}

					foreach ($options['dbo'] as $v) {
						$select_options[$v->$value_column] = htmlentities($v->$display_column, ENT_QUOTES, 'UTF-8');
					}

					$this->form->addElement('select', $options['name'], $options['label'], $select_options, $this->select_attributes);
				}
				// numeric select
				elseif ($options['is_numeric']==true) {
					if ($options['max_val']) {
						$options['min_val'] = ($options['min_val']) ? $options['min_val'] : 0;
						$options['step'] = ($options['step']) ? $options['step'] : 1;

						if ($options['min_val'] < $options['max_val']) {
							for ($i=$options['min_val']; $i<=$options['max_val']; $i=$i+$options['step']) {
								$value = ($options['show_decimals']) ? sprintf("%01.1f",$i) : $i;
								$select_options[$value] = $value;
							}
						}
						else {
							for ($i=$options['min_val']; $i>=$options['max_val']; $i=$i-$options['step']) {
								$value = ($options['show_decimals']) ? sprintf("%01.1f",$i) : $i;
								$select_options[$value] = $value;
							}
						}

						$select_options = $start+$select_options;
						$this->form->addElement('select', $options['name'], $options['label'], $select_options, $this->select_attributes);
					}
				}
				else {
				// assume it is a yes no question
					$select_options = $start+array("1" => "Yes", "0" => "No");
					$this->form->addElement('select', $options['name'], $options['label'], $select_options, $this->select_attributes);
				}
            break;

            case 'radio':

				$radio = array();
				// we might want to allow passing through of a table and which fields to use for id and value
				// previously i passed through ref_data table and then worked out creating the select from there...

				/*
				 addElement('radio',
					string element-name, // name for radio button
					string label,        // text to display before button
					string text,         // text to display after button
					int value,           // the value returned
					mixed attributes);   // string or array of attributes
				*/

				if ($options['values']) {
					// values to select have been passed
					foreach ($options['values'] as $id => $value) {
						// don't need to name the radio button here as added to a group
						$radio[] = &$this->form->createElement('radio', null, null,' '.$value, $id, $this->radio_attributes);
					}
					$this->form->addGroup($radio, $options['name'], $options['label'], $options['group_spacer']);
				}
				else {
					// assume it is a yes no question
					$radio[] = &$this->form->createElement('radio', null, null, ' Yes', 1, $this->radio_attributes);
					$radio[] = &$this->form->createElement('radio', null, null, ' No', 0, $this->radio_attributes);
					$this->form->addGroup($radio, $options['name'], $options['label'], $options['group_spacer']);
				}

            break;

            case 'hidden':
				$this->form->addElement('hidden', $options['name'], $options['label']);
            break;

            case 'date':
            break;

            case 'html':
				$this->form->addElement('html', $options['code']);
            break;

        }

		/**
		* VALIDATIONS...
		*/

        if ($options['is_required']) {
			$options['required_message'] = ($options['required_message']) ? $options['required_message'] : $options['label'].' is required';

			if ($options['type'] == 'file') {
				$this->form->addRule($options['name'],$options['required_message'],'uploadedfile', false, $options['validation']);
			}
			else {
				$this->form->addRule($options['name'],$options['required_message'],'required', false, $options['validation']);
				$this->form->applyFilter($options['name'], 'trim');
			}
        }

        if ($options['is_email']) {
			$msg = $options['label'].' is not a valid email address';
			$this->form->addRule($options['name'], $msg, 'email', false, $options['validation']);
        }

        if ($options['is_numeric']) {
			$msg = $options['label'].' must be a valid number';
			$this->form->addRule($options['name'], $msg, 'numeric', false, $options['validation']);
        }

        if ($options['exclude_spaces']) {
			$msg = $options['label'].' can only be letters or numbers without spaces';
			$this->form->addRule($options['name'], $msg,'regex','/^[a-zA-Z_0-9\-]+$/',$options['validation']);
        }

        if ($options['has_min_chars']) {
			$msg = $options['label'].' is too short (must be at least '.$options['has_min_chars'].' characters)';
			$this->form->addRule($options['name'], $msg, 'minlength', $options['has_min_chars'], $options['validation']);
        }

        if ($options['has_max_chars']) {
			$this->form->addRule($options['name'], $msg, 'maxlength', $max_characters, $options['validation']);
        }

        if ($options['note']) {
			$this->form->addElement('html', '<'.$this->options['note_element'].' class="'.$this->options['note_element_class'].'">'.$options['note'].'</'.$this->options['note_element'].'>');
        }

		if ($options['do_freeze']) {
			$this->form->freeze($options['name']);
		}

    }

    /**
	 * add any errors from $this->form->_errors into standard error class..
	 */
    public function get_errors() {
		return $this->form->_errors;
    }

	/**
	 * check to see if a form validates
	 */
	public function is_valid() {
		if ($this->form->isSubmitted() && $this->form->validate()) {
			return true;
		}
		elseif ($this->form->isSubmitted()) {
			return false;
		}

		return false;
	}

	/**
	 * check to see if a form has been submitted
	 */
	public function is_submitted() {
		return ($this->form->isSubmitted()) ? true : false;
	}

	/**
	 * Return the filled in values of forms
	 */
	public function get_submit_values () {
		return $this->form->getSubmitValues();
	}

	/**
	 * Set the default values for a form
	 */
	function set_defaults ($defaults=array()) {
		$this->form->setDefaults($defaults);
	}


	/**
	 * Returns the value of an uploaded file
	 *
	 * @param string name of file input
	 * @return array I think
	 */
	function get_file ($name) {
		if ($f = $this->form->getElement($name)) {
			$value = $f->getValue();
			if (is_uploaded_file($value['tmp_name'])) {
				return $value;
			}
		}

		return array();
	}
}
