<?php
function box($type, $titulo, $mensagem=null) {
  if ($mensagem) $mensagem = "<h2>{$titulo}</h2>{$mensagem}";
  else $mensagem = $titulo;
  return '<div class="alert alert-'.$type.'">'.$mensagem.'</div>';
}

function get_from($from, $id){
	$ci =& get_instance();
	$model = $from['model'];
	$ci->load->model($model.'_model', $model);
	if($id){
  	$result = $ci->$model->get($id)->row();
  	return $result->{$from['value']};
	}else{
	  return (isset($from['empty']) ? $from['empty'] : "Nenhum");
	}
	
}

function time_elapsed_string($ptime) {
	$etime = time() - $ptime;

	if ($etime < 1)
	{
		return '0 segundos';
	}

	$a = array( 365 * 24 * 60 * 60  =>  'ano',
		30 * 24 * 60 * 60  =>  'mês',
		24 * 60 * 60  =>  'dia',
		60 * 60  =>  'hora',
		60  =>  'minuto',
		1  =>  'segundo'
	);
	$a_plural = array( 'ano'   => 'anos',
		'mês'  => 'meses',
		'dia'    => 'dias',
		'hora'   => 'horas',
		'minuto' => 'minutos',
		'segundo' => 'segundos'
	);

	foreach ($a as $secs => $str)
	{
		$d = $etime / $secs;
		if ($d >= 1)
		{
			$r = round($d);
			return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str);
		}
	}
}

function idade($birthDate){
    if(strstr($birthDate, '-')){
        $birthDate = formata_data($birthDate);
    }
    $birthDate = explode("/", $birthDate);
    $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md")
        ? ((date("Y") - $birthDate[2]) - 1)
        : (date("Y") - $birthDate[2]));
    return $age;
}

function box_alert($titulo, $mensagem=null) {
  return box('danger', $titulo, $mensagem);
}

function box_success($titulo, $mensagem=null){
  return box('success', $titulo, $mensagem);
}


function formata_data($data){
  if (strstr($data, "/")){
    $A = explode ("/", $data);
    $V_data = $A[2] . "-". $A[1] . "-" . $A[0];
  }else{
    $A = explode ("-", $data);
    $V_data = $A[2] . "/". $A[1] . "/" . $A[0];	
  }
  return $V_data;
}

function formata_time($time, $separar=" "){
  $data = explode(" ", $time);
  if (strstr($data[0], "-")){
    $A = explode ("-", $data[0]);
    $V_data = $A[2] . "/". $A[1] . "/" . $A[0];	
  }else{
    $A = explode ("/", $data[0]);
    $V_data = $A[2] . "-". $A[1] . "-" . $A[0];	
  }
  if(count($data) < 2){
    $data[1] = "00:00:00";
  }
  return $V_data.$separar.$data[1];
}

function data_extenso($data){
  $aDia = explode(' ', $data);
  if(strstr($aDia[0], '-'))
    $dia = explode('-', $aDia[0]);
  else{
    $dia = explode('/', $aDia[0]);
    krsort($dia);
  }
  $aMes = array("01" => 'Janeiro',
                "02" => 'Fevereiro',
                "03" => 'Março',
                "04" => 'Abril',
                "05" => 'Maio',
                "06" => 'Junho',
                "07" => 'Julho',
                "08" => 'Agosto',
                "09" => 'Setembro',
                "10" => 'Outubro',
                "11" => 'Novembro',
                "12" => 'Dezembro'
                );
  return $dia[2].' de '.$aMes[$dia[1]].' de '.$dia[0];
}

function formata_valor($valor){
  if(!$valor)
    return false;
  $formato = strstr($valor, ',');
  if($formato){
    return str_replace(",", ".", str_replace(".", "", $valor));
  }else{
    return number_format($valor, 2, ',','.');
  }
}

function formata_porcentagem($valor){
  $v = explode(".", $valor);
  if($v[1] != "00")
    return formata_valor($valor);
  else
    return $v[0];
}

