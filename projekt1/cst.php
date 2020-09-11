<?php

#CST:xskuto00

/*
 * Projekt: CST do predmetu IPP
 * Autor: Sara Skutova xskuto00@stud.fit.vutbr.cz
 * Popis: Skript pro analýzu zdrojových souborů jazyka C podle standardu ISO C99, který ve
 *  stanoveném formátu vypíše statistiky komentářů, klíčových slov, operátorů a řetězců. 
 */
 $stderr = 'php://stderr'; //deskriptor na stderr
 $chybaParams = "Chyba prikazoveho radku\nPro napovedu parametr --help\n";
 $chyba_params_kod = 1;
 $chybafFilename = "Zadany soubor/adresar neexistuje nebo soubor nelze otevrit\n";
 $chyba_file_kod = 2;
 $chybaWRITEfile = "Chyba pri otevirani zadaneho vystupniho souboru\n";
 $chyba_write_file = 3;
 $chybaOPENdir = "Ze zadaneho adresare, nejaky soubo nelze otevrit/cist\n";
 $chyba_open_dir = 21;
 
 $help = false;
 $input = false;
 $nosubdir = false;
 $output = false;
 $k_param = false;
 $o_param = false;
 $i_param = false;
 $w_param = false;
 $c_param = false;
 $p_param = false;
 $dir = false;
 
 $help_cout = 0;
 $input_cout = 0;
 $nosubdir_cout = 0;
 $output_cout = 0;
 $k_param_cout = 0;
 $o_param_cout = 0;
 $i_param_cout = 0;
 $w_param_cout = 0;
 $c_param_cout = 0;
 $p_param_cout = 0;
 
 $input_file = "";
 $output_file = "";
 $w_patern = "";
 $array_file = array();
 
 /*
  * Vypise chybove hlasky, a ukonci skrypt s danou navratovou/chybovou hodnotou
  * @param $chyba kod chybove hlasky   
  */
 function MessageBad($chyba)
 {
    global $stderr;
    global $chybaParams;
    global $chybafFilename;
    global $chybaWRITEfile;
    global $chybaOPENdir;
    if ($chyba === 1) 
      file_put_contents($stderr, $chybaParams);
    elseif ($chyba === 2)
      file_put_contents($stderr, $chybafFilename);
    elseif ($chyba === 3)
      file_put_contents($stderr, $chybaWRITEfile);
    elseif ($chyba === 21)
      file_put_contents($stderr, $chybaOPENdir);
    
    
    exit($chyba);
 }
 
 /*
  * Odstarni maktra a i komentare v makrech. Odstrani i makra ktera pokracuji
  * pomoci \ i na dalsim radku. Vrati upraveny text
  * @param $file_des retezec/text celeho souboru nebo jiz predem upraveneho     
  */
 function Destroy_MACRO($file_des)
 {
    $makro_pokr = false;
    $stav = 0;
    $file_text = "";
    $new_text = preg_replace('/\r\n/', "\n", $file_des); //Prevest Windows konce radku na Unix
    
    $line_text = preg_split('/\n/', $new_text);  //POZOR! stratim znak nnoveho radku, musim ho tam pridat
    
    
    foreach($line_text as $line)
    {
    
      $line .= "\n"; //pridat ten chybejici znak
      
      if (strpos($line, "#") === 0 || $makro_pokr)//radek zacina s # - makro budu ho vynechavat, kontroluj zda nepokracuje na dalsim radku
      {
        $arr = str_split($line); //rozdelit string na pismena, je to pole
        foreach ($arr as $c) 
        {
          if ($stav == 0 && $c == '\\')  //nasla jsem \, mozne pokracovani makra na dlasim radku
          {
            $stav = 1;
          }
          elseif ($stav == 1 && $c == "\n") //jo bylo to pokracovani makra na dalsim radku
          {
            $stav = 0;
            $makro_pokr = true;
          }
          else
          {
            $stav = 0;
            $makro_pokr = false;
          }
        }
        	
      }
      else
        $file_text .= $line;       
    }
  $file_text = substr($file_text, 0, -1); //mam tam o 1 \n navic
  
  return $file_text;    
 }
 
 /*
  * Funkce vymaze vsechny komentare // a /**\/, v pripade ze // pokracuje pomoci
  * \ na dalsim radku, mazani pokracuje. Retezce budou ponechany - muzou se vyskytnou
  * retezce co vypadaji jako komentare .Vrati upraveny text.
  * @param $file_des retezec/text celeho souboru nebo jiz predem upraveneho         
  */
 function Destroy_COMMENT($file_des)
 {
    $comment_pokr = false;
    $stav = 0;
    $file_text = "";
    
    $arr = str_split($file_des); //rozdelit string na pismena, je to pole
    
    foreach($arr as $c)
    {
       if ($stav == 0 && $c == '"') //zacina string, to uvnitr nechat napokoji
       {
          $stav = 2;
          $file_text .= $c;
       }
       elseif ($stav == 0 && $c == '/') //zacina nejaky komentar
        $stav = 1;
       elseif ($stav == 0 && $c == '\'') //zacatek znakoveho literalu
       {
           $stav = 7;
           $file_text .= $c;
       }
       elseif ($stav == 0 && ($c != '"' || $c != '/' || $c != '\'')) //nezacal ani kometar ani string
        {
          $stav = 0;
          $file_text .=$c;
        }
       elseif ($stav == 2 && $c == '\\') //POZOR mozny pokus o zradu v retezci
       {
          $stav = 8;
          $file_text .= $c;
       }
       elseif ($stav == 2 && $c == '"') //konec retezce
       {
          $stav = 0;
          $file_text .= $c;
       }
       elseif ($stav == 2 && ($c != '"' || $c != '\\')) //znaky uvnit stringu
       {
          $stav = 2;
          $file_text .= $c;
       }
       elseif ($stav == 8 && $c == '"') //ZRADA!! jsme stale uvnitr stringu
       {
          $stav = 2;
          $file_text .= $c;
       }
       elseif ($stav == 8 && $c == '\\') //znak \ se tam opakuje
       {
          $stav = 8;
          $file_text .= $c;
       }
       elseif ($stav == 8 && ($c != '"' || $c != '\\')) //zadna zrada asi jenom escape sekvence
       {
          $stav = 2;
          $file_text .= $c;
       }
       elseif ($stav == 7 && $c == '\\') //POZOR mozny pokus o zradu ve znakovem literalu
       {
          $stav = 9;
          $file_text .= $c;
       }
       elseif ($stav == 7 && $c == '\'') //konec znakoveho literalu
       {
          $stav = 0;
          $file_text .= $c;
       }
       elseif ($stav == 7 && ($c != '\'' || $c != '\\')) //znaky uvnit znakovem literalu
       {
           $stav = 7;
           $file_text .= $c;
       }
       elseif ($stav == 9 && $c == '\'') ///ZRADA!! jsme stale uvnitr znakovem literalu
       {
           $stav = 7;
           $file_text .= $c;
       }
       elseif ($stav == 9 && $c != '\'') //je tam nejaky jiny znak
       {
           $stav = 7;
           $file_text .= $c;
       }
       elseif ($stav == 1 && $c == '/') //potvrzeno je to RADKOVY KOMENT
       $stav = 3;
       elseif ($stav == 1 && $c == '*') //potvrzeno je to BLOKOVY KOMENT
       $stav = 4;
       elseif ($stav == 1 && ($c != '/' || $c != '*')) //neco jinaciho...mozna deleni
       {
          $stav = 0;
          $file_text .= "/";
          $file_text .= $c;
       }
       elseif ($stav == 3 && $c == "\n") //timto konci radkovy retezec
        $stav = 0;
       elseif ($stav == 3 && $c == '\\') //POZOR mozne zalomeni na dalsi radek
        $stav = 5;
       elseif ($stav == 3 && ($c != "\n" || $c != '\\')) //neco uprostred kometaru
        $stav = 3;
       elseif ($stav == 5 && $c == "\n") //radkovy komentar je i na dalsim radku
        $stav = 3;
       elseif ($stav == 5 && $c == '\\')  //znak mozneho zalomeni se opakuje
        $stav = 5;
       elseif ($stav == 5 && ($c != "\n" || $c != '\\')) //zadne zalomeni, jenom si to z nas delalo srandu
        $stav = 3;
       elseif ($stav == 4 && $c == '*') //mozny konec blokoveho komentare
        $stav = 6;
       elseif ($stav == 4 && $c != '*') //vsechnz ostatni znaky
        $stav = 4;
       elseif ($stav == 6 && $c == '*') //znak * se opakuje
        $stav = 6;
       elseif ($stav == 6 && $c == '/') //konec blokoveho komentare
        $stav = 0;
       elseif ($stav == 6 && ($c != '*' || $c != '/')) //bylo to jenom obyc *
        $stav = 4;
    }
    
    return $file_text;
  }
 /*
  *  Vymaze vsechny retezce a znakove literaly, necha pri tom komentace napokoji.
  *  V komentarich se mohou vyskytnou retezce - ale jsou to ve skutecnosti komentare.
  *  Vrati upraveny text.
  *  @param $file_des retezec/text celeho souboru nebo jiz predem upraveneho              
  */
 function Destroy_STRING($file_des)
 {
    $comment_pokr = false;
    $stav = 0;
    $file_text = "";
    
    $arr = str_split($file_des); //rozdelit string na pismena, je to pole
    foreach ($arr as $c)
    {
      if ($stav == 0 && $c == '"') //zacatek stringu
        $stav = 2;
      elseif ($stav == 0 && $c == '/') //mozny zacatek komentu
      {
          $stav = 1;
          $file_text .= $c;
      }
      elseif ($stav == 0 && $c == '\'') //zacatek znakoveho literalu
        $stav = 7;
      elseif ($stav == 0 && ($c != '"' || $c != '/' || $c != '\'')) //obyc znak
      {
          $stav = 0;
          $file_text .= $c;      
      }
      elseif ($stav == 2 && $c == '\\') //POZOR mozny pokus o zradu v retezci
        $stav = 8;
      elseif ($stav == 2 && $c == '"') //konec retezce
        $stav = 0;
      elseif ($stav == 2 && ($c != '"' || $c != '\\')) //znaky uvnit stringu
        $stav = 2;
      elseif ($stav == 8 && $c == '"') //ZRADA!! jsme stale uvnitr stringu
        $stav = 2;
      elseif ($stav == 8 && $c == '\\') // \ se opakuje
        $stav = 8;
      elseif ($stav == 8 && ($c != '"' || $c != '\\')) //plany poplach, asi escape sekvence
        $stav = 2;
      elseif ($stav == 7 && $c == '\\') //POZOR mozny pokus o zradu ve znakovem literalu
        $stav = 9;
      elseif ($stav == 7 && $c == '\'') //konec rznakoveho literalu
        $stav = 0;
      elseif ($stav == 7 && ($c != '\'' || $c != '\\')) //znaky uvnit znakovem literalu
        $stav = 7;
      elseif ($stav == 9 && $c == '\'') ///ZRADA!! jsme stale uvnitr znakovem literalu
        $stav = 7;
      elseif ($stav == 9 && $c != '\'') //plany poplach, asi escape sekvence
        $stav = 7; 
      elseif ($stav == 1 && $c == '/') //potvrzeno je to RADKOVY KOMENT
      {
          $stav = 3;
          $file_text .= $c;
      }   
      elseif ($stav == 1 && $c == '*') //potvrzeno je to BLOKOVY KOMENT
      {
          $stav = 4;
          $file_text .= $c;
      }
      elseif ($stav == 1 && ($c != '/' || $c != '*')) //neco jinaciho...mozna deleni
      {
          $stav = 0;
          $file_text .= $c;
      }
      elseif ($stav == 3 && $c == "\n") //timto konci radkovy retezec
      {
          $stav = 0;
          $file_text .= $c;
      }
      elseif ($stav == 3 && $c == '\\') //POZOR mozne zalomeni na dalsi radek
      {
          $stav = 5;
          $file_text .= $c;
      }    
      elseif ($stav == 3 && ($c != "\n" || $c != '\\')) //neco uprostred kometaru
      {
          $stav = 3;
          $file_text .= $c;
      }
      elseif ($stav == 5 && $c == "\n") //radkovy komentar je i na dalsim radku
      {
          $stav = 3;
          $file_text .= $c;
      }
      elseif ($stav == 5 && $c == '\\') // se opakuje
      {
          $stav = 5;
          $file_text .= $c;
      }
      elseif ($stav == 5 && ($c != "\n" || $c != '\\')) //zadne zalomeni, jenom si to z nas delalo srandu
      {
          $stav = 3;
          $file_text .= $c;
      }
      elseif ($stav == 4 && $c == '*') //mozny konec blokoveho komentare
      {
          $stav = 6;
          $file_text .= $c;
      }
      elseif ($stav == 4 && $c != '*') //vsechnz ostatni znaky
      {
          $stav = 4;
          $file_text .= $c;
      }
      elseif ($stav == 6 && $c == '*') // * se opakuje
      { 
          $stav = 6;
          $file_text .= $c;
      }
      elseif ($stav == 6 && $c == '/') //konec blokoveho komentare
      {
          $stav = 0;
          $file_text .= $c;
      }
      elseif ($stav == 6 && ($c != '*' || $c != '/'))
      {
          $stav = 4;
          $file_text .= $c;
      }
      
    }
    
    return $file_text;
    
 }
 
 /*
  * Vymaze vsechny klicova slova, nahradi je mezerou a vreati 
  * upraveny text, /b je escape sekvence regularniho vyrazu jez urcuje 
  * ohraniceni slova (word boundary)
  * @param $file_des retezec/text celeho souboru nebo jiz predem upraveneho      
  */
 function Destroy_KEYWORDS($file_des)
 {
    $file_text = "";

    $patterns = array(
    '/\bauto\b/', '/\benum\b/', '/\brestrict\b/', '/\bunsigned\b/', 
    '/\bbreak\b/', '/\bextern\b/', '/\breturn\b/', '/\bvoid\b/',
    '/\bcase\b/', '/\bfloat\b/', '/\bshort\b/', '/\bvolatile\b/',
    '/\bchar\b/', '/\bfor\b/', '/\bsigned\b/', '/\bwhile\b/',
    '/\bconst\b/', '/\bgoto\b/', '/\bsizeof\b/', '/\b_Bool\b/',
    '/\bcontinue\b/', '/\bif\b/', '/\bstatic\b/', '/\b_Complex\b/',
    '/\bdefault\b/', '/\binline\b/', '/\bstruct\b/', '/\b_Imaginary\b/',
    '/\bdo\b/', '/\bint\b/', '/\bswitch\b/',
    '/\bdouble\b/', '/\blong\b/', '/\btypedef\b/',
    '/\belse\b/', '/\bregister\b/', '/\bunion\b/');
    
    $replacement = " ";
    
    $file_text = preg_replace($patterns, $replacement, $file_des);
    
    return $file_text;

 }
 
 /*
  * Najde vsechna klicova slova, vrati pocet v danem textu $file_des, 
  * pracuje se jiz bez maker, retezcu a komentaru
  * @param $file_des retezec/text ve kterem se vyhledava 
  */
 function Find_KEYWORDS($file_des)
 {
    $patterns = array(
    '/\bauto\b/', '/\benum\b/', '/\brestrict\b/', '/\bunsigned\b/', 
    '/\bbreak\b/', '/\bextern\b/', '/\breturn\b/', '/\bvoid\b/',
    '/\bcase\b/', '/\bfloat\b/', '/\bshort\b/', '/\bvolatile\b/',
    '/\bchar\b/', '/\bfor\b/', '/\bsigned\b/', '/\bwhile\b/',
    '/\bconst\b/', '/\bgoto\b/', '/\bsizeof\b/', '/\b_Bool\b/',
    '/\bcontinue\b/', '/\bif\b/', '/\bstatic\b/', '/\b_Complex\b/',
    '/\bdefault\b/', '/\binline\b/', '/\bstruct\b/', '/\b_Imaginary\b/',
    '/\bdo\b/', '/\bint\b/', '/\bswitch\b/',
    '/\bdouble\b/', '/\blong\b/', '/\btypedef\b/',
    '/\belse\b/', '/\bregister\b/', '/\bunion\b/');
    $pocet = 0;
    
    foreach ($patterns as $pattern)
    {
        
        preg_match_all($pattern, $file_des, $matches); //v poli matches budou vsechna klicova slova
        $pocet += count($matches[0]); //vsechny vyskyty jsou ulozene zde
    }
 
    return $pocet;
 }
 
 /*
  * Najde vsechny identifikatory, vrati pocet v danem textu $file_des, 
  * pracuje se jiz bez maker, retezcu a komentaru a klicovych slov
  * @param $file_des retezec/text ve kterem se vyhledava    
  */
 function Find_IDENT($file_des)
 {
    
    $pocet = 0;
    
    $pattern = '/\b[a-zA-Z_]+[a-zA-Z0-9_]*\b/';
    preg_match_all($pattern, $file_des, $matches);
    $pocet = count($matches[0]); //vsechny vyskyty jsou ulozene zde
    return $pocet;
    
 }
 /*
  * Vrati pocet znaku v komentari , komentar // a /**\/ pozor na pokracovani komentu na dalsim rakdu
  * @param $file_des retezec/text ve kterem se vyhledava  
  */
 function Find_COMMENT($file_des)
 {
    $pocet = 0;
    $stav = 0;
    $arr = str_split($file_des); //rozdelit string na pismena, je to pole
    
    foreach ($arr as $c)
    {
      if ($stav == 0 && $c == '/')  //mozny zacatek komentare
        $stav = 1;
      elseif ($stav == 0 && $c != '/') //dalsi znaky
        $stav = 0;
      elseif ($stav == 1 && $c == '/') //RAKDOVY komentar
      {
        $stav = 2;
        $pocet += 2; //protoze jsem nezapocitala tu predchozi /
      }
      elseif ($stav == 1 && $c == '*') //BLOKOVY komentar
      {
        $stav = 3;
        $pocet += 2; //protoze jsem nezapocitala tu predchozi /
      }
      elseif ($stav == 1 && ($c != '/' || $c != '*')) //nebyl to komentar
        $stav = 0;
      elseif ($stav == 2 && $c == "\n") //konec radkoveho
      {
        $stav = 0;
        $pocet++;
      }
      elseif ($stav == 2 && $c == '\\') //mozne zalomeni radkoveho
      {
        $stav = 4;
        $pocet++;
      }
      elseif ($stav == 2 && ($c != "\n" || $c != '\\')) //text uvnitr komentare
        $pocet++;
      elseif ($stav == 4 && $c == "\n") //radkovy komentar je i na dalsim radku
      {
        $stav = 2;
        $pocet++;
      }
      elseif ($stav == 4 && $c == '\\') // \ se opakuje
        $pocet++;
      elseif ($stav == 4 && ($c != "\n" || $c != '\\')) //zadne zalomeni, jenom si to z nas delalo srandu
      {
        $stav = 2;
        $pocet++;
      }
      elseif ($stav == 3 && $c == '*') //mozny konec blokoveho komentare
      {
        $stav = 5;
        $pocet++;
      }
      elseif ($stav == 3 && $c != '*') //vsechnz ostatni znaky
        $pocet++;
      elseif ($stav == 5 && $c == '*') // * se opakuje
        $pocet++;
      elseif ($stav == 5 && $c == '/') //konec blokoveho komentare
      {
        $stav = 0;
        $pocet++;
      }
      elseif ($stav == 5 && ($c != '*' || $c != '/')) //konec to nebyl
      {
        $stav = 3;
        $pocet++;
      }
      
    }
    
    return $pocet;
 }
 /*
  * Najde zadany pocet neprekryvajicich se vyskytu zadaneho retezce
  * @param $file_des retezec/text ve kterem se vyhledava  
  */
 function Find_PATTERN($file_des)
 {
    global $w_patern;
    $pocet = 0;
    
    $patern = preg_quote($w_patern, '/'); //pro pripad ze by tam byly special znaky, tohle je ohranici        

    @preg_match_all('/'.$patern.'/', $file_des, $matches);
    $pocet = count($matches[0]); //vsechny vyskyty jsou ulozene zde
    
    return $pocet;
 }
 
 /*
  * Funkce spocita pocet jednoduchych operatovu, jeste pred tim nez zacne pocitat,
  * tak vymaze mozne deklarace ukazatelu - to nejsou operatory
  * @param $file_des retezec/text ve kterem se vyhledava       
  */
 
 function Find_OPERATORS($file_des)
 {
    $pocet = 0;
    $file_text = "";
    $rep_patt = array();
    //datove typy
    $datovy_typ = array('\bchar\b', '\bsigned char\b', '\bunsigned char\b',
    '\bshort\b', '\bunsigned short\b', '\bint\b', '\bunsigned int\b', '\blong\b', '\bunsigned long\b',
    '\bfloat\b', '\bdouble\b', '\blong double\b', '\blong long\b',
    '\bunsigned long long\b');
    
    $uz_dat_typ = array('\bstruct\b', '\bunion\b');
    $identifikator = '\b[a-zA-Z_]+[a-zA-Z0-9_]*\b';
    
    $rep_patt = array();
    foreach ($datovy_typ as $d_t)
    {
      $patt = "/$d_t\s*\*\s*$identifikator/"; //he? DATOVY_TYP <0-N> whitespace * <0-N> whitespace IDENTIFIKATOR;
      array_push($rep_patt, $patt);
    }
    $replacement = " ";
    $file_text = preg_replace($rep_patt, $replacement, $file_des); //doufejme, ze text bez dalsich deklaraci ukazatelu
    
    $rep_patt = array();
    foreach ($uz_dat_typ as $uz_d_t)
    {
      $patt = "/$uz_d_t\s+$identifikator\s*\*\s*$identifikator/"; // UZIVATELSKY <1-N> whitespace IDENTIFIKATOR <0-N> whitespace * <0-N> whitespace IDENTIFIKATOR
      array_push($rep_patt, $patt);
    }
    $file_text = preg_replace($rep_patt, $replacement, $file_text); //tohle by melo zlikvidovat i uzivatelske
    
     //pocitani, s '/<{1,2}[^=]/' je problem
    $operators = array('/[^e|E]\+{1,2}[^=]/', '/[^e|E]-{1,2}[^=|>]/', '/\+=/', '/-=/',
    '/\*[^=]/', '/\*=/', '/\/[^=]/', '/\/=/', '/%[^=]/', '/%=/', '/[^<]<[^<=]/',
    '/<<[^=]/', '/<{1,2}=/', '/[^>|\-]>[^>=]/', '/>>[^=]/', '/>{1,2}=/', '/&{1,2}[^=]/',
    '/&=/', '/\|{1,2}[^=]/', '/\|=/', '/\^[^=]/', '/\^=/', '/[^\.0-9]\.[^\.0-9]/', '/->/', '/![^=]/', '/!=/',
    '/[^\+|\-|\*|\/|%|<|>|&|\||\^|!]={1,2}/', '/~/');
    
    foreach ($operators as $operator)
    {
        preg_match_all($operator, $file_text, $matches);
        $pocet += count($matches[0]); //vsechny vyskyty jsou ulozene zde
    }
    
    return $pocet;
 }
 /*
  * Otevira a zavira dane soubory, vola jednotlive funkce na hledani
  */  
 function Play_Seek($file_name)
 {
 
    global $k_param;
    global $o_param;
    global $i_param;
    global $w_param;
    global $c_param;
    global $dir;
    global $chyba_file_kod;
    global $chyba_open_dir;
    
    $pocet = 0;
    
  //pokud bude chyba pri otevirani, tak CHYBA 2, pokud adresar je true tak CHYBA 21
   $file = @fopen($file_name, "r");
   if(!$dir)
   {
      if (!$file)
        MessageBad($chyba_file_kod); //2
   }
   else
   {
      if (!$file)
        MessageBad($chyba_open_dir); //21
   }
    
   $text_file = stream_get_contents($file); //vytahnou z toho text
   fclose($file);
   
   if($k_param) //KLICOVA SLOVA, likviduju MAKRA, KOMENTY, RETEZCE
   {
     $text_file = Destroy_MACRO($text_file);
     $text_file = Destroy_COMMENT($text_file);
     $text_file = Destroy_STRING($text_file);
     $pocet = Find_KEYWORDS($text_file);
   }
   elseif($o_param) //JEDNODUCHE OPERATORY, likviduj MAKRA, KOMENTY, RETEZCE
   {
      $text_file = Destroy_MACRO($text_file);
      $text_file = Destroy_COMMENT($text_file);
      $text_file = Destroy_STRING($text_file);
      $pocet = Find_OPERATORS($text_file);
   }
   elseif($i_param) //IDENTIFIKATORY, likviduj MAKRA, KOMENTY, RETEZCE, KLICOVA SLOVA
   {
     $text_file = Destroy_MACRO($text_file);
     $text_file = Destroy_COMMENT($text_file);
     $text_file = Destroy_STRING($text_file);
     $text_file = Destroy_KEYWORDS($text_file);
     $pocet = Find_IDENT($text_file);
    
   }
   elseif($w_param)//NEPREKVRYVAJICI RETEZEC v celem souboru aka soubor ala string
      $pocet = Find_PATTERN($text_file);
   elseif($c_param) //KOMENTARE, likviduj MAKRA, pro bezpecnost likviduj i RETEZCE
   {
      $text_file = Destroy_MACRO($text_file);
      $text_file = Destroy_STRING($text_file);
      $pocet = Find_COMMENT($text_file);
   }
   
   return $pocet; 
 }
 
 
