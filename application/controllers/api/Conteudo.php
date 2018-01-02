<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Conteudo extends CI_Controller
{
    public function __construct() 
    {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }

    public function index() 
    {
        $this->load->model('content_model','content');
        $this->db->order_by('sort');
        $output = $this->content->get_all()->result();

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }
}