if (!function_exists('force_ssl')){
  function force_ssl() {
    if($_SERVER['SERVER_NAME'] != 'localhost'){
      $CI =& get_instance();
      $CI->config->config['base_url'] = str_replace('http://', 'https://', $CI->config->config['base_url']);
      if ($_SERVER['SERVER_PORT'] != 443){
        redirect($CI->uri->uri_string());
      }
    }
  }
}  

function base_ssl(){
  return str_replace('http://', 'https://', base_url());
}

function site_ssl($url) {
  return str_replace('http://', 'https://', site_url($url));
}

function canvas_url($url) {
  if($_SERVER['SERVER_NAME'] != 'localhost')
    return CANVAS_PAGE.$url;
  else
    return site_url($url);
}

function order_array_num($array, $key, $order = "ASC"){
  $tmp = array();
  foreach($array as $akey => $array2) 
    $tmp[$akey] = str_replace(",", ".", str_replace(".", "", $array2[$key]));

  if($order == "DESC")
    arsort($tmp , SORT_NUMERIC);
  else
    asort($tmp , SORT_NUMERIC);

  $tmp2 = array();       
  foreach($tmp as $key => $value)
    $tmp2[$key] = $array[$key];

  return $tmp2;
} 

function preenche_form(&$campos, $dados){
  $ci =& get_instance();
  
  foreach($campos as $key => $val)
    if(isset($val['class']))
      if(strstr($val['class'], 'data'))
        $campos[$key]['value'] = formata_data($dados->{$key});
      elseif(strstr($val['class'], 'valor'))
        $campos[$key]['value'] = formata_valor($dados->{$key});
      elseif(strstr($val['class'], 'date_time'))
        $campos[$key]['value'] = formata_time($dados->{$key});
      elseif(strstr($val['type'], 'password'))
        $campos[$key]['value'] = $ci->encrypt->decode($dados->{$key});
      else
        $campos[$key]['value'] = (isset($dados->{$key}) ? $dados->{$key} : '');
  
}
function _pre_valor($fields, &$data){
  foreach($data as $key => $val){
		if(array_key_exists($key, $fields))
		  if(strstr($fields[$key]['class'], "valor"))
		    $data[$key] = formata_valor($val);
	}
}

function _pre_data($fields, &$data){
  foreach($data as $key => $val){
		if(array_key_exists($key, $fields))
		  if(strstr($fields[$key]['class'], "data"))
		    $data[$key] = formata_data($val);
	}
}

function _pre_time($fields, &$data){
  foreach($data as $key => $val){
		if(array_key_exists($key, $fields))
		  if(strstr($fields[$key]['class'], "time"))
		    $data[$key] = formata_time($val);
	}
}

function dohash($string){
  if((isset($string)) && (is_string($string))){
      $enc_string = base64_encode($string);
      $enc_string = str_replace("=","",$enc_string);
      $enc_string = strrev($enc_string);
      $md5 = md5($string);
      $enc_string = substr($md5,0,3).$enc_string.substr($md5,-3);
  }else{
      $enc_string = "Parâmetro incorreto ou inexistente!";
  }
  return $enc_string;
}

function unhash($string){
  if((isset($string)) && (is_string($string))){
      $ini = substr($string,0,3);
      $end = substr($string,-3);
      $des_string = substr($string,0,-3);
      $des_string = substr($des_string,3);
      $des_string = strrev($des_string);
      $des_string = base64_decode($des_string);
      $md5 = md5($des_string);
      $ver = substr($md5,0,3).substr($md5,-3);
      if($ver != $ini.$end){
          $des_string = "Erro na desencriptação!";
      }
  }else{
      $des_string = "Parâmetro incorreto ou inexistente!";
  }
  return $des_string;
}