//Promenna obsahujici Napovedu, vztiskne se pri --help
  $helpMessage = "Skript pro analyzu zdrojovych souboru jazyja c.
Ve stanovenem formatu vypise statistiky komentaru, klicovych slov, 
operatoru a retezcu.
Parametry:
\t--help vypise napovedu
\t--input=fileordir vstupni soubor/adresar, pokud je zadan adresar
\t\ttak se analyzuji vsechny *.c a *.h soubory v danem adresari i jeho
\t\tpodadresarich, pri nezadani tohoto parametru se pouzije aktualni adresar
\t--nosubdir prohledavat se bude pouze v aktualnim adresari, ne v jeho podadresarich
\t--output=filename vystupni soubor
\t-k statiskika klicovych slov
\t-o statiskika jednoduchych operatoru
\t-i statistika vyskytu identifikatoru
\t-w=pattern statistika retezce pattern
\t-c statistika znaku komentaru
\t-p soubory se budou vypisovat bez uplne cesty
\tParametry -k -o -i -w -c nelze mezi sebou kombinovat\n";

//Zpracovani parametru, asi neumim pracovat s getopt

if ($argc == 1) //bez parametru, CHYBA = 1
  MessageBad($chyba_params_kod);
else
{
  array_shift($argv); // zbavime se argv[0];
  
  foreach ($argv as $params)
  {
    if (strcmp($params, "--help") == 0) //zadan parametr --help
    {
      $help = true;
      $help_cout++; 
    }
    elseif (strpos($params, "--input=") === 0) //zadan parametr --input, musi byt === kvuli strpos
    {
      $input_file = substr($params, 8); //ziskat jmeno souboru/adresare
      if (strlen($input_file) === 0) // zjistit, zda tam neco bylo, pokud prazdny, tak chyba
        MessageBad($chyba_params_kod);
      $input_cout++;
      $input = true;
    }
    elseif (strcmp($params, "--nosubdir") == 0) //zadan parametr --nosubdir
    {
      $nosubdir = true;
      $nosubdir_cout++;
    }
    elseif (strpos($params, "--output=") === 0) //zadan parametr --output, musi byt === kvuli strpos
    {
      $output_file = substr($params, 9); //ziskat jmeno souboru/adresare
      if (strlen($output_file) === 0) // zjistit, zda tam neco bylo, pokud prazdny, tak chyba
        MessageBad($chyba_params_kod);
      $output_cout++;
      $output = true;
    }
    elseif (strcmp($params, "-k") == 0) //zadan parametr -k
    {
      $k_param = true;
      $k_param_cout++;
    }
    elseif (strcmp($params, "-o") == 0) //zadan parametr -o
    {
      $o_param = true;
      $o_param_cout++;
    }
    elseif (strcmp($params, "-i") == 0) //zadan parametr -i
    {
      $i_param = true;
      $i_param_cout++;
    }
    elseif (strpos($params, "-w=") === 0) //zadan parametr -w, musi byt === kvuli strpos
    {
      $w_patern = substr($params, 3); //ziskat jmeno souboru/adresare
      if (strlen($w_patern) === 0) // zjistit, zda tam neco bylo, pokud prazdny, tak chyba
        MessageBad($chyba_params_kod);
      $w_param_cout++;
      $w_param = true;
    }
    elseif (strcmp($params, "-c") == 0) //zadan parametr -c
    {
      $c_param = true;
      $c_param_cout++;
    }
    elseif (strcmp($params, "-p") == 0) //zadan parametr -p
    {
      $p_param = true;
      $p_param_cout++;
    }
    else 
      MessageBad($chyba_params_kod);
  }

}

