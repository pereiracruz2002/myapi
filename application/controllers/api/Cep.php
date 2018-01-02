<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Cep extends CI_Controller
{
    public function __construct() 
    {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }

    public function index() 
    {
        $cep = preg_replace('/[^\d\s]/', '', $this->input->post('cep'));
        $result = $this->db->select("CONCAT(cepbr_endereco.tipo_logradouro, ' ' ,cepbr_endereco.logradouro) as logradouro", false)
                                 ->select("  
                                     uf, 
                                     bairro,
                                     cidade as localidade 
                                    ")
                           ->join('cepbr_cidade', 'cepbr_cidade.id_cidade=cepbr_endereco.id_cidade')
                           ->join('cepbr_bairro', 'cepbr_endereco.id_bairro=cepbr_bairro.id_bairro')
                           ->where('cep', $cep)
                           ->get('cepbr_endereco')
                           ->row();

        if(!$result){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "http://cep.correiocontrol.com.br/{$cep}.json");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/1.0");
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            $result = json_decode(curl_exec($curl));
            curl_close($curl);
        }
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($result));
    }
    
    public function getAddress()
    {
        $result = array();
        
        if ($this->input->posts()) {
            // 'R', 'r', 'R.', 'r.', 'Av.', 'Av', 'av', 'av.'
            //$pattern = array('rua', 'Rua', 'Praça', 'Avenida', 'avenida');
            //$logradouro = trim(str_replace($pattern, "", $this->input->post('logradouro')));
            $cep = preg_replace('/[^\d\s]/', '', $this->input->post('cep'));
            $result = $this->db->select("CONCAT(cepbr_endereco.tipo_logradouro, ' ' ,cepbr_endereco.logradouro) as logradouro", false)
                    ->select("CONCAT(cepbr_endereco.tipo_logradouro, ' ' ,cepbr_endereco.logradouro, ', ', cepbr_bairro.bairro, ', ', cepbr_cidade.cidade, ' - ', cepbr_cidade.uf) as formated_address", false)
                    ->select("  
                             uf, 
                             bairro,
                             cidade,
                             cep
                            ")
                   ->join('cepbr_cidade', 'cepbr_cidade.id_cidade=cepbr_endereco.id_cidade')
                   ->join('cepbr_bairro', 'cepbr_endereco.id_bairro=cepbr_bairro.id_bairro')
                   ->where('cep', $cep)
                   ->get('cepbr_endereco')
                   ->result_array();
            
            if(!$result){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "http://cep.correiocontrol.com.br/{$cep}.json");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/1.0");
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            $result = json_decode(curl_exec($curl));
            curl_close($curl);
        }
        }
        
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($result));
    }
}
