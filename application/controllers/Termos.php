<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Termos extends CI_Controller
{
    var $data = array();
    public function __construct() 
    {
        parent::__construct();
    }

    public function index() 
    {
        $this->load->model('content_model','content');
        $this->data['content'] = $this->content->get_where(array('permalink' => 'termos'))->row();
        if(!$this->data['content']){
            redirect('404');
        }
        $this->load->view('site/conteudo', $this->data);
    }
}
