<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Frontend extends CI_Controller
{
    var $data = array();
    public function index() 
    {
        $this->data['page_title'] = 'Uma rede de amigos que amam gastronomia, sejam eles cozinheiros ou comilões';
        $this->data['view'] = 'home';
        $this->load->view('site/home', $this->data);
    }
}
