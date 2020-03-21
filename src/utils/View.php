<?php
namespace strangerfw\utils;

/**
 * 
 */
class View {
  protected $layout = 'default';
  protected $iterate_viwes = [];
  protected $converte_values = [];
  protected $view_template_path = null;

  public function __construct($view_template_path = null) {
    $this->view_template_path = $view_template_path ? $view_template_path : VIEW_TEMPLATE_PATH;
    $this->error_log = new \strangerfw\utils\Logger('ERROR');
    $this->info_log = new \strangerfw\utils\Logger('INFO');
    $this->debug = new \strangerfw\utils\Logger('DEBUG');
  }

  /**
   * Layout template set.
   * 
   * @param string $layout layout filename
   */
  public function setLayout($layout){
    $this->layout = $layout ? $layout : $this->layout;
  }

  /**
   * render view.
   * 
   * @param string $controller_class_name Controller class name
   * @param string $action action name
   * @param array $datas set data
   */
  public function render($controller_class_name, $action, $datas){
    $this->layout = $this->view_template_path . '/layout/' . $this->layout . '.tpl';
    $document = [];
    $this->framingView($document, $datas , $this->layout, $controller_class_name, $action);
  }

  /**
   * Set data to template
   * 
   * @param array $datas set data
   * @param string $fileatime template file name
   * @param string $controller_class_name Controller class name
   * @param string $action action name
   */
  public function framingView(&$document, $datas, $fileatime, $controller_class_name = null, $action = null) {
    $disp_div = true;
    if (file_exists($this->view_template_path . $controller_class_name . '/' . $action . 'tpl')) return false;
    $file_context = file($fileatime);
    for($i = 0; $i < count($file_context); $i++) {
      $value = $file_context[$i];
      try {
        $value = str_replace("\n", '', $value);
        $value = str_replace("\r", '', $value);
        //  展開先の取得
        preg_match_all(
          "<\!----(.*?)---->",
          $value,
          $matchs
        );
        // $this->debug->log("View::framingView() matchs:".print_r($matchs, true));

        if (count($matchs[1]) > 0) {
          if (strpos($value, '!----renderpartial') > 0) {
            $this->renderPartial($value, $matchs, $controller_class_name, $action, $document, $datas);
            continue;
          }
          else if(strpos($value, '<!----value:')) {
            //  変数展開
            /*****
             * 例）
             *   string:Array ( 
             *     [0] => Array ( [0] => [1] => [2] => ) 
             *     [1] => Array ( [0] => UserList:1:name [1] => UserList:2:name [2] => UserList:3:name ) 
             *   ) 
             */
            $value = $this->convertKeyToValue($value, $matchs[1], $datas);
          }
          else if (strpos($value, '<!----iteratior:') && strpos($value, ':start')) {
            //  イテレーター
            $keys = explode(':', $matchs[1][0]);
            /**
             *  イレーター処理メソッド呼び出し
             *
             *  i : カウンター
             *  datas[keys[1]]  : セットする変数（多次元連想配列）
             *  file_context   :  テンプレートの内容
             */
            if (isset($datas[$keys[1]])) {
              foreach ($datas[$keys[1]] as $data) {
                $ret = $this->viewIterator($i, $data, $file_context);
              }
              $value = "";
              $i = isset($ret) ? $ret : $i;
            }
            else {
              for(; $i < count($file_context); $i++) {
                if (strpos($value, '!----iteratior:') && strpos($value, ':end')) break;
              }
            }
          }
          else if(strpos($value, '!----select_options:')) {
            $this->selectOptions($value, $datas);
            continue;
          }
          else if(strpos($value, '!----radiobutton_options:')) {
            $this->radiobuttonOptions($value, $datas);
            continue;
          }
          else if(strpos($value, '!----checkbox_options:')) {
            $this-checkboxOptions($value, $datas);
            continue;
          }
          else if(strpos($value, '!----disp_div:') && (strpos($value, '!----disp_div:end---->') == 0)) {
            $disp_div = $this->disp_div($value, $datas);
            continue;
          }
          else if(strpos($value, '!----disp_div:end----')) {
            $disp_div = true;
            continue;
          }
        }
        if ($disp_div) {
          echo $value . "\n";
          $document[] = $value;
        }
      } catch (Exception $e) {
        $this->debug->log("View::framingView() error:" . $e->getMessage());
      }
    }
    // return $document;
  }

  protected function disp_div($value, $data) {
    $result = false;
    $operators =[':equal:',':not_equal:',':or_more:',':greater_than:',':less_than:',':or_less:',':existing:'];
    /***
     * ・Authが空の場合
     * ・Auth['User']['id']と['User']['id']やModel[Model]['user_id']に違いがあった場合
     * ・Model['Model']['column']が空であるなど
     *  disp_div:value1:[condition]:value2
     *    condition
     *      equal:「等しいなら」
     *      not_equal:「等しくないなら」
     *      or_more:「左辺が右辺より以上の場合
     *      greater_than：「左辺が右辺より大きい場合」
     *      less_tan：「左辺が右辺より小さい場合」
     *      or_less：「左辺が右辺以下の場合」
     */
    $value = str_replace('<!----disp_div:', '', $value);
    $value = str_replace('---->', '', $value);
    foreach ($operators as $key => $operator) {
      if (strpos($value, $operator)) {
        $arr = explode($operator, $value);
        $result = $this->compLastPropaty($arr, $data, $operator);
      }
    }
    return $result;
  }

