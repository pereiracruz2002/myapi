<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Download extends CI_Controller
{
    var $data = array();
    
    public function index()
    {
        $this->load->library('user_agent');
        
        $this->data['Android']['browser'] = "https://play.google.com/store/apps/details?id=br.com.dinnerforfriends.app";
        $this->data['Android']['mobile'] = "market://details?id=br.com.dinnerforfriends.app";
        $this->data['iOS']['browser'] = "https://itunes.apple.com/br/app/dinner-for-friends/id1256570158?mt=8";
        $this->data['iOS']['mobile'] = "itms://itunes.apple.com/br/app/dinner-for-friends/id1256570158?mt=8";
        if ($this->agent->is_mobile()) {
            if ($this->agent->is_mobile('android')) {
                redirect($this->data['Android']['mobile']);
            } else if ($this->agent->is_mobile('iphone')) {
                redirect($this->data['iOS']['mobile']);
            }
        }
        
        $this->load->view("site/download", $this->data);
    }
}