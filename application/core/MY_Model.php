<?php

/**
 * My_Model class.
 * 
 * @extends CI_Model
 * Versao 1.01
 *
 */
class MY_Model extends CI_Model{
  var $str = '';
  var $fields = array();
  var $id_col = 'id';
  var $table;
  var $get_order;
  var $CI;

  function __construct() {
    $this->_table();
  }

  function _table(){
    if (!$this->table)
      $this->table = strtolower(preg_replace('@_?model$@i', '', get_class($this)));
    
    return $this->table;
  }
  function get($id) {
    $result = $this->db->get_where($this->table, array($this->table.'.'.$this->id_col => $id));
    return $result;
  }

  function total() {
    return $this->db->count_all($this->table);
  }

  function get_all(){
  	$res = $this->db->get($this->table);
    return $res;
  }

  function get_where($where, $limit=false, $campos=false){
    if($campos)
      $this->db->select($campos);
      
    if (gettype($where) == 'array')
      foreach ($where as $k=>$v)
        if (strpos($k, 'OR ')===0)
          $this->db->or_where(substr($k, '3'), $v);
        else
          $this->db->where($k, $v);
    elseif (ctype_digit($where))
      $this->db->where($this->_table().'.'.$this->id_col, $where);

    if($limit) {
      $this->db->limit($limit);
      $this->db->order_by($this->id_col, 'desc');
    }
    $res = $this->db->get($this->table);
    return $res;
  }

  public function get_where_in($col, $where=array()) 
  {
      return $this->db->where_in($col, $where)->get($this->_table())->result();
  }
  
  function get_related ($model, $where = array(), $tipo='left') {
    $CI =& get_instance();
    $CI->load->model($model.'_model');
    $model = $CI->{$model.'_model'};

    if (is_array($where))
      foreach ($where as $k=>$v)
        if (strpos($k, 'OR ')===0)
          $this->db->or_where(substr($k, '3'), $v);
        else
          $this->db->where($k, $v);
    elseif (ctype_digit($where))
      $this->db->where($this->_table().'.'.$this->id_col, $where);
		
    $r = $this->db
      ->select('*')
      ->from($this->_table())
      ->join($model->_table(),
          $this->_table() . '.' . $this->id_col . '=' . $model->_table() . '.' . $this->id_col ,$tipo)
      ->get();
    return $r;
  }

  function get_last($col=false, $publicado=false) {
    if(!$col)
      $col = $this->id_col;

    if($publicado)
      $this->db->where(array("publicacao <" => date("Y-m-d H:i:s")));
      
    $this->db->order_by($col, "desc");
    $this->db->limit(1);
    $dados = $this->db->get($this->table)->result();
    return $dados[0];
  }
  
  function validar($run=true , $isField=false ){
    if ($isField){
      foreach($this->fields as $key=>$field){
        if (isset($this->fields[$key]['rules'])){
          if (!$this->input->post($key) || 
              is_array($this->input->post($key)) || 
              empty($this->input->post($key))){
            $this->form_validation->set_rules($key, $this->fields[$key]['label'], $this->fields[$key]['rules']);
          }
        }
      }
    }else{
      foreach($this->input->posts($_FILES) as $key=>$field)
        if (isset($this->fields[$key]['rules']))
          $this->form_validation->set_rules($key, $this->fields[$key]['label'], $this->fields[$key]['rules']);
    }
    if (!$run) return true;
    return $this->form_validation->run();
  }
  