  /**
   *  Compare left value and right value.
   *
   *  @param  $arr
   *  @param  $left_data
   *  @param  $right_data
   *  @param  $operator
   *  @return boolean
   */
  protected function compLastPropaty($arr, $data, $operator){
    if (isset($arr[0]) && $arr[0] != '') {
      $arr_comp_left = explode(':', $arr[0]);
      if (count($arr_comp_left) > 0 && $arr_comp_left[0] != '') $datum_left = $this->getLastProperty($arr_comp_left, $data);
    }

    if (isset($arr[1]) && $arr[1] !== 'none') {
      $arr_comp_right = explode(':', $arr[1]);
      if (count($arr_comp_right) > 0 && $arr_comp_right[0] != '') $datum_right = $this->getLastProperty($arr_comp_right, $data);
    }

    $ret = false;
    /***
     *    condition
     *      equal:「等しいなら」
     *      not_equal:「等しくないなら」
     *      or_more:「左辺が右辺より以上の場合
     *      greater_than：「左辺が右辺より大きい場合」
     *      less_tan：「左辺が右辺より小さい場合」
     *      or_less：「左辺が右辺以下の場合」
     */

    switch ($operator) {
      case ':equal:':
        $ret = ($datum_left == $datum_right) ? true : false;
        break;
      case ':not_equal:':
        $ret = ($datum_left != $datum_right) ? true : false;
        break;
      case ':or_more:':
        $ret = ($datum_left >= $datum_right) ? true : false;
        break;
      case ':greater_than:':
        $ret = ($datum_left > $datum_right) ? true : false;
        break;
      case ':less_than:':
        $ret = ($datum_left < $datum_right) ? true : false;
        break;
      case ':or_less:':
        $ret = ($datum_left <= $datum_right) ? true : false;
        break;
      case ':existing:':
        $ret = $datum_left ? true : false;
        break;
      default:
        break;
    }
    return $ret;
  }

  /**
   *
   *
   *
   */
  protected function getLastProperty($arr, $data) {
    $key = is_array($arr) ? array_shift($arr) : $arr;
    $data = $data[$key];
    if (count($arr) > 0){
      $data = $this->getLastProperty($arr, $data);
    }
    return $data;
  }

  /**
   *
   *
   *
   */
  protected function renderPartial($value, $matchs, $controller_class_name, $action, $document, $datas) {
    //  部分テンプレート読み込み  
    $renderpartial = $matchs[1][0];
    if (strpos($value, 'CONTROLLER/ACTION') > 0) {
      if (isset($action)) {
        $renderpartial = str_replace('ACTION', $action, $renderpartial);
      }
      if (isset($controller_class_name)) {
        $renderpartial = str_replace('CONTROLLER', $controller_class_name, $renderpartial);
      }
    }

    $arr = explode(':', $renderpartial);
    
    $partial_tpl_filename = $this->view_template_path . $arr[1] . '.tpl';
    $this->framingView($document, $datas , $partial_tpl_filename);
  }

  /**
   *
   *
   *
   */
  protected function selectOptions($value, $datas) {
    /**
     *  <!----select_options:UserInfo:pref_id:Prefecture:name---->
     */
    $this->debug->log("View::selectOptions() datas:", print_r($datas, true));
    $arr = explode(':', $value);
    $pattern = "<!----select_options:(.*)---->";
    preg_match_all(
      '<!----select_options:(.*)---->',
      $value,
      $matchs
    );
    foreach ($matchs[1] as $key => $match) {
      $arr = explode(':', $match);
      $selects = isset($datas[$arr[1]]) ? $datas[$arr[1]] : [];
      $this->selectOption($selects, $datas[$arr[2]], $arr[3]);
    }
  }

  /**
   *
   *
   *
   */
  protected function radiobuttonOptions($value, $datas) {
    /**
     *  <!----radiobutton_options:UserInfo:pref_id:Prefecture:id:name---->
     */
    $arr = explode(':', $value);
    preg_match_all(
      '<!----radiobutton_options:(.*)---->',
      $value,
      $matchs
    );
    foreach ($matchs[1] as $key => $match) {
      $arr = explode(':', $match);
      $select = isset($datas[$arr[1]]) ? $datas[$arr[1]] : null;
      $this->radiobutton($select, $arr[0], $datas[$arr[2]], $arr[4], $arr[3], $arr[4]);
    }
  }