//--help nelze s nicim jinym kombinovat, CHYBA 1
if ($help && ($input || $nosubdir || $output || $k_param || $o_param || $i_param || 
    $w_param || $c_param || $p_param))
    MessageBad($chyba_params_kod);
    
//Parametry se nemohou opakovat, hoo ono to funguje, CHYBA 1
if ($help_cout > 1 || $input_cout > 1 || $nosubdir_cout > 1 || $output_cout > 1 ||
    $k_param_cout > 1 || $o_param_cout > 1 || $i_param_cout > 1 || $w_param_cout > 1 ||
    $c_param_cout > 1 || $p_param_cout > 1)
    MessageBad($chyba_params_kod);

//-k -o -i -w -c musi existovat alespon jeden z nich, pokud neexistuje --help, jinak CHYBA 1    
if ($help)
{
  echo "$helpMessage";
  exit(0);
}
else
{
  if($k_param || $o_param || $i_param || $w_param || $c_param); //OK alespon jeden existuje
  else
    MessageBad($chyba_params_kod);
}

//-k -o -i -w -c se nesmi kombinovat, CHYBA 1, mozna se opkauju, ale..
if (($k_param && ($o_param || $i_param || $w_param || $c_param)) || 
    ($o_param && ($i_param || $w_param || $c_param || $k_param)) ||
    ($i_param && ($w_param || $c_param || $k_param || $o_param)) ||
    ($w_param && ($c_param || $k_param || $o_param || $i_param)) ||
    ($c_param && ($k_param || $o_param || $i_param || $w_param)))
    MessageBad($chyba_params_kod);
    
