<?php 
//filtering example

//in calling page
$filters = [
    'query' => '',
    'params' => []
];

if (!empty($_GET['start'])) {
    $filters['query'] .= ' AND orders.order_date >= :start_date';
    $filters['params']['start_date'] = $_GET['start'];
}
if (!empty($_GET['end'])) {
    $filters['query'] .= ' AND orders.order_date <= :end_date';
    $filters['params']['end_date'] = $_GET['end'];
}
$lists = $class_object->exampleFunction($filters);

// in class 
public function exampleFunction($filters = [])
{
    $query = $filters['query'] ?? '';
    $params = $filters['params'] ?? [];
    $sql="...";

    $stmt = DBConnection::myQuery($sql);
    //other sql related params
  
    // Bind parameters safely
    Helper::bindFilterParams($stmt,$params??[]);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

//in Helper class
public static function bindFilterParams($stmt, $params = [])
{
    foreach ($params as $key => $value) {
        $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
        $stmt->bindValue(":".$key, $value, $type);
    }
}
  