  /**
   *
   *
   *
   */
  protected function checkboxOptions($value, $datas) {
    /**
     *    <!----checkbox_options:UserInfo:pref_id:Prefecture:id:name---->
     */
    $arr = explode(':', $value);
    preg_match_all(
      '<!----checkbox_options:(.*)---->',
      $value,
      $matchs
    );
    foreach ($matchs[1] as $key => $match) {
      $arr = explode(':', $match);
      $selects = isset($datas[$arr[1]]) ? $datas[$arr[1]] : null;
      $this->checkbox($selects, $arr[0], $datas[$arr[2]], $arr[4], $arr[3]);
    }
  }

  /**
   *  SELECT OPTIONタグ生成メソッド
   *  
   *  @param array $selects    : 選択済みID
   *  @param array $option_data : 選択枝データ
   *  @param array $value_name : 選択名
   *  @return none
   */
  protected function selectOption($selects, $option_data, $value_name){
    $options_str = '';
    foreach ($option_data as $key => $value) {
      $selected = (in_array($value['id'], $selects)) ? 'selected' : '';
      $options_str .= "<option value='" . $value['id'] . "' " . $selected . ">" . $value[$value_name] . "</option>\n";
    }
    echo $options_str;
  }

  /**
   *  SELECT OPTIONタグ生成メソッド
   *  
   *  @param array $select    : 選択済みID
   *  @param array $option_data : 選択枝データ
   *  @param array $value_name : 選択名
   *  @param string $column_name : カラム名 
   *  @return none
   */
  protected function radiobutton($select, $class_name, $option_data, $column_name, $index_name){
    $options_str = "";
    foreach ($option_data as $key => $value) {
      $selected = ($value[$index_name] == $select) ? 'checked' : '';
      $options_str .= "<input type='radio' name='" . $class_name . "[" . $column_name . "]' value='" . $value[$index_name] . "' " . $selected . ">" . $value[$column_name] . "\n";
    }
    echo $options_str;
  }

  /**
   *  SELECT OPTIONタグ生成メソッド
   *  
   *  @param array $selects    : 選択済みID
   *  @param array $option_data : 選択枝データ
   *  @param array $value_name : 選択名
   *  @param string $column_name : カラム名 
   *  @return none
   */
  protected function checkbox($selects, $class_name, $option_data, $column_name, $index_name){
    $options_str = "";
    foreach ($option_data as $key => $value) {

      $selected = ($selects && in_array($value[$index_name], $selects)) ? 'checked' : '';
      $options_str .= "<input type='checkbox' name='" . $class_name . "[" . $column_name . "]' value='" . $value[$index_name] . "' " . $selected . ">" . $value[$column_name] . "\n";
    }
    echo $options_str;
  }

  /**
   *  キー置換メソッド
   *  
   *  @param string $context    : 置換対象文字列
   *  @param array $matchs : 置換対象キー文字列
   *  @param array $datas set data
   */
  protected function convertKeyToValue($context, $matchs, $datas){
    // $this->debug->log("View::convertKeyToValue() matchs:".print_r($matchs, true));
    foreach ($matchs as $v) {
      $keys = explode(':', $v);
      if ($v == 'value:document_root') {
        $context = str_replace('<!----value:document_root---->', DOCUMENT_ROOT, $context);
        continue;
      }
      $arr_value = isset($datas[$keys[1]]) ? $datas[$keys[1]] : '';

      if (!$arr_value) return null;

      for($i = 2; $i < count($keys) ; $i++) {
        $arr_value = $arr_value[$keys[$i]];
      }

      $search = '<!----'.$v.'---->';
      $context = str_replace($search, $arr_value, $context);
    }
    return $context;
  }

  /**
   *  イテレータ表示機能
   *  
   *  @param int $i : Line counter
   *  @param array $datas : 表示用データ  
   *  @param string $file_context : テンプレート内容  
   */
  public function viewIterator($i, $datas, $file_context) {
    $this->debug->log("View::viewIterator() Start");
    $keys = [];
    $j = 0;
    for($j = ($i + 1); $j < count($file_context); $j++) {
      $value = $file_context[$j];
      $value = str_replace('\n', '', $value);
      preg_match_all(
        '<\!----(.*?)---->',
        $value,
        $matchs
      );
      if (strpos($value, '<!----iteratior:') && strpos($value, ':start')) {
        $this->debug->log("View::viewIterator() Start");
        //  イテレーター再帰呼び出し
        $keys = explode(':', $matchs[1][0]);
        if (isset($datas[$keys[1]][$keys[2]])) {
          foreach ($datas[$keys[1]][$keys[2]] as $data) {
            $ret = $this->viewIterator($j, $data, $file_context);
          }
          $value = "";
          $j = $ret;
        }
        else {
          for(; $j < count($file_context); $j++) {
            if (strpos($value, '<!----iteratior:') && strpos($value, ':end')) {
              break;
            }
          }
        }

        continue;
      }
      else if (strpos($value, '<!----iteratior:') && strpos($value, ':end')) {
        //  イテレーター自身のイテレーション準備の完了
        break;
      }
      else if (strpos($value, '<!----value:')){
        $value = $this->convertKeyToValue($value, $matchs[1], $datas);        
      }
      echo $value;
    }
    return $j;
  }

  public function partialRender($datas){
  }
}