  /**
   * Monta o formulário com os campos pre-determinados no model
   *
   * Se você passar o primeiro parâmetro como array, este será a configuração do forumlário.
   *
   * sintaxe: $model->form([(array) $args[, $campo[, $campo[, $campo[, ...]]]]]);
   *
   *
   * @var array $args [opcional]
   * @var string $campo
   * @return void
   */
  function form() {
    $args = func_get_args();
    
    if (count($args) and gettype($args[0])=='array'){
      $config = array_shift($args);
    }
    if (!count($args))
      $args=array_keys($this->fields);
    foreach($args as $k) {
      if (!in_array($k, array_keys($this->fields)))
        continue;
      $v=$this->fields[$k];
      unset($v['rules']);
      if(isset($config) and isset($config['values'][$k]))
        $v['value'] = $config['values'][$k];

      $prefix = isset($config['nameprefix'])?$config['nameprefix']:'';
      $sufix = isset($config['namesufix'])?$config['namesufix']:'';
      $args = $v+array('name'=>$prefix.$k.$sufix);

      if (isset($v['from'])){
        $model = $v['from']['model'].'_model';
        $this->load->model($model);
        
        if($this->$model->get_order)
          $this->db->order_by($CI->$model->get_order);

        if(isset($v['from']['where']))
          $this->db->where($v['from']['where']);

        if(isset($v['from']['join']))
        {
          $join = $v['from']['join'];
          foreach ($join as $key => $value) 
          {
            if (!($key % 2 == 0)) 
              $this->db->join($v['from']['join'][$key - 1], $v['from']['join'][$key]);
          }
        }
        
        $result = $this->{$model}->get_all();
        
        if (!isset($v['from']['key']))
          $v['from']['key'] = $this->$model->id_col;
        $values = array ();
        if (isset($args['empty']))
          $values[null] = $args['empty'];
        foreach ($result->result() as $item) {
          $values[trim($item->{$v['from']['key']})] = $item->{$v['from']['value']};
        }
        $args['values'] = $values;
        unset($v['from']);
      }

      $this->lib_formulator->{$v['type']}($args);
    }
    //exit();
    return $this->lib_formulator->show();
  }
  
  /**
   * Faz uma busca no banco de dados, paginados
   *
   * O resultado desta busca retorna um array com três posições
   *
   * <code>
   *   array (
   *     0 => 15, // Total de registros
   *     1 => CI_DB_db2_result Object (...), // Resultado do registro
   *     2 => array ('total' => 15, 'atual' => 1, 'per_page' => 5) // Paginate simples
   *   )
   * </code>
   *
   * @param array $where Dados de busca
   * @param int $page Página de busca
   * @param int $per_page Quantidade de registros por pagina
   * @param string|array $campos Campos que gostaria que sejam retornados na busca
   * @return array
   *
   */
  function search ($where=array (), $page=1, $per_page=15, $campos='*', $join=false, $qry = "", $order="", $group ="", $retorno=false, $return_type="obj") {
    $this->db->cache_on();
    $this->db->start_cache();

    if($join)
      foreach ($join as $key => $value) 
        if(is_array($value))
          $this->db->join($key, $value[0], $value[1]);
        else
          $this->db->join($key, $value);

    if(is_array($campos))
        foreach($campos as $key => $val)
            $this->db->select($key, $val);
    else
        $this->db->select($campos);
    $this->db->from($this->table);

    if ($page<1) $page=1; //$page--; Removido sript pois estava duplicando a última linha da página e acrescentando a mesma no início da nova página.

    if(!empty($qry)){
      if(is_array($qry)){
        $this->db->group_start();
        foreach($qry as $k => $v){
          if(strpos($k, 'OR ')===0){
            if(strstr($k, " )")){
              $this->db->or_where(substr($k, 3, -2), "'".$v."' )", false);
            }else{
              $this->db->or_where(substr($k, '3'), $v);
            }
          }elseif(strpos($k, 'IN ') === 0){
            $this->db->where_in(substr($k, '3'), $v);
          }else{
            $this->db->where($k, $v);
          }
        }
        $this->db->group_end();
      }else{
        $this->db->where($qry);
      }
    }
      
      
    if($where){
      $this->db->group_start();
      foreach($where as $k => $v)
        if($k == $this->id_col)
          $this->db->where($k, $v);
        elseif(strpos($k, 'OR ')===0)
          if(strstr($k, " )")){
            $this->db->or_like(substr($k, 3, -2), $v, "both )");
          }else{
            if(is_array($v))
              foreach($v as $vBusca)
                $this->db->or_like(substr($k, '3'), $vBusca);
            else
              $this->db->or_like(substr($k, '3'), $v);
          }
        elseif(strstr($k, " )"))
          $this->db->like(substr($k, 3, -2), $v, "both )");
        else{
          if(is_array($v))
            foreach($v as $vBusca)
              $this->db->or_like($k, $vBusca);
          else
            $this->db->like($k, $v);
        }
        $this->db->group_end();
          
    }
    $this->db->stop_cache();
    

    if($page==0 or $page==1)
      $limit = 0;
    else
      $limit = $page;
    
    if($order)
      foreach ($order as $key => $value)
        $this->db->order_by($key, $value);
    
    if($group)
      foreach ($group as $key => $value)
        $this->db->group_by($value);
    
    if($retorno==false){
      $data['total_rows'] = $this->db->count_all_results();
    }
    else{
      $query = $this->db->get();
      $data['total_rows'] = $query->num_rows();
    }

    if($order)
      foreach ($order as $key => $value) 
        $this->db->order_by($key, $value);
    
    if($group)
      foreach ($group as $key => $value) 
        $this->db->group_by($value);
    
    $this->db->limit($per_page, $limit);
    $query = $this->db->get();
    $this->db->flush_cache();
    
    $data['total_query'] = $query->num_rows();

    if($return_type == 'obj')
      $data['resultados'] = $query->result();
    else  
      $data['resultados'] = $query->result_array();
    
    return $data;
  }
  
