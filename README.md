# dbconnect functions

# use configuration

     Session: 

          $_SESSION['HOST']     = "localhost";
          $_SESSION['USER']     = "root";
          $_SESSION['PASSWORD'] = "";
          $_SESSION['DATABASE'] = "test";

     or .env file

          HOST     = "localhost"
          USER     = "root"
          PASSWORD = ""
          DATABASE = "test"

# functions:


    [0] => __construct
    [1] => db_query
    [2] => get_table_info
    [3] => getDatabaseName
    
    [4] => getTables
         Helper::e($db->getTables());

    [5] => columnHelper
    [6] => table

    $result = $db->table('testtabelle',['testtabellenfeld'])
                ->where('id > ?','0')
                ->select('result');

                or

                 ->select('string'); // SQL
                 ->select('table'); // Table infos

    [7] => joinleft
    [8] => joinright
    [9] => join

         $result = $db->table('testtabelle',['testtabellenfeld'])
                 ->join('user','testtabelle.userid = user.id',['vorname','nachname'])
                 ->join('useraddress','useraddress.userid = user.id',['land','adrresse'])
                 ->where('user.id > ?','0')
                 ->select('result');


    [10] => where

             ->where('user.id > ?','0')

    [11] => orwhere

    [12] => setwhere
    [13] => setwheresearch
    [14] => subquery
    [15] => group
    [16] => order
    [17] => limit
    [18] => distinct
    [19] => cleanInsert
    [20] => splittimestamp
    [21] => clearTables
    [22] => pager
    [23] => toString
    [24] => select
    [25] => insert
    [26] => save
    [27] => update
    [28] => delete
    [29] => multiquery
    [30] => query
    [31] => cleanQuery
    [32] => getArrQuery
    [33] => setArrQuery
    [34] => format_date
    [35] => Debug
    [36] => is_serialized
    [37] => getLanguage_name
    [38] => setLanguage_name
    [39] => setDevelop
    [40] => createTable
    [41] => createInsert
    [42] => getCrypt_type
    [43] => setCrypt_type
    [44] => getCrypt_cipher
    [45] => setCrypt_cipher
    [46] => setCrypt_column
    [47] => getAi
    [48] => xDebug
