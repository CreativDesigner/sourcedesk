<?php
namespace Product;

class Software
{
    private $info;
    private $product;

    public function __construct($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM client_products WHERE ID = " . intval($id));
        if ($sql->num_rows == 1) {
            $this->info = $sql->fetch_object();

            $sql = $db->query("SELECT * FROM products WHERE ID = " . intval($this->info->product));
            if ($sql->num_rows == 1) {
                $this->product = $sql->fetch_object();
            }
        }
    }

    public function getProductInfo()
    {
        return $this->product;
    }

    public function found()
    {
        return $this->info !== null && $this->product !== null && $this->versions !== null;
    }

    public static function getInstance($id)
    {
        $obj = new Software($id);
        return $obj->found() ? $obj : false;
    }
}
