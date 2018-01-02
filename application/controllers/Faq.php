<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Faq extends CI_Controller
{
    var $data = array();

    public function index() 
    {
        $this->data['page_title'] = 'Dúvidas Frequentes';
        $this->data['view'] = 'faq';
        $this->load->view('site/faq', $this->data);
    }
}