//--input existuje a jeho parametr skutecne existuje, jinak CHYBA 2, pokud neexistuje pracujeme z aktualnim adresarem
if ($input)
{
  $filename = realpath($input_file);
  if (file_exists($filename));
  else
    MessageBad($chyba_file_kod);
  //--nosubdir a file nemuye byt dohromady
  if(is_file($filename))
  {  if ($nosubdir)
        MessageBad($chyba_params_kod);
  }
  else
    $dir = true;
}
else //neni zadan --input, pracujeme z aktualnim adresarem
{
  $filename = dirname(__FILE__); 
  $dir = true;
}
  
//ziskat vse z adresaru a podasreasru, POZOR! cerna magie! v zajmu bezpecnosti NEHYBAT! NEMENIT!
if($dir)
{
  if (!$nosubdir) // prohledavame  i podasresare
  {
    $pomoc_dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($filename));
    $pomoc_dir->rewind();
    while($pomoc_dir->valid())
    {
      if (!$pomoc_dir->isDot()) //neresit . ..
      {  
        $help_file = $pomoc_dir->key(); //ziskat absolutni jmeno i cestu
        $koncovka = substr($help_file, -2); //vrati mi koncovku
        if ($koncovka == ".c" || $koncovka == ".C" || $koncovka == ".h" || $koncovka == ".H")
          array_push($array_file, $help_file);
      }
      $pomoc_dir->next();
    }
  }
  else //prohledava se jenom aktualni adresar
  {
    $pomoc_dir = new DirectoryIterator($filename);
    while ($pomoc_dir->valid())
    {
      if (!$pomoc_dir->isDot()) //neresit . ..
      {
        $help_file = $pomoc_dir->getPathname(); //ziskat absolutni jmeno i cestu
        $koncovka = substr($help_file, -2); //vrati mi koncovku
        if ($koncovka == ".c" || $koncovka == ".C" || $koncovka == ".h" || $koncovka == ".H")
          array_push($array_file, $help_file);
      }
      $pomoc_dir->next();
    }

  }
}

