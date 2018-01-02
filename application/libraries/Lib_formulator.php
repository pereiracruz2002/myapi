<?php

class Lib_formulator {
  var $inputs = array ();

  /*
   * Converte os argumentos de array para atributo valor
   * array("name" => "value") para name="value"
   *
   */
  function _args($type, $args) {
    extract($args, EXTR_SKIP);
    $_data_extra = array();

    if (!isset($extra)) $extra = array();

    if (gettype($extra)=='string') {
      $_data_extra[] = $extra;
      $extra = array();
    }

    foreach (array('name', 'value', 'size') as $item){
      if (isset($$item))
        if($item == 'value')
          $extra[$item] = (is_array($$item) ? '': trim($$item));
        else
          $extra[$item] = $$item;
    }

    if (isset($label)) {
      $uid = $type.uniqid();
      $extra['id'] = $uid;
    }

    foreach (array('checked', 'readonly', 'disabled', 'multiple') as $item)
      if (isset($$item) AND $$item==true)
        $extra[$item] = $item;

    if (gettype($extra)=='array')
      foreach ($extra as $key=>$value)
        $_data_extra[] = $key.'="'.addslashes($value).'"';

    $args = get_defined_vars();

    return $args;
  }

  function input ($type, $args=array ()) {
    if (!isset($type)) return $this;

    if(isset($args['extra']['class'])){
      $args['extra']['class'] .= ' form-control';    	
    }
    else{
      $args['extra']['class'] = 'form-control';    	
    }
	

    $args = $this->_args($type, $args);
    extract($args, EXTR_SKIP);

    $input = sprintf('<input type="%s" %s/>',
      $type,
      (count($_data_extra) ? join(' ', $_data_extra).' ' : '')
    );

    if (isset($label)) {
      $classes = '';
      if (isset($class))
        if (gettype($class)=='string')
          $classes = " $class";
      $label = "{$label}";

      $append = (isset($append) ? $append : '');
      $prepend = (isset($prepend) ? $prepend : '');
      $div_class =(isset($div_class) ? $div_class: '');
      $label_class =(isset($label_class) ? $label_class : '');
      if (in_array($type, array('checkbox', 'radio'))) $input = "<label for=\"{$uid}\" class=\"{$label_class} label-{$type}\"> {$input}<span class=\"lbl\"></span> {$label}</label>";
      else $input = "<div class=\"form-group {$div_class}\"><label for=\"{$uid}\" class=\"control-label {$label_class} \">{$label}</label>{$prepend} {$input} {$append}</div>";
      
      
    }

    $this->inputs[] = $input;
    return $this;
  }

  function text ($args=array ()) {
    return $this->input('text', $args);
  }
  
  function email($args=array ()) {
    return $this->input('email', $args);
  }

	function file ($args=array ()) {
    return $this->input('file', $args);
  }
  function password ($args=array()) {
    return $this->input('password', $args);
  }

  function date($args=array()) {
    return $this->input('text', $args);
  }


  function hidden ($args=array()) {
    return $this->input('hidden', $args);
  }
  
  function number ($args=array()) {
    return $this->input('number', $args);
  }

  function multiple($args=array()) {
    return $this->select($args, 'multiple');
  }

  function model_method($args=array())
  {
      $CI =& get_instance(); 
      $this->input[] = $CI->{$args['model']}->{$args['method']}();
  }



  function textarea ($args=array ()) {
    $args = $this->_args('textarea', $args);
    if(isset($args['extra']['class']))
      $args['extra']['class'] .= ' form-control';
    else
      $args['extra']['class'] = 'form-control';

    extract($args, EXTR_SKIP);
    $value = isset($extra['value']) ? $extra['value'] : '';
    $_data_extra = array_filter($_data_extra, create_function ('$i', 'return !preg_match("@^value=@", $i);') );
    $textarea = sprintf('<textarea %s>%s</textarea>',
      (count($_data_extra) ? join(' ', $_data_extra).' ' : ''),
      $value
    );
    if (isset($label)) {
      $classes = '';
      if (isset($class))
        if (gettype($class)=='string')
          $classes = " $class";
      $label = "{$label}";

      $append = (isset($append) ? $append : '');
      $prepend = (isset($prepend) ? $prepend : '');
      $div_class =(isset($div_class) ? $div_class: '');
      $label_class =(isset($label_class) ? $label_class : '');

      $textarea = "<div class=\"form-group {$div_class}\"><label for=\"{$uid}\" class=\"control-label {$label_class} \">{$label}</label>{$prepend} {$textarea} {$append}</div>";

    }
    $this->inputs[] = $textarea;
	
    return $this;
  }

