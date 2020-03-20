<?php
namespace strangerfw\utils;

class StringUtil
{
  private static $irregular_rules = array(
    'men'   =>  'man',
    'seamen'  =>  'seaman',
    'snowmen' =>  'snowman',
    'women'   =>  'woman',
    'people'  =>  'person',
    'children'  =>  'child',
    'sexes'   =>  'sex',
    'moves'   =>  'move',
    'databases' =>  'database',
    'feet'    =>  'foot',
    'cruces'  =>  'crux',
    'oases'   =>  'oasis',
    'phenomena' =>  'phenomenon',
    'teeth'   =>  'tooth',
    'geese'   =>  'goose',
    'atlases' =>  'atlas',
    'corpuses'  =>  'corpus',
    'genies'  =>  'genie',
    'genera'  =>  'genus',
    'graffiti'  =>  'graffito',
    'loaves'  =>  'loaf',
    'mythoi'  =>  'mythos',
    'niches'  =>  'niche',
    'numina'  =>  'numen',
    'octopuses' =>  'octopus',
    'opuses'  =>  'opus',
    'penises' =>  'penis',
    'equipment' =>  'equipment',
    'information' =>  'information',
    'rice'    =>  'rice',
    'money'   =>  'money',
    'species' =>  'species',
    'series'  =>  'series',
    'fish'    =>  'fish',
    'sheep'   =>  'sheep',
    'swiss'   =>  'swiss',
  );

  private static $singular_rules = array(
    '(quiz)zes$'    =>  '$1',
    '(matr)ices$'   =>  '$1ix',
    '(vert|ind)ices$' =>  '$1ex',
    '^(ox)en'   =>  '$1',
    '(alias|status)es$' =>  '$1',
    '(octop|vir)i$'   =>  '$1us',
    '(cris|ax|test)es$' =>  '$1is',
    '(shoe)s$'    =>  '$1',
    '(o)es$'    =>  '$1',
    '(bus)es$'    =>  '$1',
    '([m|l])ice$'   =>  '$1ouse',
    '(x|ch|ss|sh)es$' =>  '$1',
    'movies$'   =>  'movie',
    'series$'   =>  'series',
    '([^aeiouy]|qu)ies$'  =>  '$1y',
    '([lr])ves$'    =>  '$1f',
    '(tive)s$'    =>  '$1',
    '(hive)s$'    =>  '$1',
    '([^f])ves$'    =>  '$1fe',
    '(^analy)ses$'    =>  '$1sis',
    '(analy|ba|diagno|parenthe|progno|synop|the)ses$' =>  '$1sis',
    '([ti])a$'    =>  '$1um',
    '(n)ews$'   =>  '$1ews',
    '(.)s$'     =>  '$1',
  );

  //  複数形ー＞単数形  
  public static function singularByPlural($plural) {
    $singular = $plural;

    if (array_key_exists(strtolower($plural), self::$irregular_rules)) {
      $singular = self::$irregular_rules[strtolower($plural)];
    } else {
      foreach(self::$singular_rules as $key => $value) {
        if (preg_match('/' . $key . '/', $plural)) {
          $singular = preg_replace('/' . $key . '/', $value, $plural);
          break;
        }
      }
    }

    return $singular;
  }

  public static function underscore($str)
  {
    return ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $str)), '_');
  }

  public static function camelize($str)
  {
    return strtr(ucwords(strtr($str, ['_' => ' '])), [' ' => '']);
  }

  public static function convertTableNameToClassName($str)
  {
    $arr = explode('_', $str);
    $arr[count($arr) - 1] = self::singularByPlural($arr[count($arr) - 1]);
    $str = implode('_', $arr);
    return strtr(ucwords(strtr($str, ['_' => ' '])), [' ' => '']);
  }

  /**
   * ランダム文字列生成 (英数字)
   * $length: 生成する文字数
   */
  public static function makeRandStr($length) {
      $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
      $r_str = null;
      for ($i = 0; $i < $length; $i++) {
          $r_str .= $str[rand(0, count($str) - 1)];
      }
      return $r_str;
  }
}
