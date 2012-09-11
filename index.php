<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Script de prueba</title>
    </head>
    <body>
<?php
  ini_set('display_errors', '1');

  class TextSearch {
    var $t;
    var $p;
    var $error;
    var $start;
    var $end;
    function TextSearch() {
      $this->set_text('');
    }
    function set_text($t) {
      $this->t = $t;
      $this->p = 0;
      $this->error = FALSE;
    }
    function find_next($str) {
      $this->p = strpos($this->t, $str, $this->p);
      if($this->p === FALSE) {
        $this->error = TRUE;
      } else {
        return $this->p;
      }
    }
    function after($str) {
      $this->find_next($str);
      $this->p += strlen($str);
      $this->start = $this->p;
    }
    function before($str) {
      $this->find_next($str);
      $this->end = $this->p;
    }
    function get_word() {
      if(!$this->error) {
        return substr($this->t, $this->start, $this->end - $this->start);
      }
    }
  }

  $s = new TextSearch();

  if(is_file('db.php')) {
    include_once('db.php');
  } else {
    include_once('db_sample.php');
  }

  $my->real_query("SELECT id, html FROM iniciativa");
  $res = $my->use_result();

  while($row = $res->fetch_assoc()) {
    $s->set_text($row['html']);
    $s->find_next('class="ficha_iniciativa"');
    $s->after('<b>');
    $s->before('</b>');
    $w = $s->get_word();
    if($w) {
      print $w;
    } else {
      print "<b>ERROR</b> on id=$row[id]";
    }
    print "<br/>\n";
  }
?>
    </body>
</html>