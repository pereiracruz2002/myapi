<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Categorias extends CI_Controller
{
    public function __construct() 
    {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }

    public function index() 
    {
        $this->load->model('categories_model','categories');
        $output = $this->categories->get_all()->result();
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }
}
