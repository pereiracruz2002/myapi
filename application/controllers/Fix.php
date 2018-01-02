<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Fix extends CI_Controller
{
    public function amizades_duplicadas() 
    {
        $duplicadas = $this->db->select('id, count(*) as total')->group_by('user_id, friend_id')->having('total >', 1)->get('friends')->result();
        $where_in = array();
        foreach ($duplicadas as $item) {
            $where_in[] = $item->id;
        }
        if($where_in){
            $this->db->where_in('id', $where_in)->delete('friends');
        }
    }
}
