<?php
    require_once "config.php";
    require_once "language.php";
    $random_id = 0;
    
    function get_lang(){
        $lang = empty($_GET["lang"]) ? "en" : $_GET["lang"];
        return $lang;
    }
    
    function get_language_text($name,$lang=""){
        global $language_texts;
        
        $languageObject = $language_texts[$name][empty($lang) ? get_lang():$lang];
        return empty($languageObject) ? $language_texts[$name]["en"] : $languageObject;
    }
    
    function get_title_language_text($title){
        
        if(get_lang() != "en"){
            $getdata = http_build_query(
                array('one'=>'titles','string'=>$title));
            $context = stream_context_create($opts);
            $search_url = "https://api.xivdb.com/search?".$getdata;
            $json = file_get_contents($search_url);
            $obj = json_decode($json);
            $prop = "name_".get_lang();
            $translated_title = $obj->titles->results[0]->$prop;
            return $translated_title;
        }
        return $title;
    }
    
    function create_table($title,$sql_data,$type){
        $lang = get_lang();
        $table = '<div class="panel panel-primary">
        <div class="panel-heading">'.$title.'</div>
        <div class="panel-body">';
        $icon = get_language_text("icon");
        $name = get_language_text("name");
        $patch = get_language_text("patch");
        $method = get_language_text("method");
        $description = get_language_text("description");
        $table .= "<table class='table table-striped'><thead><tr><th>$icon</th><th>$name</th><th>$patch</th><th>$method</th><th>$description</th></tr></thead>";
        

        $table .= "<tbody>";
        foreach($sql_data as $minion_data){
            $name = ucwords($minion_data['name_'.$lang]);
            $m_id = $minion_data['id'];
            $icon_url = $minion_data['icon_url'];
            $patch = $minion_data['patch'];
            $methode_lang = $minion_data['method_description_'.get_lang()];
            $methode = empty($methode_lang) ? $minion_data['method_description_en'] : $methode_lang;
                
            $methode_name = $minion_data['method'] ;
            if(!empty($methode_name)){
                $methodes_en = get_language_text("methodes","en");
                $m_index = array_search($methode_name,$methodes_en);
                $methode_name = get_language_text("methodes")[$m_index];
            }
            $dom_id = $type."_".$m_id;
            $table .= "<tr id='$dom_id'>";
            $table .= "<td class='shrink'><img class='media-object' src=$icon_url></td>";
            $base_url = get_lang() == "en" ? "https://xivdb.com" : "https://$lang.xivdb.com";
            $table .= "<td class='shrink'><a href='$base_url/$type/$m_id'>$name</a></td>";
            $table .= "<td class='shrink'>$patch</td>";
            $table .= "<td class='shrink'>$methode_name</td>";
            $table .= "<td class='expand'>$methode</td>";
            $table .= "</tr>";
        }
        $table .= "</tbody></table></div>
        </div>";
        return $table;
    }
    
    function create_ranking($fc = ""){
        global $database;
        //$lang = get_lang();
        $nr = get_language_text("nr");
        $name = get_language_text("name");
        $world = get_language_text("world");
        $number_minions = get_language_text("number_minions");
        $number_mounts = get_language_text("number_mounts");
        $number_all = get_language_text("number_all");
        $last_sync_title = get_language_text("last_synced");
        $table = "<table class='table table-condensed'><thead><tr><th>$nr</th><th>$name</th><th>$world</th><th>$number_minions</th><th>$number_mounts</th><th>$number_all</th><th>$last_sync_title</th></tr></thead>";
        $where_clause = empty($fc) ? "" : ["freeCompany"=>$fc];
        $players = $database->select("players",["id","name","world","last_update_date"],$where_clause);
        $ranking = array();
        foreach($players as $player){
            $count_minions = $database->count("player_minion",["p_id[=]"=>$player["id"]]);
            $count_mounts = $database->count("player_mounts",["p_id[=]"=>$player["id"]]);
            $count = $count_minions+ $count_mounts;
            array_push($ranking,array($count,$count_minions,$count_mounts,$player));
        }
        arsort($ranking);
        $nr = 1;
        foreach($ranking as $r_player){
            $player = $r_player[3];
            $p_id = $player['id'];
            $name = ucwords($player['name']);
            $world = ucwords($player['world']);
            $count = $r_player[0];
            $count_minions = $r_player[1];
            $count_mounts = $r_player[2];
            $last_sync_date = $player['last_update_date'];
            $table .= "<tr class='active' id='$p_id'><td>$nr</td><td><a onclick='loadCharakter($p_id)'>$name</a></td><td>$world</td><td>$count_minions</td><td>$count_mounts</td><td>$count</td><td>$last_sync_date</td></tr>";
            $nr++;
        }
        $table .= '</table>';
        return $table;
    }
    
    function create_thumbnail($title,$sql_data,$type){
        global $random_id;
        $random_id_tag = "div_".$random_id;
        $random_id++;
        $thumbnail = '<div class="panel panel-primary">
        <div class="panel-heading" data-toggle="collapse" data-target="#'.$random_id_tag.'" aria-expanded="true" aria-controls="'.$random_id_tag.'">'.
        $title.'</div>
        <div class="panel-body">';
        $thumbnail .= '<div class="collapse  in" id="'.$random_id_tag.'">';
        $count = 0;
        foreach($sql_data as $minion_data){
            
            $count++;
            $name = ucwords($minion_data['name']);
            $m_id = $minion_data['id'];
            $icon_url = $minion_data['icon_url'];
            $description = $minion_data['description'];
            $dom_id = $type."_".$m_id;
            $thumbnail .= '<div class="col-xs-0 col-md-2" style="width:auto; padding:0px">';
            $thumbnail .= "<a  id='$dom_id' href='https://xivdb.com/$type/$m_id' class='thumbnail' >";
            $thumbnail .= "<img class='media-object' alt='$name' src=$icon_url >";
            $thumbnail .= "</a>";
            $thumbnail .= "</div>";
        }
        $thumbnail .= '</div>';
        $thumbnail .= "</div></div>";
        return $thumbnail;
    }
    
    function create_dropdown_menu($type){
        $methodes = get_language_text("methodes");
        $methodes_en = get_language_text("methodes","en");
        //var_dump($methodes);
        $dropdown = "";
        $class = $type."_methode";
        foreach($methodes as $i=>$methode){
            $methode_get = urlencode ($methodes_en[$i]);
            $dropdown .= "<li><a id='$methode_get' class='$class'>$methode</a></li>";
        }
        return $dropdown;
    }
    
    function insert_update_charakter_by_id($id){
        //Get charakter from lodestone
        $api = new Viion\Lodestone\LodestoneAPI();
        $character = $api->Search->Character($id);
        
        return insert_update_charakter($character);
    }
    
    function insert_update_charakter_by_name($name,$server){
        //Get charakter from lodestone
        $api = new Viion\Lodestone\LodestoneAPI();
        $character = $api->Search->Character($name, $server);
        
        return insert_update_charakter($character);
    }
    
    function insert_update_charakter($character){
        global $database;
        if(empty($character->id)){
            return "Could not find the charakter '$name' on server '$server'";
            exit;
        }
        $c_name = strtolower($character->name);
        $c_world = strtolower($character->world);
        $c_portrait = $database->quote($character->portrait);
        
        
        
        //Check if an charakter with the same id already exists
        $p_id = $database->get("players", "id", ["id" => $character->id]);
        $output;
        if(!$player && empty($p_id)){
            //Insert new charakter
            $database->insert("players", [
            	"id" => $character->id,
            	"name" => $c_name,
            	"world" => $c_world,
            	"title" => $character->title,
            	"portrait" => $c_portrait,
            	"race" => $character->race,
            	"clan" => $character->clan,
            	"gender" => $character->gender,
            	"nameday" => $character->nameday,
            	"guardian" => $character->guardian,
            	"grandCompany" => $character->grandCompany,
            	"freeCompany" => $character->freeCompany,
            	"last_update_date" => date("Y-m-d")
            ]);
            $output = "New charakter '$c_name' with id '$character->id' from server '$c_world' was added to database.";
        }
        else{
            //Update existing charakter
            $database->update("players", [
            	"name" => $c_name,
            	"world" => $c_world,
            	"title" => $character->title,
            	"portrait" => $c_portrait,
            	"race" => $character->race,
            	"clan" => $character->clan,
            	"gender" => $character->gender,
            	"nameday" => $character->nameday,
            	"guardian" => $character->guardian,
            	"grandCompany" => $character->grandCompany,
            	"freeCompany" => $character->freeCompany,
            	"last_update_date" => date("Y-m-d")
            ], ["id[=]"=>$character->id]);
            $output = "Charakter '$c_name' with id '$character->id' from server '$c_world' was updated.";
        }
        
        $p_id = $character->id;
        
        //Get all minions from current charakter
        $minions = $character->minions;
        insert_item_char($p_id,$minions,"minions","player_minion");
        
        $mounts = $character->mounts;
        insert_item_char($p_id,$mounts,"mounts","player_mounts");
        
        return $output;
    }
    
    function insert_item_char($p_id,$items,$table,$link_table){
        global $database;
        $datas = $database->select($table, [
        	"id",
        	"name"
        ]); 
        
        
        //Add all minions of an charakter to databese
        foreach($datas as $data)
        {
            
            $have = false;
            $db_minion = strtolower($data['name']);
            $m_id = $data["id"];
            
            foreach($items as $item){
                
                $player_item = strtolower($item['name']);
                if ($db_minion == $player_item) {
                    
                    $database->query("REPLACE INTO $link_table VALUES (
                        $p_id, 
                        $m_id);");
                    break;
                }
                
            }
        }
    }

    function insert_update_minion($id){
        global $database;
        $json = file_get_contents("https://api.xivdb.com/minion/$id");
        $obj = json_decode($json);
        $db_minion = strtolower($obj->name);
        $patch = empty($obj->patch) ? "2.0" : $obj->patch->number;
        
        if(empty($obj->id)){
            //echo "Minion with number '$number' does not exists.";
        }
        elseif($db_minion == "wind-up merlwyb" ||
            $db_minion == "wind-up kan-e" ||
            $db_minion == "wind-up raubahn"){
            
        }
        elseif($obj->id == 68 ||$obj->id == 69 || $obj->id == 70){
            
        }
        else{
            $xivdb_icon = $database->quote("https://xivdb.com".$obj->xivdb_icon);
            
            $db_id = $database->get("minions",["id"],["id[=]"=>$id]);
            if(empty($db_id)){
                $database->insert("minions",[
                    "id"=>$obj->id,
                    "name"=>$obj->name,
                    "icon_url" => $xivdb_icon,
                    "patch" => $patch,
                    "name_en"=>$obj->name_en,
                    "name_fr"=>$obj->name_fr,
                    "name_de"=>$obj->name_de,
                    "name_ja"=>$obj->name_ja,
                    "description_en" => $obj->info1_en,
                    "description_fr" => $obj->info1_fr,
                    "description_de" => $obj->info1_de,
                    "description_ja" => $obj->info1_ja]);
            }
            else{
                $database->update("minions",[
                    "id"=>$obj->id,
                    "name"=>$obj->name,
                    "icon_url" => $xivdb_icon,
                    "name_en"=>$obj->name_en,
                    "name_fr"=>$obj->name_fr,
                    "name_de"=>$obj->name_de,
                    "name_ja"=>$obj->name_ja,
                    "description_en" => $obj->info1_en,
                    "description_fr" => $obj->info1_fr,
                    "description_de" => $obj->info1_de,
                    "description_ja" => $obj->info1_ja],
                    ["id[=]"=>$id]);
            }
        }
    }
    
    function insert_update_mount($id){
        global $database;
        $json = file_get_contents("https://api.xivdb.com/mount/$id");
        $obj = json_decode($json);
        $db_minion = strtolower($obj->name);
        $patch = empty($obj->patch) ? "2.0" : $obj->patch->number;
        
        if(empty($obj->id)){
            //echo "Minion with number '$number' does not exists.";
        }
        else{
            $xivdb_icon = $database->quote("https://xivdb.com".$obj->xivdb_icon);
            
            $db_id = $database->get("mounts",["id"],["id[=]"=>$id]);
            if(empty($db_id)){
                $database->insert("mounts",[
                    "id"=>$obj->id,
                    "name"=>$obj->name,
                    "icon_url" => $xivdb_icon,
                    "patch" => $patch,
                    "name_en"=>$obj->name_en,
                    "name_fr"=>$obj->name_fr,
                    "name_de"=>$obj->name_de,
                    "name_ja"=>$obj->name_ja,
                    "description_en" => $obj->info1_en,
                    "description_fr" => $obj->info1_fr,
                    "description_de" => $obj->info1_de,
                    "description_ja" => $obj->info1_ja]);
            }
            else{
                $database->update("mounts",[
                    "id"=>$obj->id,
                    "name"=>$obj->name,
                    "icon_url" => $xivdb_icon,
                    "name_en"=>$obj->name_en,
                    "name_fr"=>$obj->name_fr,
                    "name_de"=>$obj->name_de,
                    "name_ja"=>$obj->name_ja,
                    "description_en" => $obj->info1_en,
                    "description_fr" => $obj->info1_fr,
                    "description_de" => $obj->info1_de,
                    "description_ja" => $obj->info1_ja],
                    ["id[=]"=>$id]);
            }
        }
    }
    
    function read_write_methode($table,$file,$readOnly){
        global $database;
        
        //Read local file
        $json = file_get_contents($file);
        $read_minons = json_decode($json);
        
        //Update mehtode from local file
        foreach($read_minons as $minion){
            $database->update($table,[
                "method" => $minion->method,
                "method_description_en" => $minion->method_description_en,
                "method_description_fr" => $minion->method_description_fr,
                "method_description_de" => $minion->method_description_de,
                "method_description_ja" => $minion->method_description_ja],
                ["id[=]"=>$minion->id]);
        }
        
        if(!$readOnly){
            //Save the database in the file / update new minions to file
            $minions = $database->select($table,
                ["id","name","method","method_description_en","method_description_fr",
                "method_description_de","method_description_ja"]);
            $json_informations = json_encode($minions,JSON_PRETTY_PRINT);
            file_put_contents($file, $json_informations);
        }
    }
?>
        
        