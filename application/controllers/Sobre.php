<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Sobre extends CI_Controller
{
    var $data = array();

    public function index() 
    {
        $this->data['page_title'] = 'Sobre Nós';
        $this->data['view'] = 'sobre';
        $this->load->view('site/sobre', $this->data);
    }
}