  function save($data){
		
    if (method_exists($this, '_filter_pre_save'))
      $this->_filter_pre_save($data);
      
    // DEBUG
    #file_put_contents(dirname(FCPATH).'/log.log', "----------------\n" . get_class($this)."\n-----------\n", FILE_APPEND );
    #file_put_contents(dirname(FCPATH).'/log.log', print_r ($data, true), FILE_APPEND );
    $id_col = $this->id_col;

    if (isset($data[$id_col]) AND trim($data[$id_col])) {
      $id = $data[$id_col];
      unset($data[$id_col]);
      $this->db
        ->where($id_col, $id)
        ->update($this->_table(), $data);
      return $id;
    }
    unset($data[$id_col]);
    $this->db->insert($this->_table(), $data);
    if (method_exists($this, '_filter_pos_save'))
      $this->_filter_pos_save($data);
      
    $id = $this->db->insert_id();
    return $id;
  }

  public function insert_batch($data) 
  {
      $this->db->insert_batch($this->_table(), $data);
  }
   
  function rules($campo){
    return isset($this->fields[$campo]['rules']) ? $this->fields[$campo]['rules'] : $campo;
  }
  
  function delete($where) {
    if (gettype($where)=='array') {
      $this->db->where($where);
    } elseif (gettype($where)=='int' OR (gettype($where)=='string' AND ctype_digit($where))) {
      $this->db->where($this->id_col, $where);
    } else {
      return false;
    }
    $ret = $this->db->delete($this->table);
    return $ret;
  }

  function update($dados,$where){
    if (is_array($where)) {
      $this->db->where($where);
    } else{
      $this->db->where($this->id_col, $where);
    }
    if ($this->db->update($this->table, $dados)) {
        return true;
    } else {
        return false;
    }
  }
  
  function count_where($where){
    $this->db->where($where);
    $total = $this->db->count_all_results($this->table);
    return $total;
  }

  function __call($fn, $args){
    if(method_exists($this->db, $fn)) return call_user_func_array(array ($this->db, $fn), $args);
    else {
      die('<h1>Erro 730</h1><p>Erro ao chamar o metodo <strong>'.$fn.'</strong> da classe <strong>'.get_class ($this).'</strong>.</p>');
    }
  }
}