function diffDate($d1, $d2, $type='', $sep='-'){
  $d1 = explode($sep, $d1);
  $d2 = explode($sep, $d2);
  switch ($type){
    case 'A':
      $X = 31536000;
    break;
    case 'M':
      $X = 2592000;
    break;
    case 'D':
      $X = 86400;
    break;
    case 'H':
      $X = 3600;
    break;
    case 'MI':
      $X = 60;
    break;
    default:
      $X = 1;
  }
  $f1 = mktime(0, 0, 0, $d2[1], $d2[2], $d2[0]);
  $f2 = mktime(0, 0, 0, $d1[1], $d1[2], $d1[0]);
  
  $ret = floor( ($f1-$f2) / $X );
  return $ret;
}
function abreviaString($texto, $limite=100, $tres_p = '...'){
  $totalCaracteres = 0;
  $vetorPalavras = explode(" ",$texto);
  if(strlen($texto) <= $limite){
    $tres_p = "";
    $novoTexto = $texto;
  }else{
    $novoTexto = "";
    for($i = 0; $i <count($vetorPalavras); $i++){
      $totalCaracteres += strlen(" ".$vetorPalavras[$i]);
      if($totalCaracteres <= $limite)
        $novoTexto .= ' ' . $vetorPalavras[$i];
    }
  }
  return $novoTexto . $tres_p;
}

function pagseguro_status($status){
  $arr = array(
    "1" => "Aguardando Pagto",
    "2" => "Em Análise",
    "3" => "Pago",
    "4" => "Completo",
    "5" => "Em disputa",
    "6" => "Devolvida",
    "7" => "Cancelada",
  );
  return $arr[$status];
}

function pages_names($page, $ci){
  if(is_numeric($page) and !in_array($ci->uri->segment(2), array('vendas', 'widgets', 'avaliacoes')) and ($ci->uri->segment(2) != 'cursos' or $ci->uri->segment(3) == 'editar')){
  	$keys = array_keys($ci->model->fields);
    $key = str_replace($ci->modelname.'_', '', $keys[0]);
    
    return $ci->model->get($page)->row()->$key;
  }else{
    $arrNames = array('paginas' => 'Páginas',
                      'configuracoes' => 'Configurações',
                      'importar' => 'Importar Produtos',
                      'conta' => 'Minha Conta',
                      'informacoes' => 'Informações',
                      'produtos' => 'Produtos',
                      'myhome' => 'Organizar Página Inicial',
                      'cancelarAssinatura' => 'Cancelar Assinatura',
                      'monitoramento' => 'Monitoramento Social',
                      'emails' => 'Configurar Emails',
                      'contato' => 'Formulário',
                      'aparencia' => 'Aparência',
                      'relatorios' => 'Relatórios',
                      'divulgacao' => 'Divulgação',
                      'avaliacoes' => 'Avaliações',
                     );
    return (key_exists($page, $arrNames) ? $arrNames[$page] : ucfirst($page));
  }
}

function breadcrumbs(){
    $ci =& get_instance();
    $uris = $ci->uri->segment_array();
    unset($uris[1]);
    $ret = '<div class="breadcrumbs"><ul class="breadcrumb"><li> <a href="'.site_url('admin').'">Início</a></li>';
    $i = 0;
    if(in_array('ok', $uris))
        unset($uris[5]);
    foreach($uris as $item){
        if(!in_array($item, array('editar','visualizar','ecommerce','relatorio', 'rastreio', 'config', 'corrigir'))){
            if(count($uris) == $i+1)
                $ret .= ' <li> <a href="#">'.pages_names($item, $ci).'</a>';
            else{
                if($ci->uri->segment(2) == 'listar')
                    $ret .= ' <li> <a href="'.site_url('admin/'.$ci->uri->segment(1).'/'.$item).'">'.pages_names($item, $ci).'</a>';
                else
                    $ret .= ' <li> <a href="'.site_url('admin/'.$item).'">'.pages_names($item, $ci).'</a>';
            }
            $ret .= '</li>';
        }
        $i++;
    }
    $ret .= '</ul>';
    $ret .= '<a href="'.site_url('ajuda').'" class="pull-right ajuda"><i class="menu-icon fa fa-question-circle"></i> Ajuda</a>';
    $ret .= '</div>';
    print $ret;
}

function html_compress($html){
  preg_match_all('!(<(?:code|pre).*>[^<]+</(?:code|pre)>)!',$html,$pre);#exclude pre or code tags
   
  $html = preg_replace('!<(?:code|pre).*>[^<]+</(?:code|pre)>!', '#pre#', $html);#removing all pre or code tags
  $html = preg_replace('#<!–[^\[].+–>#', '', $html);#removing HTML comments
  $html = preg_replace('/[\r\n\t]+/', ' ', $html);#remove new lines, spaces, tabs
  $html = preg_replace('/>[\s]+</', '><', $html);#remove new lines, spaces, tabs
  $html = preg_replace('/[\s]+/', ' ', $html);#remove new lines, spaces, tabs
  
  if(!empty($pre[0]))
    foreach($pre[0] as $tag)
    $html = preg_replace('!#pre#!', $tag, $html,1);#putting back pre|code tags
  
  return $html;  
}

