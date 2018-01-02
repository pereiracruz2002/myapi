<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MY_Input extends CI_Input {


  function post($index = '', $default='', $xss_clean = FALSE) {
    $r = $this->_fetch_from_array($_POST, $index, $xss_clean);
    return $this->_manupilate_get_post($r, $default);
  }

  function _manupilate_get_post($r, $default='') {
    if (is_array($r)) return $r;
    $r = trim($r);
    $r = (($r OR $r === '0') ? $r : $default);
    return $r;
  }

  function posts_gets($data, $type='get') {
    $return = array ();
    if ( $not_associative = ($data[0]===TRUE) )
      array_shift($data);
    foreach ($data as $item) {
      if (gettype($item)=='array') list($item, $default) = $item;
      else $default = '';
      if ($type=='get')
        $return[$item] = $this->get($item, $default);
      else
        $return[$item] = $this->post($item, $default);
    }
    if ($not_associative)
      return array_values($return);
    return $return;
  }

  function posts($data=array()){
    foreach ($_POST as $key => $value) 
      $data[$key] =  $this->post($key);

    return $data;
  }

  function posts_array($data) {
    return $this->posts_gets($data, 'post');
  }

  function gets() {
    $data = func_get_args();
    return $this->posts_gets($data);
  }
  function gets_array($data) {
    return $this->gets_gets($data, 'get');
  }

}