  function onoff($args) 
  {    

    $div_class =(isset($args['div_class']) ? $args['div_class']: '');
    $label_class =(isset($args['label_class']) ? $args['label_class'] : '');
   	$this->inputs[] = '<div class="form-group '.$div_class.'"><label class="control-label '.$label_class.'">' . $args['label'] . '</label>';
    if(!isset($args['value']))
        $args['value'] = 0;
      
    if(isset($args['prepend']))
        $this->inputs[] = $args['prepend'];

    $this->inputs[] = '<label for="crossChannel" class="lbl">
                           <input type="checkbox" id="crossChannel" class="ace ace-switch" name="'.$args['name'].'" value="1" '.(($args['value'] == 1) ? 'checked' : '').' />
                           <span class="lbl" data-lbl="Sim&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Não"></span>
                       </label>';
    if(isset($args['append']))
        $this->inputs[] = $args['append'];

    $this->inputs[] = '</div>';
  }



  function check ($args=array()) {
    $values = isset($args['values']) ? $args['values'] : array ();

    $div_class =(isset($args['div_class']) ? $args['div_class']: '');
    $label_class =(isset($args['label_class']) ? $args['label_class'] : '');
    unset($args['values']);
    $val = isset($args['value']) ? unserialize($args['value']) : '';
    if(!$val) $val = array();
    unset($args['value']);
    $args['name'] = $args['name']."[]";
    
    if(isset($args['label']))
    	$this->inputs[] = '<div class="form-group '.$div_class.'"><label class="col-sm-3 control-label">' . $args['label'] . '</label>';
    else
    	$this->inputs[] = '<div class="checks form-group">';
      
    if(isset($args['prepend']))
        $this->inputs[] = $args['prepend'];
    
    foreach ($values as $key => $value) {
      if (in_array($key, $val)) $args['checked'] = 'checked';
      else $args['checked'] = false;
      $arg = array_merge($args,array('value' => $key, 'label' => $value));
      $this->inputs[] = '<div class="checkbox">';
      $this->input('checkbox', $arg);
      $this->inputs[] = '</div>';
    }
    if(isset($args['append']))
        $this->inputs[] = $args['append'];
    
    $this->inputs[] = '</div>';
    
    return $this;
  }

  function radio ($args=array()) {

    $div_class =(isset($args['div_class']) ? $args['div_class']: '');
    $label_class =(isset($args['label_class']) ? $args['label_class'] : '');
    
    $values = isset($args['values']) ? $args['values'] : array ();
    unset($args['values']);
    $val = isset($args['value']) ? $args['value'] : '';
    unset($args['value']);
    if(isset($args['label']))
    	$this->inputs[] = '<div class="radios '.$div_class.'"><div class="'.$label_class.'">' . $args['label'] . '</div>';
    else
    	$this->inputs[] = '<div class="radios form-group">';
    
    foreach ($values as $key => $value) {
      if ($val==$key) $args['checked'] = 'checked';
      else $args['checked'] = false;
      $arg = array_merge($args,array('value' => $key, 'label' => $value));
      $arg['label_class'] = 'label-radio';
      $arg['append'] = '<span class="custom-radio"></span>';
      $this->input('radio', $arg);
    }
    
    $this->inputs[] = '</div>';
    
    return $this;
  }

  function select($args=array(), $type="normal"){
    if(isset($args['extra']['class']))
      $args['extra']['class'] .= ' form-control';
    else
      $args['extra']['class'] = 'form-control';

    $args = $this->_args('select', $args);
    $value = '';
    extract($args, EXTR_SKIP);
    
    
    $options = array();
    if(isset($args['multiple'])){
      foreach ($values as $k => $v)
      	$options[] = "  <option value=\"$k\"".((isset($extra['value']) AND (in_array($k, $args['args']['value'])))?' selected="selected"':'').">$v</option>";
    }else{
      foreach ($values as $k => $v){
        if(is_array($v)){
          $options[] = '<optgroup label="'.$k.'">';
          foreach ($v as $value => $item)
            $options[] = "  <option value=\"$value\"".((isset($extra['value']) AND ($extra['value']==$value))?' selected="selected"':'').">$item</option>";
          $options[] = '</optgroup>';
      	}else{
          $options[] = "  <option value=\"$k\"".((isset($extra['value']) AND ($extra['value']==$k))?' selected="selected"':'').">$v</option>";
        }
      }
    }
    
    foreach ($_data_extra as $k=>$v) {
      if (preg_match('@^value=@', $v))
        unset($_data_extra[$k]);
    }

    $append = (isset($append) ? $append : '');
    $prepend = (isset($prepend) ? $prepend : '');
    $div_class =(isset($div_class) ? $div_class: '');
    $label_class =(isset($label_class) ? $label_class : '');


    $input = sprintf("<select %s>\n%s\n</select>",
      (count($_data_extra) ? join(' ', $_data_extra).' ' : ''),
      join("\n", $options)
    );

    if (isset($label)) {
      $classes = '';
      if (isset($class))
        if (gettype($class)=='string')
          $classes = " $class";
      $label = "{$label}";
      $input = "<div class=\"{$div_class} form-group\"><label for=\"{$uid}\" class=\"{$label_class} control-label\">{$label}</label>{$prepend} {$input} {$append}</div>";

    }

    $this->inputs[] = $input;
    return $this;
  }

  function show($clear=true) {
    $saida = join("\n", $this->inputs);
    if ($clear) $this->inputs = array();
    return $saida;
  }
}
