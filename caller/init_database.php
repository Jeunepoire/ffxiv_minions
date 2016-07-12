<?php
require_once "../config.php";

require_once "../helper.php";
header("Content-Type: text/html; charset=utf-8");
// Create table for minions

$database->query("CREATE TABLE minions (
        id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        icon_url VARCHAR(255) NOT NULL,
        patch VARCHAR(50) NOT NULL,
        name_en VARCHAR(100) NOT NULL,
        name_fr VARCHAR(100) NOT NULL,
        name_de VARCHAR(100) NOT NULL,
        name_ja VARCHAR(100) NOT NULL,
        description_en TEXT NOT NULL,
        description_fr TEXT NOT NULL,
        description_de TEXT NOT NULL,
        description_ja TEXT NOT NULL,
        method VARCHAR(100),
        method_description_en TEXT,
        method_description_fr TEXT,
        method_description_de TEXT,
        method_description_ja TEXT,
        PRIMARY KEY ( id )
        );");
        

        
$database->query("CREATE TABLE mounts (
        id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        icon_url VARCHAR(255) NOT NULL,
        patch VARCHAR(50) NOT NULL,
        name_en VARCHAR(100) NOT NULL,
        name_fr VARCHAR(100) NOT NULL,
        name_de VARCHAR(100) NOT NULL,
        name_ja VARCHAR(100) NOT NULL,
        description_en TEXT NOT NULL,
        description_fr TEXT NOT NULL,
        description_de TEXT NOT NULL,
        description_ja TEXT NOT NULL,
        method VARCHAR(100),
        method_description_en TEXT,
        method_description_fr TEXT,
        method_description_de TEXT,
        method_description_ja TEXT,
        PRIMARY KEY ( id )
        );");
$database->query("CREATE TABLE players (
        id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        world VARCHAR(50) NOT NULL,
        title VARCHAR(100) ,
        portrait VARCHAR(250) NOT NULL,
        race VARCHAR(50) NOT NULL,
        clan VARCHAR(50) NOT NULL,
        gender VARCHAR(50) NOT NULL,
        nameday VARCHAR(200) NOT NULL,
        guardian VARCHAR(100) NOT NULL,
        grandCompany VARCHAR(50) ,
        freeCompany VARCHAR(100),
        freeCompanyId VARCHAR(30),
        last_update_date DATE NOT NULL,
        PRIMARY KEY ( id )
        );");
$database->query("CREATE TABLE player_minion (
        p_id INT NOT NULL,
        m_id INT NOT NULL,
        primary key (p_id, m_id),
        FOREIGN KEY (p_id) REFERENCES players(id),
        FOREIGN KEY (m_id) REFERENCES minions(id)
        );");
        
$database->query("CREATE TABLE player_mounts (
        p_id INT NOT NULL,
        m_id INT NOT NULL,
        primary key (p_id, m_id),
        FOREIGN KEY (p_id) REFERENCES players(id),
        FOREIGN KEY (m_id) REFERENCES mounts(id)
        );");
        
$database->query("CREATE TABLE minions_methode (
        m_id INT NOT NULL,
        method VARCHAR(100),
        method_description_en TEXT,
        method_description_fr TEXT,
        method_description_de TEXT,
        method_description_ja TEXT,
        primary key (m_id, method),
        FOREIGN KEY (m_id) REFERENCES minions(id)
        );");
        
$database->query("CREATE TABLE mounts_methode (
        m_id INT NOT NULL,
        method VARCHAR(100),
        method_description_en TEXT,
        method_description_fr TEXT,
        method_description_de TEXT,
        method_description_ja TEXT,
        primary key (m_id, method),
        FOREIGN KEY (m_id) REFERENCES mounts(id)
        );");
$updateMinions = $_GET["update"];

if ($updateMinions) {

	// Get all minions and insert or update the informations from xivdb

	$first = 1;
	$last = $_GET["last"];
	$type = $_GET["type"];
	if ($last > 0) {
		foreach(range($first, $last) as $number) {
		        switch($type){
		                case "minions":
		                        insert_update_minion($number);
		                        break;
	                        case "mounts":
	                               insert_update_mount($number); 
	                               break;
                               default:
                                        exit;
		        }
			
		}
	}

	read_write_methode("minions","../minions.json",$_GET["readonly"]);
	read_write_methode("mounts","../mounts.json",$_GET["readonly"]);
}

?>