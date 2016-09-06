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
        $count = count($sql_data);
        $table = '<div class="panel panel-primary">
        <div class="panel-heading"><h4><b>'.$title.": $count".'</b></h4></div>
        <div class="panel-body">';
        $icon = get_language_text("icon");
        $name = get_language_text("name");
        $patch = get_language_text("patch");
        $can_fly = get_language_text("can_fly");
        $method = get_language_text("method");
        
        $description = get_language_text("description");
        $table .= "<table class='table table-striped'>
                    <thead><tr><th>$icon</th><th>$name</th><th>$patch</th>";
        $table .= $type == "mount" ? "<th>$can_fly</th>":"";
        $table .= "<th>$method</th><th>$description</th></tr></thead>";
        

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
            $base_url = get_lang() == "en" ? "https://xivdb.com" : "https://$lang.xivdb.com";
            $table .= "<td class='shrink'><a href='$base_url/$type/$m_id'><img class='media-object' src=$icon_url></a></td>";
            
            $table .= "<td class='shrink'><a href='$base_url/$type/$m_id'>$name</a></td>";
            $table .= "<td class='shrink'>$patch</td>";
            if($type == "mount"){
                $can_fly =  $minion_data['can_fly'];
                if($can_fly == 0){
                    $can_fly = get_language_text("no");
                }
                elseif($can_fly == 1){
                    $can_fly = get_language_text("yes");
                }
                else{
                    $can_fly = get_language_text("unknown");
                }
                
                $table .= "<td class='shrink'>$can_fly</td>";
            }
            $table .= "<td class='shrink'>$methode_name</td>";
            $table .= "<td class='expand'>$methode</td>";
            $table .= "</tr>";
        }
        $table .= "</tbody></table></div>
        </div>";
        return $table;
    }
    
    function create_ranking($type = "",$fc = ""){
        global $database;
        
        $table = crate_ranking_header($type);
        $players = $database->select("players",["id","name","world","last_update_date"],
            empty($fc) ? "" : ["freeCompanyId"=>$fc]);
        $ranking = get_ranking_players($players,$type);
        $table .= crate_ranking_table($ranking,$type);
        if(!empty($fc)){
            $table .= get_missing_player_ranking_rows($fc);
        }
        $table .= '</table>';
        
        return $table;
    }
    
    function get_ranking_players($players,$type = ""){
        global $database;
        $ranking = array();
        foreach($players as $player){
            $count_minions = $database->count("player_minion",["p_id[=]"=>$player["id"]]);
            $count_mounts = $database->count("player_mounts",["p_id[=]"=>$player["id"]]);
            $count = $count_minions+ $count_mounts;
            $key;
            switch($type){
                case "minions":
                    $key = $count_minions;
                    break;
                case "mounts":
                    $key = $count_mounts;
                    break;
                default:
                    $key = $count;
                    break;
                    
            }
            $ranking[] = array($key,(object)array("all"=>$count,"minions"=>$count_minions,"mounts"=>$count_mounts,"player"=>$player));
            
        }
        arsort($ranking);
        return $ranking;
    }
    
    function crate_ranking_header($type = ""){
        $nr = get_language_text("nr");
        $name = get_language_text("name");
        $world = get_language_text("world");
        $number_minions = get_language_text("number_minions");
        $number_mounts = get_language_text("number_mounts");
        $number_all = get_language_text("number_all");
        $last_sync_title = get_language_text("last_synced");
        $table = "<table class='table table-condensed'><thead><tr><th>$nr</th><th>$name</th><th>$world</th>";

        switch($type){
            case "minions":
                $table .= "<th>$number_minions</th>";
                break;
            case "mounts":
                $table .= "<th>$number_mounts</th>";
                break;
            default:
                $table .= "<th>$number_minions</th>";
                $table .= "<th>$number_mounts</th>";
                $table .= "<th>$number_all</th>";
                break;
                
        }
        $table .= "<th>$last_sync_title</th></tr></thead>";
        return $table;
    }
    
    function get_ranking_of_player($id,$world = "",$type=""){
        global $database;
        
        $players = $database->select("players",["id","name","world","last_update_date"],empty($world) ? "" : ["world[=]"=>$world] );
        $ranking = get_ranking_players($players,$type);
        $nr = 0;
        $count_befor = $ranking[0][0];
        foreach($ranking as $rank){
            $count_key = $rank[0];
            if($count_befor != $count_key){
                $nr++;
                $count_befor = $count_key;
            }
            
            if($rank[1]->player['id'] == $id){
                return $nr;
            }
        } 
    }
    
    function create_char_ranking($id,$world = ""){
        $cell = "";
        $gl_rank_all = get_ranking_of_player($id,$world);
        $gl_rank_minion = get_ranking_of_player($id,$world,"minions");
        $gl_rank_mounts = get_ranking_of_player($id,$world,"mounts");
        $cell .= "<b>".get_language_text("all").":</b> ".$gl_rank_all;
        $cell .= "</br>";
        $cell .= "<b>".get_language_text("minions").":</b> ".$gl_rank_minion;
        $cell .= "</br>";
        $cell .= "<b>".get_language_text("mounts").":</b> ".$gl_rank_mounts;
        return $cell;
    }
    
    function crate_ranking_table($ranking,$type = ""){
        $table = "";
        $nr = 0;
        $count_befor = $ranking[0][0];
        foreach($ranking as $rank){
            $count_key = $rank[0];
            if($count_befor != $count_key){
                $nr++;
                $count_befor = $count_key;
            }
            
            $table .= create_ranking_row($nr,$rank[1]->player,$rank,$type);
        } 
        return $table;
    }
    
    function create_ranking_row($nr,$player,$rank = null,$type =""){
        $p_id = $player['id'];
        $row = "<tr class='active' id='$p_id'>";

        $row .= create_inner_ranking_row($nr,$player,$rank,$type);
        $row .= "</tr>";
        return $row;
    }
    
    function create_inner_ranking_row($nr,$player,$rank = null,$type =""){
        $p_id = $player['id'];
        $last_sync_date_button = "<button class='btn btn-info' onclick='updateCharakter($p_id)'>".get_language_text("update_char")."</button>";
        if($rank == null){
            $rank = array(0,(object)array("all"=>0,"minions"=>0,"mounts"=>0,"player"=>$player));
        }
        
        $name = ucwords($player['name']);
        $world = ucwords($player['world']);
        $date_diff = date_diff(date_create($player['last_update_date']), date_create(date("Y-m-d")));
        $last_sync_date = empty($player['last_update_date']) || $date_diff->d > 7 
            ? $last_sync_date_button : $player['last_update_date'];
        $row = "<td>$nr</td><td><a onclick='loadCharakter($p_id)'>$name</a></td><td>$world</td>";

        switch($type){
            case "minions":
                $count_minions = $rank[1]->minions;
                $row .= "<td>$count_minions</td>";
                break;
            case "mounts":
                $count_mounts = $rank[1]->mounts;
                $row .= "<td>$count_mounts</td>";
                break;
            default:
                $count_minions = $rank[1]->minions;
                $count_mounts = $rank[1]->mounts;
                $count_all = $rank[1]->all;
                $row .= "<td>$count_minions</td>";
                $row .= "<td>$count_mounts</td>";
                $row .= "<td>$count_all</td>";
                break;
        }
        $row .= "<td>$last_sync_date</td>";
        return $row;
    }
    
    function get_missing_player_ranking_rows($fc){
        global $database;
        
        
        $api = new Viion\Lodestone\LodestoneAPI();
        $freeCompany = $api->Search->Freecompany($fc,true);
        
        $rows = "";
        foreach($freeCompany->members as $member){
            $in_table = $database->has("players", ["AND"=>["freeCompanyId"=>$fc,"id"=>$member["id"]]]);
            if(!$in_table){
                //$character = $api->Search->Character($member["id"]);
                $player = array(
                    "id"=>$member["id"],
                    "name"=>$member["name"],
                    "world"=>$member["world"],
                    "last_update_date"=>"");
                $rows .= create_ranking_row("999",$player);
            }
        }
        return $rows;
    }
    
    function get_rarest_object($id,$table){
        global $database;
        $result = $database->query("SELECT COUNT( p_id ),m_id FROM $table GROUP BY m_id ORDER BY COUNT( p_id ) ASC")->fetchAll();
        foreach($result as $obj){
            if($database->has($table,["AND"=>["p_id"=>$id,"m_id"=>$obj['m_id']]])){
                return $obj['m_id'];
            }
        }
    }
    
    function create_rarest_thumbnail($id){
        global $database;
        global $random_id;
        $random_id_tag = "div_".$random_id;
        $random_id++;
        $title = get_language_text("rarest");
        $thumbnail = '<div class="panel panel-primary">
        <div class="panel-heading" data-toggle="collapse" data-target="#'.$random_id_tag.'" aria-expanded="true" aria-controls="'.$random_id_tag.'"><h4><b>'.
        $title.'</b></h4></div>
        <div class="panel-body">';
        $thumbnail .= '<div class="collapse  in" id="'.$random_id_tag.'">';
        
        $minion_id = get_rarest_object($id,"player_minion");
        $mount_id = get_rarest_object($id,"player_mounts");
        
        $table = '<div class="media">';
        $table .= '<div>';
        
        $minion = $database->get("minions","*",["id[=]"=>$minion_id]);
        $minion_name = ucwords($minion['name']);
        $minion_icon_url = $minion['icon_url'];
        $minion_thumbnail .= create_thumbnail_link("minion",$minion_id,$minion_name,$minion_icon_url);
        
        $table .= $minion_thumbnail;
        $table .= "</div>";
        $table .= '<div class="col-xs-0 col-md-2" style="width:auto; padding:0px; padding-left:2em">';
        $table .= "<h4>  $minion_name</h4>";
        $table .= "</div></div>";
        
        $table .= '<div class="media">';
        $table .= '<div >';
        
        $mount = $database->get("mounts","*",["id[=]"=>$mount_id]);
        $mount_name = ucwords($mount['name']);
        $mount_icon_url = $mount['icon_url'];
        $mount_thumbnail .= create_thumbnail_link("mount",$mount_id,$mount_name,$mount_icon_url);
        
        $table .= $mount_thumbnail;
        $table .= "</div>";
        $table .= '<div class="col-xs-0 col-md-2" style="width:auto; padding:0px; padding-left:2em">';
        $table .= "<h4>  $mount_name</h4></div></div>";
        
        $thumbnail .= $table;
        $thumbnail .= '</div>';
        $thumbnail .= "</div></div>";
        return $thumbnail;
    }
    
    function create_thumbnail_link($type,$id,$name,$url,$remove_div = false){
        $thumbnail = "";
        $dom_id = $type."_".$id;
        $lang = get_lang();
        $lang = $lang == "en" ? "" : $lang.".";
        
        $thumbnail .= $remove_div ? "" :'<div class="col-xs-0 col-md-2" style="width:auto; padding:0px">';
        $thumbnail .= "<a  id='$dom_id' href='https://".$lang."xivdb.com/$type/$id' class='thumbnail' >";
        $thumbnail .= "<img class='media-object' alt='$name' src=$url >";
        $thumbnail .= "</a>";
        $thumbnail .= $remove_div ? "" :"</div>";
        return $thumbnail;
    }
    
    function create_thumbnail($title,$sql_data,$type){
        global $random_id;
        $random_id_tag = "div_".$random_id;
        $random_id++;
        $count = count($sql_data);
        $thumbnail = '<div class="panel panel-primary">
        <div class="panel-heading" data-toggle="collapse" data-target="#'.$random_id_tag.'" aria-expanded="true" aria-controls="'.$random_id_tag.'"><h4><b>'.
        $title.": $count".'</b></h4></div>
        <div class="panel-body">';
        $thumbnail .= '<div class="collapse  in" id="'.$random_id_tag.'">';
        $count = 0;
        foreach($sql_data as $minion_data){
            
            $count++;
            $name = ucwords($minion_data['name']);
            $m_id = $minion_data['id'];
            $icon_url = $minion_data['icon_url'];
            $description = $minion_data['description'];
            $thumbnail .= create_thumbnail_link($type,$m_id,$name,$icon_url);
            /*
            $dom_id = $type."_".$m_id;
            $thumbnail .= '<div class="col-xs-0 col-md-2" style="width:auto; padding:0px">';
            $thumbnail .= "<a  id='$dom_id' href='https://xivdb.com/$type/$m_id' class='thumbnail' >";
            $thumbnail .= "<img class='media-object' alt='$name' src=$icon_url >";
            $thumbnail .= "</a>";
            $thumbnail .= "</div>";
            */
        }
        $thumbnail .= '</div>';
        $thumbnail .= "</div></div>";
        return $thumbnail;
    }
    
    function create_dropdown_menu($type){
        global $database;
        $methodes = get_language_text("methodes");
        $methodes_en = get_language_text("methodes","en");
        //var_dump($methodes);
        $dropdown = "";
        $class = $type."_methode";
        foreach($methodes as $i=>$methode){
            $mehtod_en = $methodes_en[$i];
            $count = $database->count($type,["method[=]"=>$mehtod_en]);
            if($count > 0 || $mehtod_en == "All"){
                $methode_get = urlencode ($mehtod_en);
                $dropdown .= "<li><a id='$methode_get' class='$class'>$methode</a></li>";
            }
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
            	"freeCompanyId" => $character->freeCompanyId,
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
            	"freeCompanyId" => $character->freeCompanyId,
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
        $logs;
        //Read local file
        $json = file_get_contents($file);
        $read_collectables = json_decode($json);
        
        $missing = 0;
        //Update mehtode from local file
        foreach($read_collectables as $coll){
            $logs .= "-> $table - $coll->id updated.</br>";
            $logs .= "--> Methode: $coll->method || Desciption: - $coll->method_description_en.</br>";
            $data = $table == "mounts" ? [
                "method" => $coll->method,
                "can_fly" => $coll->can_fly,
                "method_description_en" => $coll->method_description_en,
                "method_description_fr" => $coll->method_description_fr,
                "method_description_de" => $coll->method_description_de,
                "method_description_ja" => $coll->method_description_ja] :
                    [
                "method" => $coll->method,
                "method_description_en" => $coll->method_description_en,
                "method_description_fr" => $coll->method_description_fr,
                "method_description_de" => $coll->method_description_de,
                "method_description_ja" => $coll->method_description_ja];
            $database->update($table,$data,["id[=]"=>$coll->id]);
            if(empty($coll->method)){
                $missing++;
            }
        }
        $logs .= "===> $table is missing '$missing' methods.</br>";
        if(!$readOnly){
            $logs .= "</br>Write database to file.</br";
            $list = $table == "mounts" ?  ["id","name","can_fly","method","method_description_en","method_description_fr",
                "method_description_de","method_description_ja"] : ["id","name","method","method_description_en","method_description_fr",
                "method_description_de","method_description_ja"];
            //Save the database in the file / update new minions to file
            $minions = $database->select($table,$list);
            $json_informations = json_encode($minions,JSON_PRETTY_PRINT);
            file_put_contents($file, $json_informations);
        }
        $logs .= "The methodes for table '$table' have been updated.</br></br>";
        return $logs;
    }
?>
        
        