function meses(){
    return array("01" => 'Janeiro',
            "02" => 'Fevereiro',
            "03" => 'Março',
            "04" => 'Abril',
            "05" => 'Maio',
            "06" => 'Junho',
            "07" => 'Julho',
            "08" => 'Agosto',
            "09" => 'Setembro',
            "10" => 'Outubro',
            "11" => 'Novembro',
            "12" => 'Dezembro'
            );
}

function strip_tags_attributes($sSource, $aAllowedTags = "<p><strong><span><a><h1><img><div><h2><h3><h4><h5><em><b><i><u><strike><sub><sup><br><audio><video>", $aDisabledAttributes = FALSE, $aAllowedProperties = 'font|font-size|font-weight|color|text-align|text-decoration|margin|margin-left|margin-top|margin-bottom|margin-right|padding|padding-top|padding-left|padding-right|padding-bottom|width|height|p|span|em|b|strong|style'){

 if(!is_array($aDisabledAttributes)){
    $aDisabledAttributes = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut',
																 'ondataavaible', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup',
																 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit', 'onrowsdelete',
																 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
 }

 $search  = Array('%u2018',  '%u2019',  '%u2013',  '%u2014',  '%u2026',   '%u201C',  '%u201D');
 $replace = Array('&lsquo;', '&rsquo;', '&ndash;', '&mdash;', '&hellip;', '&ldquo;', '&rdquo;');
 $html = str_replace($search, $replace, $sSource);

 // replace unicode's URL '%u' character entities with their HTML entity equivs
 $html = preg_replace('/%u([0-9A-Fa-f]{4})/', '&#x$1;', $html);

 $html = preg_replace('|<([\w]+)([^>]+?)class="mso[^"]+"([^>]+)?>|is', '<\\1>', $html);
 $html = preg_replace('|<([\w]+)([^>]+?)style="([^"]+)?mso([^"]+)?"([^>]+)?>|is', '<\\1>', $html);
 $html = preg_replace('|<\/?\w+:[^>]*>|is', '', $html);
 $html = preg_replace('|<font[^>]+>(.+?)</font>|is', '\\1', $html);
 $html = preg_replace('|<span([^>]+?)lang=[^\s]+([^>]+?)xml:lang="[^\s]+">(.+?)</span>|is', '\\3', $html);
 $html = preg_replace('|<\?xml[^>]+microsoft[^>]+\?>|is', '', $html);
 $html = preg_replace('|<\/?\w+:[^>]*>|is', '', $html);
 $html = preg_replace('|<\\?\??xml[^>]>|is', '', $html);
 $html= stripcslashes($html);
         
     
 if(empty($aDisabledAttributes)){
    return $sSource;
 }

 $aDisabledAttributes = implode('|', $aDisabledAttributes);
     
 $sSource = preg_replace('/<(.*?)>/ie', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . $aDisabledAttributes . ")[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", $sSource );
 $sSource = preg_replace('/\s(' . $aDisabledAttributes . ').*?([\s\>])/', '\\2', $sSource);
         
 $regexp = '@([^;"]+)?(?<!'. $aAllowedProperties .'):(?!\/\/(.+?)\/)((.*?)[^;"]+)(;)?@is';   
 $sSource = preg_replace($regexp, '', $sSource);
 $sSource = preg_replace('@[a-z]*=""@is', '', $sSource);
         
 $sSource = strip_tags($html, $aAllowedTags);
 return $sSource;
}

function recursive_array_search($needle,$haystack) {
    foreach($haystack as $key=>$value) {
        $current_key=$key;
        if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
            return $current_key;
        }
    }
    return false;
}

function formata_texto($texto, $type){
    switch($type){
        case "date_time":
            return formata_time($texto);
        break;
        default:
            return $texto;
        break;
    }

}