if(!$dir)//pracuji s jednim souborem
{
    $pocet = 0;
    $pocet = Play_Seek($filename);
    if ($p_param)
      $filename = basename($filename);
// formatovani...tady to sice nemusim uvazovat, ale u adresaroveho modu budu muset kontrovat i velikoc celkoveho poctu 

    //$filename = "/homes/kazi/krivka/ipp/perl/tests/cst/Student/dir/file.c";   
    $str_pocet = "$pocet";
    $celkem = "CELKEM:";
    $file_len = strlen($filename);
    $celkem_len = strlen($celkem);
    
    //jaky je nejdelsi retezec - pro tvorbu poctu mezer
    if($file_len < $celkem_len)
      $nejdelsi = $celkem_len;
    else
      $nejdelsi = $file_len;
    
    //formatovani pro soubor  
    $mezery = " ";
    $pocet_mezer = 0;
    
    if ($file_len <= $nejdelsi)
      $pocet_mezer = $nejdelsi - $file_len;
      
    while($pocet_mezer)
    {
      $mezery .= " ";
      $pocet_mezer--; 
    }
      
    $soubor = "$filename" . "$mezery" . "$str_pocet" . "\n";
    
    //formatovani pro posledni radek
    $mezery = " ";
    $pocet_mezer = 0;
    
    if ($celkem_len <= $nejdelsi)
      $pocet_mezer = $nejdelsi - $celkem_len;
      
      while($pocet_mezer)
      {
        $mezery .= " ";
        $pocet_mezer--; 
      }
      
     $all = "$celkem" . "$mezery" . "$str_pocet" . "\n";
     
     if(!$output) // vypis na standartni vystup
     {
        echo "$soubor";
        echo "$all";
     }
     else //vypis do souboru
     {  
        $write_file = @fopen($output_file, "w"); //@ aby open nerval
        if (!$write_file)
          MessageBad($chyba_write_file);
          
        fwrite($write_file, $soubor);
        fwrite($write_file, $all);
        
        fclose($write_file);
        
     }
}
else //pracujeme z adresarem
{
    $celokvy_pocet = 0;
    $array_as_file = array();
    foreach ($array_file as $mom)
    {
      $pocet = 0;
      $pocet = Play_Seek($mom);
      $celokvy_pocet += $pocet;
      
      if ($p_param)
        $mom = basename($mom);
        
      $array_as_file["$mom"] = "$pocet";
    }
    
    ksort($array_as_file);
    $celkem = "CELKEM:";
    $celkem_len = strlen($celkem);
    $celkem_number = "$celokvy_pocet";
    $celkem_muber_len = strlen($celkem_number);
    $nejdelsi_file = 0;
    $nejdelsi_number = 0;
    $vypis = array();
    //potrebuju zjistit nejdelsi retezec
    foreach ($array_as_file as $polozka => $hodnota)
    {
      $file_len = strlen($polozka);
      $number_len = strlen($hodnota);
      
      if ($nejdelsi_file < $file_len)
        $nejdelsi_file = $file_len;
        
      if ($nejdelsi_number < $number_len)
        $number_len = $number_len;
    }
    //zda nahodou CELKEM neni delsi
    if ($nejdelsi_file < $celkem_len)
      $nejdelsi_file = $celkem_len;
    if ($nejdelsi_number < $celkem_muber_len)
      $nejdelsi_number = $celkem_muber_len;
      
    //vytvorit retezec jez se vytiskne
    foreach ($array_as_file as $polozka => $hodnota)
    {
      $mezery_file = " ";
      $mezery_number = "";
      $pocet_mezer_file = 0;
      $pocet_mezer_number = 0;
      $file_len = strlen($polozka);
      $number_len = strlen($hodnota);
       //spocitat kolik mezer musime pridat abz sedelo formatovani
      if ($file_len <= $nejdelsi_file)
        $pocet_mezer_file = $nejdelsi_file - $file_len;
      if ($number_len <= $nejdelsi_number)
        $pocet_mezer_number = $nejdelsi_number - $number_len;
        
      //vytvorit pozadovant pocet mezer  
      while($pocet_mezer_file)
      {
         $mezery_file .= " ";
         $pocet_mezer_file--;
      }
      while ($pocet_mezer_number)
      {
        $mezery_number .= " ";
        $pocet_mezer_number--;
      }
      //vlozit do pole, ktere se bude tisknout
      $soubor = "$polozka" . "$mezery_file" . "$mezery_number" . "$hodnota" . "\n";
      array_push($vypis, $soubor);
    }
    
      $mezery_file = " ";
      $mezery_number = "";
      $pocet_mezer_file = 0;
      $pocet_mezer_number = 0;
      
      //pozadovany pocet mezer i pro posledni radek
      if ($celkem_len <= $nejdelsi_file)
        $pocet_mezer_file = $nejdelsi_file - $celkem_len;
      if ($celkem_muber_len <= $nejdelsi_number)
        $pocet_mezer_number = $nejdelsi_number - $celkem_muber_len;
      
      //tvorba mezer pro posledni radek  
      while($pocet_mezer_file)
      {
         $mezery_file .= " ";
         $pocet_mezer_file--;
      }
      while ($pocet_mezer_number)
      {
        $mezery_number .= " ";
        $pocet_mezer_number--;
      }
      
      //celkove pole
      $all = "$celkem" . "$mezery_file" . "$mezery_number" . "$celkem_number" . "\n";
      array_push($vypis, $all);
      
      //tisk do souboru nebo na standartni vystup
      if ($output)
      {
        $write_file = @fopen($output_file, "w"); //@ aby open nerval
        if (!$write_file)
          MessageBad($chyba_write_file); 
      }
      
      foreach($vypis as $radek)
      {
        if(!$output) // vypis na standartni vystup
          echo "$radek";
        else //vypis do souboru
          fwrite($write_file, $radek);
      }
      if($output)
        fclose($write_file);
}
exit(0);
?>