{include uri = 'design:class_search_form/form_fields/numeric_slider.tpl'
		label = $input.class_attribute.name
		placeholder = $input.class_attribute.name
		value = $input.value
		input_name = $input.name
		bounds = $input.bounds
		current_bounds = $input.current_bounds
		id = concat('search-for-',$input.id)}