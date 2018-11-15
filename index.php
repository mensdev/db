<?php

use Glzd\DB;

spl_autoload_register(
    function ($class_name) {
        include $class_name . '.php';
    }
);

$db = new DB('.settings');

// Creating a simple table using SQLite
/*$db->create('test_table',
    'id',
    [
        ['text','name'],
        ['integer','age']
    ]
);*/

// Creating a more complex table using SQLite without a primary key
/*$db->create('more_complex_table',
    null,
    [
        ['text','name','nn','Mike'],
        ['integer','age'],
        ['text','position']
    ]
);*/

// Creating a simple table using mySQL
/*$db->create('test_table',
    'id',
    [
        ['varchar(255)','name'],
        ['int(11)','age']
    ]
);*/

// Creating a more complex table using mySQL without a primary key
/*$db->create('more_complex_table',
    null,
    [
        ['varchar(255)','name','nn','Mike'],
        ['int(11)','age'],
        ['varchar(255)','position']
    ]
);*/

// Truncating table's data
//$db->clear('test_table');

// Dropping a table
//$db->removeTable('test_table');

// Inserting some data
/*$db->insert('more_complex_table',
    ['John',100,'PHP developer']
);
$db->insert('more_complex_table',
    ['Mike',55,'Python developer']
);
$db->insert('more_complex_table',
    ['Elton',21,'Java developer']
);*/

// Need to update smth, our guy grows young again :)
/*$db->update(
    'more_complex_table',
    ['age'=>35],
    ['age',100]
);*/

// Getting all the stuff
/*$res = $db->all('more_complex_table');
$db::vd($res);*/

// Getting specific stuff
/*$res = $db->get('more_complex_table',['age','NOT',55]);
$db::vd($res);*/

// Getting by a row
/*$res = $db->row('more_complex_table',2);
$db::vd($res);*/

// Deleting
/*$db->delete('more_complex_table',
    ['name','John']
);*/

// Custom query
/*$q = $db->query("SELECT position FROM more_complex_table
  WHERE name='John' AND age=55")->fetchAll();
db::vd($q);*/


